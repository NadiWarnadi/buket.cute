<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyRule extends Model
{
    protected $table = 'fuzzy_rules';

    protected $fillable = [
        'intent',
        'pattern',
        'confidence_threshold',
        'action',
        'response_template',
        'context_slug',
        'next_context',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'confidence_threshold' => 'float',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Find matching rule by message and context
     * 
     * @param string $message User message text
     * @param string|null $currentContext Current conversation context
     * @return FuzzyRule|null
     */
    public static function findMatchingRule(string $message, ?string $currentContext = null)
    {
        $message = strtolower(trim($message));
        
        // First, get rules for current context if exists
        $query = static::where('is_active', true);
        
        if ($currentContext) {
            $query->where('context_slug', $currentContext);
        }

        $rules = $query->orderBy('priority', 'desc')->get();

        if ($rules->isEmpty()) {
            return null;
        }

        // Calculate similarity for each rule and find best match
        $bestMatch = null;
        $bestScore = 0;

        foreach ($rules as $rule) {
            $similarity = self::calculateSimilarity($message, $rule->pattern);

            if ($similarity >= $rule->confidence_threshold && $similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $rule;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate similarity between message and pattern
     * Using multiple fuzzy matching techniques
     * 
     * @param string $message User message
     * @param string $patterns Pattern keywords (comma-separated)
     * @return float 0-1 similarity score
     */
    public static function calculateSimilarity(string $message, string $patterns): float
    {
        $message = strtolower(trim($message));
        $patternArray = array_map('trim', explode(',', $patterns));

        $maxScore = 0;

        foreach ($patternArray as $pattern) {
            $pattern = strtolower(trim($pattern));
            
            if (empty($pattern)) {
                continue;
            }

            // 1. Exact match (100% score)
            if ($message === $pattern) {
                return 1.0;
            }

            // 2. Substring match (80% score)
            if (strpos($message, $pattern) !== false || strpos($pattern, $message) !== false) {
                $maxScore = max($maxScore, 0.8);
                continue;
            }

            // 3. Similar text using PHP built-in
            similar_text($message, $pattern, $percent);
            $percent = $percent / 100;

            // 4. Levenschtein distance (for typos)
            if (function_exists('levenshtein')) {
                $messageLen = strlen($message);
                $patternLen = strlen($pattern);
                $maxLen = max($messageLen, $patternLen);
                
                if ($maxLen > 0) {
                    $lev = levenshtein($message, $pattern);
                    $levScore = 1 - ($lev / $maxLen);
                    
                    // Weight Levenshtein score if close
                    if ($levScore > 0.65) {
                        $percent = max($percent, $levScore * 0.95);
                    }
                }
            }

            $maxScore = max($maxScore, $percent);

            // Stop early if we have high confidence
            if ($maxScore >= 0.95) {
                break;
            }
        }

        return round($maxScore, 3);
    }

    /**
     * Get all contexts for dropdown
     * 
     * @return array
     */
    public static function getAllContexts(): array
    {
        return static::query()
            ->whereNotNull('context_slug')
            ->distinct('context_slug')
            ->pluck('context_slug')
            ->sort()
            ->values()
            ->toArray();
    }
}
