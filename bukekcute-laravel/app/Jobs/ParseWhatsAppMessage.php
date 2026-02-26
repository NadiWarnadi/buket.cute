<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;
    public $tries = 3;
    public $timeout = 30;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        $message = $this->message;
        
        // Skip if already parsed
        if ($message->parsed) {
            return;
        }

        try {
            // Only parse text messages
            if ($message->type !== Message::TYPE_TEXT) {
                $message->markAsParsed();
                return;
            }

            // Extract order intent
            $intent = $this->analyzeMessage($message->body);

            if (!$intent) {
                // Could not parse - optionally send clarification message
                $this->sendClarificationMessage($message);
                $message->markAsParsed();
                return;
            }

            // Create order from parsed intent
            if ($intent['action'] === 'order') {
                $order = $this->createOrderFromIntent($message->customer, $intent);
                $message->markAsParsed($order->id);

                // Send confirmation to customer
                NotificationService::notifyOrderCreated($order);
                
                // Notify admin
                NotificationService::notifyAdminNewOrder($order);

                return;
            }
        } catch (\Exception $e) {
            \Log::error("Parse message error: " . $e->getMessage(), [
                'message_id' => $this->message->id,
            ]);
            throw $e;
        }
    }

    /**
     * Analyze message and extract intent
     * Returns array with action, products, quantity, address, etc
     * 
     * Format: "Halo saya ingin memesan [NamaProduk] sebanyak [jumlah] untuk alamat [alamat lengkap]"
     */
    private function analyzeMessage($text)
    {
        $text = strtolower(trim($text));

        // First, try structured format parsing
        $parsedStructured = $this->parseStructuredFormat($text);
        if ($parsedStructured) {
            return $parsedStructured;
        }

        // Fallback to simple keyword matching
        if ($this->containsOrderKeyword($text)) {
            $qty = $this->extractQuantity($text);
            $productName = $this->extractProductName($text);
            $address = $this->extractAddress($text);

            if ($productName && $qty > 0) {
                return [
                    'action' => 'order',
                    'product_name' => $productName,
                    'quantity' => $qty,
                    'address' => $address,
                    'text' => $text,
                ];
            }
        }

        // Check for inquiry
        if ($this->containsInquiryKeyword($text)) {
            return [
                'action' => 'inquiry',
                'text' => $text,
            ];
        }

        // Could not determine
        return null;
    }

    /**
     * Parse structured format:
     * "Halo saya ingin memesan [produk] sebanyak [qty] untuk alamat [address]"
     */
    private function parseStructuredFormat($text)
    {
        // Pattern: memesan/pesan ... sebanyak [qty] untuk alamat
        $pattern = '/(?:memesan|pesan)\s+(.+?)\s+sebanyak\s+(\d+)\s+(?:untuk\s+)?(?:alamat|di)?\s*(.+)$/i';
        
        if (preg_match($pattern, $text, $matches)) {
            return [
                'action' => 'order',
                'product_name' => trim($matches[1]),
                'quantity' => (int) $matches[2],
                'address' => trim($matches[3]),
                'text' => $text,
            ];
        }

        // Alternative pattern without address
        $pattern2 = '/(?:memesan|pesan)\s+(.+?)\s+sebanyak\s+(\d+)/i';
        if (preg_match($pattern2, $text, $matches)) {
            return [
                'action' => 'order',
                'product_name' => trim($matches[1]),
                'quantity' => (int) $matches[2],
                'address' => null,
                'text' => $text,
            ];
        }

        return null;
    }

    /**
     * Check if message contains order keywords
     */
    private function containsOrderKeyword($text)
    {
        $keywords = ['pesan', 'pesanan', 'order', 'mau', 'ingin', 'beli', 'bucket'];
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message contains inquiry keywords
     */
    private function containsInquiryKeyword($text)
    {
        $keywords = ['harga', 'berapa', 'harga berapa', 'biaya', 'ready', 'ada'];
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract quantity from message
     */
    private function extractQuantity($text)
    {
        // Look for number patterns: "2 bucket", "3x bucket", etc
        preg_match('/(\d+)[\s\-x]*(bucket|buket|pesanan)/i', $text, $matches);
        
        if (isset($matches[1])) {
            $qty = (int) $matches[1];
            return min($qty, 10); // Max 10 per order from message
        }

        // If no pattern found, default to 1
        return 1;
    }

    /**
     * Extract product name from message
     */
    private function extractProductName($text)
    {
        // Remove order keywords
        $cleaned = preg_replace('/\b(pesan|pesanan|order|mau|ingin|beli|bucket|buket)\b/i', '', $text);
        $cleaned = preg_replace('/\d+[\s\-x]*/i', '', $cleaned);
        $cleaned = trim($cleaned);

        // Match against product names in database
        $products = Product::where('is_active', true)->pluck('name')->toArray();

        foreach ($products as $product) {
            if (stripos($cleaned, $product) !== false) {
                return $product;
            }
        }

        // If can't match, return first meaningful word (heuristic)
        $words = explode(' ', $cleaned);
        foreach ($words as $word) {
            if (strlen($word) > 2) {
                return $word;
            }
        }

        return null;
    }

    /**
     * Create order from parsed intent
     */
    private function createOrderFromIntent($customer, $intent)
    {
        // Find product by name (case-insensitive, fuzzy)
        $product = Product::where('is_active', true)
            ->where(function ($query) use ($intent) {
                $cleanName = strtolower(str_replace(['bucket', 'buket'], '', $intent['product_name']));
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . $cleanName . '%']);
            })
            ->first();

        if (!$product) {
            // Try exact match
            $product = Product::where('is_active', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($intent['product_name']) . '%'])
                ->first();
        }

        if (!$product) {
            // Fallback ke produk pertama
            $product = Product::where('is_active', true)->first();
        }

        if (!$product) {
            throw new \Exception('Produk tidak ditemukan');
        }

        // Update customer address jika ada
        if (!empty($intent['address'])) {
            $customer->update(['address' => $intent['address']]);
        }

        // Create order
        $order = Order::create([
            'customer_id' => $customer->id,
            'total_price' => 0,
            'status' => Order::STATUS_PENDING,
            'notes' => 'Dari WhatsApp - Pesan: ' . $intent['text'],
        ]);

        // Create order item
        $subtotal = $product->price * $intent['quantity'];
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $intent['quantity'],
            'price' => $product->price,
            'subtotal' => $subtotal,
        ]);

        // Update total
        $order->update(['total_price' => $subtotal]);

        return $order;
    }

    /**
     * Extract address from message
     */
    private function extractAddress($text)
    {
        // Pattern: untuk alamat [address]
        if (preg_match('/untuk\s+(?:alamat|di)\s+(.+)$/i', $text, $matches)) {
            return trim($matches[1]);
        }

        // Pattern: alamat [address]
        if (preg_match('/alamat\s+(.+)$/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Send clarification message
     */
    private function sendClarificationMessage($message)
    {
        // Create clarification request message
        // Will be replaced by NotificationService when integrated properly
        \Log::info("Clarification needed for message: {$message->id}");
    }

    /**
     * Handle failed job
     */
    public function failed(\Throwable $exception)
    {
        \Log::error("ParseWhatsAppMessage failed for message {$this->message->id}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
