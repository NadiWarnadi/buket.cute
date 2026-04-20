<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\OrderDraft;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderDraftService
{
    protected ParameterValidationService $validationService;

    public function __construct(ParameterValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Create or get existing order draft untuk customer
     * IMPORTANT: Menggunakan eager loading untuk menghindari N+1 queries
     * 
     * @param Customer $customer
     * @return OrderDraft
     */
    public function getOrCreateDraft(Customer $customer): OrderDraft
    {
        // Eager load dengan customer
        $draft = OrderDraft::where('customer_id', $customer->id)
            ->with(['customer']) // Eager load
            ->where('expires_at', '>', now())
            ->orWhereNull('expires_at')
            ->latest()
            ->first();

        if ($draft && $draft->isActive()) {
            return $draft;
        }

        // Create new draft dengan expiry 24 jam
        return OrderDraft::create([
            'customer_id' => $customer->id,
            'data' => $this->getEmptyDraftData($customer),
            'step' => 'collecting_name',
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Update draft dengan extracted parameters
     * 
     * @param OrderDraft $draft
     * @param array $extractedData
     * @return array ['draft' => OrderDraft, 'validation' => array]
     */
    public function updateDraftWithExtraction(OrderDraft $draft, array $extractedData): array
    {
        $data = $draft->data ?? [];

        // Update customer info
        if (!empty($extractedData['customer_name'])) {
            $data['customer_name'] = $extractedData['customer_name'];
        }

        // Update product info
        if (!empty($extractedData['product_data'])) {
            $product = $extractedData['product_data'];
            $data['product_id'] = $product['product_id'];
            $data['product_name'] = $product['product_name'];
            $data['category'] = $product['category'];
            $data['price'] = $product['price'];
            $data['product_similarity'] = $product['similarity']; // Track confidence
        }

        // Update quantity
        if (!empty($extractedData['quantity'])) {
            $data['quantity'] = $extractedData['quantity'];
        }

        // Update address
        if (!empty($extractedData['address'])) {
            $data['customer_address'] = $extractedData['address'];
        }

        // Calculate total price
        if (!empty($data['price']) && !empty($data['quantity'])) {
            $data['total_price'] = $data['price'] * $data['quantity'];
        }

        // Determine next step
        $validation = $this->validationService->validateOrderParameters($data);
        $nextStep = $validation['valid'] ? 'confirming' : $this->validationService->getNextStep($data);

        // Update draft
        $draft->update([
            'data' => $data,
            'step' => $nextStep,
            'expires_at' => now()->addHours(24), // Extend expiry
        ]);

        Log::debug('Draft updated', [
            'draft_id' => $draft->id,
            'step' => $nextStep,
            'data' => $data,
        ]);

        return [
            'draft' => $draft,
            'validation' => $validation,
            'next_step' => $nextStep,
        ];
    }

    /**
     * Complete draft: convert to actual order
     * IMPORTANT: Uses transaction & eager loading to prevent N+1
     */
    public function completeDraft(OrderDraft $draft)
    {
        // Validate dulu
        $validation = $this->validationService->validateOrderParameters($draft->data);

        if (!$validation['valid']) {
            throw new \Exception('Order tidak lengkap: ' . implode(', ', $validation['missing']));
        }

        // Eager load relationships untuk menghindari N+1
        $draft->load(['customer']);

        try {
            $order = \DB::transaction(function () use ($draft) {
                // Update customer data
                $draft->customer->update([
                    'name' => $draft->data['customer_name'] ?? $draft->customer->name,
                    'address' => $draft->data['customer_address'] ?? $draft->customer->address,
                ]);

                // Create order
                $order = \App\Models\Order::create([
                    'customer_id' => $draft->customer->id,
                    'total_price' => $draft->data['total_price'] ?? 0,
                    'status' => 'pending',
                    'notes' => $draft->data['raw_message'] ?? null,
                    'payment_method' => $draft->data['payment_method'] ?? 'cod',
                    'payment_status' => ($draft->data['payment_method'] ?? 'cod') === 'cod' ? 'paid' : 'pending',
                ]);

                // Create order item
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $draft->data['product_id'],
                    'quantity' => $draft->data['quantity'],
                    'price' => $draft->data['price'],
                    'subtotal' => $draft->data['total_price'],
                ]);

                // Mark draft as completed
                $draft->delete();

                return $order;
            });

            Log::info('Draft completed as order', [
                'draft_id' => $draft->id,
                'order_id' => $order->id,
                'customer_id' => $draft->customer->id,
            ]);

            return $order;
        } catch (\Exception $e) {
            Log::error('Failed to complete draft', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Discard draft
     */
    public function discardDraft(OrderDraft $draft): void
    {
        $draft->delete();

        Log::info('Draft discarded', ['draft_id' => $draft->id]);
    }

    /**
     * Get draft summary untuk display ke user
     */
    public function getDraftSummary(OrderDraft $draft): string
    {
        return $this->validationService->formatOrderSummary($draft->data);
    }

    /**
     * Get empty draft template untuk customer baru
     */
    private function getEmptyDraftData(Customer $customer): array
    {
        return [
            'customer_id' => $customer->id,
            'customer_phone' => $customer->phone,
            'customer_name' => $customer->name,
            'customer_address' => $customer->address,
            'product_id' => null,
            'product_name' => null,
            'quantity' => null,
            'price' => null,
            'total_price' => null,
            'category' => null,
            'raw_message' => null,
            'product_similarity' => null,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clean up expired drafts (bisa di-cron job)
     */
    public function cleanupExpiredDrafts(): int
    {
        $deleted = OrderDraft::where('expires_at', '<', now())
            ->where('expires_at', '!=', null)
            ->delete();

        Log::info('Expired drafts cleaned up', ['count' => $deleted]);

        return $deleted;
    }

    /**
     * Get customer's active draft
     * IMPORTANT: Eager loading untuk performance
     */
    public function getCustomerActiveDraft(Customer $customer): ?OrderDraft
    {
        return OrderDraft::where('customer_id', $customer->id)
            ->with(['customer']) // Eager load
            ->where('expires_at', '>', now())
            ->orWhereNull('expires_at')
            ->latest()
            ->first();
    }
}
