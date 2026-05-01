<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\OrderDraft;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderDraftService
{
    protected ParameterValidationService $validationService;

    /**
     * Constructor dengan Dependency Injection untuk Validation Service
     */
    public function __construct(ParameterValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Mengambil draft aktif atau membuat draft baru jika tidak ada.
     */
    public function getOrCreateDraft(Customer $customer): OrderDraft
    {
        $draft = $this->getCustomerActiveDraft($customer);

        if ($draft) {
            return $draft;
        }

        // Buat draft baru jika tidak ditemukan yang aktif
        return OrderDraft::create([
            'customer_id' => $customer->id,
            'data' => $this->getEmptyDraftData($customer),
            'step' => 'collecting_name',
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Mencari draft aktif milik customer.
     * Menggunakan query grouping agar 'orWhereNull' tidak mengambil data milik customer lain.
     */
    public function getCustomerActiveDraft(Customer $customer): ?OrderDraft
    {
        return OrderDraft::where('customer_id', $customer->id)
            ->with(['customer']) // Eager loading untuk performa
            ->where(function ($query) {
                $query->where('expires_at', '>', now())
                      ->orWhereNull('expires_at');
            })
            ->latest()
            ->first();
    }

    /**
     * Update data draft berdasarkan hasil ekstraksi AI/Input.
     */
    public function updateDraftWithExtraction(OrderDraft $draft, array $extractedData): array
    {
        $data = $draft->data ?? [];

        // Update Nama
        if (!empty($extractedData['customer_name'])) {
            $data['customer_name'] = $extractedData['customer_name'];
        }

        // Update Produk
        if (!empty($extractedData['product_data'])) {
            $product = $extractedData['product_data'];
            $data['product_id'] = $product['product_id'];
            $data['product_name'] = $product['product_name'];
            $data['category'] = $product['category'];
            $data['price'] = $product['price'];
            $data['product_similarity'] = $product['similarity'];
        }

        // Update Jumlah & Alamat
        if (!empty($extractedData['quantity'])) $data['quantity'] = $extractedData['quantity'];
        if (!empty($extractedData['address'])) $data['customer_address'] = $extractedData['address'];

        // Kalkulasi Total Harga
        if (!empty($data['price']) && !empty($data['quantity'])) {
            $data['total_price'] = $data['price'] * $data['quantity'];
        }

        // Validasi kelengkapan data
        $validation = $this->validationService->validateOrderParameters($data);
        $nextStep = $validation['valid'] ? 'confirming' : $this->validationService->getNextStep($data);

        // Simpan update ke database
        $draft->update([
            'data' => $data,
            'step' => $nextStep,
            'expires_at' => now()->addHours(24), // Extend masa berlaku tiap ada interaksi
        ]);

        return [
            'draft' => $draft,
            'validation' => $validation,
            'next_step' => $nextStep,
        ];
    }

    /**
     * Konversi draft menjadi Order asli (Checkout).
     * Menggunakan Database Transaction untuk keamanan data.
     */
    public function completeDraft(OrderDraft $draft)
    {
        // Pastikan data valid sebelum diconvert
        $validation = $this->validationService->validateOrderParameters($draft->data);
        if (!$validation['valid']) {
            throw new \Exception('Order tidak lengkap: ' . implode(', ', $validation['missing']));
        }

        try {
            return DB::transaction(function () use ($draft) {
                // 1. Update data customer terbaru
                $draft->customer->update([
                    'name' => $draft->data['customer_name'] ?? $draft->customer->name,
                    'address' => $draft->data['customer_address'] ?? $draft->customer->address,
                ]);

                // 2. Buat Order
                $order = Order::create([
                    'customer_id' => $draft->customer_id,
                    'total_price' => $draft->data['total_price'] ?? 0,
                    'status' => 'pending',
                    'notes' => $draft->data['raw_message'] ?? null,
                ]);

                // 3. Buat Item Order
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $draft->data['product_id'],
                    'quantity' => $draft->data['quantity'],
                    'price' => $draft->data['price'],
                    'subtotal' => $draft->data['total_price'],
                ]);

                // 4. Hapus draft karena sudah jadi order
                $draft->delete();

                return $order;
            });
        } catch (\Exception $e) {
            Log::error('Gagal menyelesaikan draft: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Hapus draft jika user membatalkan pesanan.
     */
    public function discardDraft(OrderDraft $draft): void
    {
        $draft->delete();
        Log::info("Draft #{$draft->id} dibatalkan oleh user.");
    }

    /**
     * Mendapatkan ringkasan teks untuk dikirim ke user (WhatsApp).
     */
    public function getDraftSummary(OrderDraft $draft): string
    {
        return $this->validationService->formatOrderSummary($draft->data);
    }

    /**
     * Template data awal untuk draft baru.
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
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Membersihkan draft yang sudah expired (bisa dipanggil via Scheduler).
     */
    public function cleanupExpiredDrafts(): int
    {
        $deleted = OrderDraft::where('expires_at', '<', now())
            ->whereNotNull('expires_at')
            ->delete();

        return $deleted;
    }
}
