<?php

namespace App\Services;

use App\Models\FuzzyRule;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class FuzzyBotService
{
    /**
     * Process incoming message with context awareness
     * Anti N+1: Uses eager loading and caching
     */
    public function processMessageWithContext(string $message, ?string $currentContext = null): array
    {
        
        $normalized = $this->normalizeText($message); 
    // Try to find matching rule (uses static method from FuzzyRule)
        $rule = FuzzyRule::findMatchingRule($normalized, $currentContext);

        if (!$rule) {
            return [
                'matched' => false,
                'rule_id' => null,
                'intent' => null,
                'confidence' => 0,
                'response' => null,
                'action' => null,
                'next_context' => null,
            ];
        }

        $confidence = FuzzyRule::calculateSimilarity($message, $rule->pattern);
        $response = $this->generateResponse($rule, $message);

        return [
            'matched' => true,
            'rule_id' => $rule->id,
            'intent' => $rule->intent,
            'action' => $rule->action,
            'confidence' => $confidence,
            'response' => $response,
            'next_context' => $rule->next_context,
            'context_slug' => $rule->context_slug,
        ];
    }

    /**
     * Calculate fuzzy match confidence score
     * Uses keyword matching and pattern matching
     */
    private function calculateConfidence(string $message, FuzzyRule $rule): float
    {
        $patterns = $this->parsePatterns($rule->pattern);
        $scores = [];

        // Keyword matching
        foreach ($patterns['keywords'] as $keyword) {
            $keyword = $this->normalizeText($keyword);

            if (stripos($message, $keyword) !== false) {
                $scores[] = 1.0; // Exact match
            } else {
                // Calculate similarity using levenshtein for fuzzy matching
                $similarity = $this->calculateSimilarity($message, $keyword);
                if ($similarity > 0.6) {
                    $scores[] = $similarity;
                }
            }
        }

        // Regex matching
        foreach ($patterns['regex'] as $regex) {
            try {
                if (preg_match($regex, $message)) {
                    $scores[] = 0.95; // Regex match is very confident
                }
            } catch (\Exception $e) {
                Log::warning('Invalid regex pattern', ['pattern' => $regex, 'error' => $e->getMessage()]);
            }
        }

        if (empty($scores)) {
            return 0;
        }

        // Return average confidence score
        return array_sum($scores) / count($scores);
    }

    /**
     * Parse pattern string into keywords and regex patterns
     * Format: "keyword1|keyword2|/regex1/i|/regex2/"
     */
    private function parsePatterns(string $patternString): array
    {
        $keywords = [];
        $regex = [];

        $parts = array_map('trim', explode('|', $patternString));

        foreach ($parts as $part) {
            if (! empty($part)) {
                // Check if it's a regex (surrounded by / /)
                if (preg_match('~^/(.+)/([imsxADSUXJu]*)$~', $part, $matches)) {
                    $regex[] = '~'.$matches[1].'~'.($matches[2] ?? '');
                } else {
                    $keywords[] = $part;
                }
            }
        }

        return ['keywords' => $keywords, 'regex' => $regex];
    }

    /**
     * Calculate string similarity using levenshtein distance
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $maxLen = max($len1, $len2);

        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);

        return 1 - ($distance / $maxLen);
    }

    /**
     * Normalize text for comparison
     */
    private function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        // Remove special characters but keep space
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);

        return $text;
    }

    /**
     * Generate response from template
     * Supports simple variable substitution: {variable_name}
     * Special handling for dynamic catalog generation
     */
    private function generateResponse(FuzzyRule $rule, string $message): ?string
    {
        // Special handling for dynamic catalog
        if ($rule->action === 'show_product') {
            return $this->generateCatalogResponse();
        }

        if (empty($rule->response_template)) {
            return null;
        }

        $response = $rule->response_template;

        // Variable substitution
        $variables = [
            '{message}' => $message,
            '{intent}' => $rule->intent,
            '{action}' => $rule->action,
            '{timestamp}' => now()->format('H:i'),
            '{date}' => now()->format('Y-m-d'),
        ];

        foreach ($variables as $placeholder => $value) {
            $response = str_replace($placeholder, $value, $response);
        }

        return $response;
    }

    /**
     * Generate dynamic catalog response from database
     */
    private function generateCatalogResponse(): string
    {
        try {
            $categories = Category::whereHas('products', function ($query) {
                    $query->where('is_active', true);
                })
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            if (empty($categories)) {
                return 'Maaf ka, katalog produk sedang tidak tersedia. Silakan coba lagi nanti.';
            }

            $categoryList = implode(', ', $categories);

            return "Ini katalog produk terbaik kami ka 🌸. Ada {$categoryList}. Kakak tertarik yang mana?";
        } catch (\Exception $e) {
            Log::error('Error generating catalog response', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 'Ini katalog produk terbaik kami ka 🌸. Ada Buket Mawar, Snack Bouquet, Hampers, dan Bouquet Wisuda. Kakak tertarik yang mana?';
        }
    }

    /**
     * Get all active rules grouped by intent
     */
    public function getAllRules()
    {
        return FuzzyRule::where('is_active', true)
            ->orderBy('intent')
            ->get()
            ->groupBy('intent');
    }

    /**
     * Get rules by intent
     */
    public function getRulesByIntent(string $intent)
    {
        return FuzzyRule::where('is_active', true)
            ->where('intent', $intent)
            ->get();
    }

    /**
     * Process message for order collection flow
     * Handles parameter extraction dan validation untuk multistep ordering
     * IMPORTANT: Uses eager loading & dependency injection untuk performance
     *
     * @param string $message
     * @param \App\Models\Customer $customer
     * @return array Order flow result
     */
    public function processOrderCollection(
        string $message,
        \App\Models\Customer $customer
    ): array {
        $paramExtractor = new ParameterExtractionService();
        $draftService = new OrderDraftService(new ParameterValidationService());
        $validationService = new ParameterValidationService();

        try {
            // Get or create draft dengan eager loading
            $draft = $draftService->getOrCreateDraft($customer);

            // Extract parameters dari message
            $extracted = $paramExtractor->extractParameters($message, $draft->data);

            // Update draft dengan extracted data
            $result = $draftService->updateDraftWithExtraction($draft, $extracted);
            $draft = $result['draft'];
            $validation = $result['validation'];
            $nextStep = $result['next_step'];

            // Generate response berdasarkan validation result
            if ($validation['valid']) {
                // Semua parameter terkumpul - siap untuk konfirmasi
                $response = $this->generateConfirmationMessage($draft->data);
                $action = 'confirm_order';

                return [
                    'matched' => true,
                    'intent' => 'order_collection_complete',
                    'action' => $action,
                    'response' => $response,
                    'next_context' => 'confirming',
                    'draft_id' => $draft->id,
                    'step' => $nextStep,
                    'data' => $draft->data,
                ];
            } else {
                // Ada parameter yang masih hilang - minta input
                $question = $validationService->generateFollowUpQuestion(
                    $validation['missing'],
                    $draft->data
                );

                return [
                    'matched' => true,
                    'intent' => 'order_collection_pending',
                    'action' => 'ask_parameter',
                    'response' => $question,
                    'next_context' => $nextStep,
                    'draft_id' => $draft->id,
                    'step' => $nextStep,
                    'missing' => $validation['missing'],
                    'data' => $draft->data,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error in processOrderCollection', [
                'message' => $message,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'matched' => false,
                'intent' => 'error',
                'action' => 'escalate',
                'response' => 'Maaf, terjadi kesalahan saat memproses pesanan Anda. Admin akan segera membantu.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate confirmation message dengan order summary
     */
    private function generateConfirmationMessage(array $draftData): string
    {
        $confirmationTemplate = <<<'MSG'
Baik ka, pesanan Anda sudah lengkap. Berikut ringkasannya:

📋 *RINGKASAN PESANAN*
Nama: {customer_name}
Produk: {product_name}
Jumlah: {quantity} biji
Harga: Rp {total_price}
Alamat: {customer_address}

Lanjutkan? Ketik "iya" atau "ya" untuk konfirmasi, atau "ubah" jika ingin mengubah data.
MSG;

        // Replace variables
        $message = $confirmationTemplate;
        foreach ($draftData as $key => $value) {
            $placeholder = '{' . $key . '}';
            $formatted = $value;

            // Format currency
            if ($key === 'total_price' && is_numeric($value)) {
                $formatted = number_format($value, 0, ',', '.');
            }

            $message = str_replace($placeholder, $formatted, $message);
        }

        return $message;
    }

    /**
     * Process message untuk confirm order
     */
    public function processOrderConfirmation(
        string $message,
        \App\Models\Customer $customer
    ): array {
        $draftService = new OrderDraftService(new ParameterValidationService());

        $message = strtolower(trim($message));

        // Check jika user konfirmasi
        if (in_array($message, ['iya', 'ya', 'ok', 'setuju', 'confirm', 'lanjut'])) {
            try {
                $draft = $draftService->getCustomerActiveDraft($customer);

                if (!$draft) {
                    return [
                        'matched' => false,
                        'response' => 'Maaf, pesanan tidak ditemukan. Mulai dari awal ya.',
                    ];
                }

                // Complete the draft -> buat order
                $order = $draftService->completeDraft($draft);

                return [
                    'matched' => true,
                    'intent' => 'order_confirmed',
                    'action' => 'order_created',
                    'response' => "Terima kasih! 🙏 Pesanan Anda telah kami terima dengan nomor #{$order->id}. Admin akan segera memproses.",
                    'order_id' => $order->id,
                    'next_context' => 'order_completed',
                ];
            } catch (\Exception $e) {
                Log::error('Error confirming order', ['error' => $e->getMessage()]);

                return [
                    'matched' => false,
                    'response' => 'Maaf, terjadi kesalahan saat membuat pesanan. Admin akan segera membantu.',
                ];
            }
        } elseif (in_array($message, ['ubah', 'ganti', 'batal', 'tidak', 'tidak jadi'])) {
            // User ingin ubah data
            return [
                'matched' => true,
                'intent' => 'order_cancel',
                'action' => 'restart_collection',
                'response' => 'Baik, mari kita mulai ulang. Siapa nama Anda?',
                'next_context' => 'collecting_name',
            ];
        }

        return [
            'matched' => false,
            'response' => 'Maaf, saya tidak mengerti. Ketik "iya" untuk lanjut atau "ubah" untuk mengubah data.',
        ];
    }
}
