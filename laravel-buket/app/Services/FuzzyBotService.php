<?php

namespace App\Services;

use App\Models\FuzzyRule;
use Illuminate\Support\Facades\Log;

class FuzzyBotService
{
    /**
     * Process incoming message and find best matching rule
     * Using fuzzy matching with confidence scores
     */
    public function processMessage(string $message): array
    {
        // Normalize message
        $normalizedMessage = $this->normalizeText($message);

        // Get all active rules
        $rules = FuzzyRule::where('is_active', true)->get();

        if ($rules->isEmpty()) {
            return [
                'matched' => false,
                'rule_id' => null,
                'intent' => null,
                'confidence' => 0,
                'response' => null,
            ];
        }

        $matches = [];

        // Calculate confidence score for each rule
        foreach ($rules as $rule) {
            $confidence = $this->calculateConfidence($normalizedMessage, $rule);

            if ($confidence >= $rule->confidence_threshold) {
                $matches[] = [
                    'rule' => $rule,
                    'confidence' => $confidence,
                ];
            }
        }

        if (empty($matches)) {
            return [
                'matched' => false,
                'rule_id' => null,
                'intent' => null,
                'confidence' => 0,
                'response' => null,
            ];
        }

        // Sort by confidence descending and get the best match
        usort($matches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
        $bestMatch = $matches[0];
        $rule = $bestMatch['rule'];

        // Generate response
        $response = $this->generateResponse($rule, $normalizedMessage);

        return [
            'matched' => true,
            'rule_id' => $rule->id,
            'intent' => $rule->intent,
            'action' => $rule->action,
            'confidence' => round($bestMatch['confidence'], 2),
            'response' => $response,
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
     */
    private function generateResponse(FuzzyRule $rule, string $message): ?string
    {
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
}
