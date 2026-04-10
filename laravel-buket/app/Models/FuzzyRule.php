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
        
        // ini untuk mengambil di data base semua rule yang aktif dan sesuai dengan context saat ini (jika ada)
        $query = static::where('is_active', true);
        
        $query->where(function($q) use ($currentContext) {
            $q->where('context_slug', $currentContext);
            if ($currentContext !== null) {
                $q->orWhereNull('context_slug');
            }
        });
        // ini cada ngan 
        // if ($currentContext) {
        //     $query->where('context_slug', $currentContext);
        // }

        $rules = $query->orderBy('priority', 'desc')->get();

        if ($rules->isEmpty()) {
            return null;
        }

        $bestMatch = null;
        $highestScore = 0;

        // menghitung similarity untuk setiap rule dan mencari yang paling cocok
        foreach ($rules as $rule) {
            $similarity = self::calculateSimilarity($message, $rule->pattern);

      if ($similarity >= $rule->confidence_threshold && $similarity > $highestScore) {
            $highestScore = $similarity;
            $rule->temp_confidence = $similarity;
            $bestMatch = $rule;
            }
             if ($highestScore >= 0.99) {
            break;
        }
    }
        

        return  $bestMatch;
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
        $patternArray = array_map('trim', explode('|', $patterns));

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
              if (str_contains($message, $pattern)) {
                $maxScore = max($maxScore, 0.95);
                continue;
            }

            // 3. Similar text using PHP built-in
            similar_text($message, $pattern, $percent);
            $simScore = $percent / 100;

            //parameteer typo
            $levScore = 0;
            $maxLen = max(strlen($message), strlen($pattern));
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
            $currentPatternScore = max($simScore, $levScore);
            $maxScore = max($maxScore, $currentPatternScore);

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
