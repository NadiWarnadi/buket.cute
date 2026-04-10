<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use App\Models\FuzzyRule;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Fuzzy Rules for 'Halo' ===\n\n";

try {
    // Check greeting rules
    $greetingRules = FuzzyRule::where('is_active', true)
        ->where('intent', 'greeting')
        ->get();

    echo "Greeting Rules Found: {$greetingRules->count()}\n";
    foreach ($greetingRules as $rule) {
        echo "  - ID: {$rule->id}, Pattern: '{$rule->pattern}', Threshold: {$rule->confidence_threshold}\n";
    }
    echo "\n";

    // Test message
    $message = "Halo";
    echo "Testing message: '{$message}'\n\n";

    // Test similarity calculation
    foreach ($greetingRules as $rule) {
        $similarity = FuzzyRule::calculateSimilarity($message, $rule->pattern);
        echo "Rule ID {$rule->id} similarity: {$similarity}\n";
        echo "  Pattern: '{$rule->pattern}'\n";
        echo "  Threshold: {$rule->confidence_threshold}\n";
        echo "  Match: " . ($similarity >= $rule->confidence_threshold ? 'YES' : 'NO') . "\n\n";
    }

    // Test findMatchingRule
    echo "Testing findMatchingRule...\n";
    $matchedRule = FuzzyRule::findMatchingRule($message, null);
    if ($matchedRule) {
        echo "✅ MATCHED: Rule ID {$matchedRule->id}, Intent: {$matchedRule->intent}\n";
    } else {
        echo "❌ NO MATCH FOUND\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}