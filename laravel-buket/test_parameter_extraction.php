<?php

require_once 'vendor/autoload.php';

use App\Services\ParameterExtractionService;
use App\Services\ParameterValidationService;
use App\Services\OrderDraftService;
use App\Services\FuzzyBotService;
use App\Models\Customer;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Testing Parameter Extraction System ===\n\n";

// Test message
$testMessage = "halo kak saya mau pesan buket merah bunga hitam 2 biji itu berapaan ka dan alamat nya di kota jakarta dst no23";

echo "Test Message: $testMessage\n\n";

// Create or get test customer
$customer = Customer::firstOrCreate([
    'phone' => '081234567890'
], [
    'name' => 'Test Customer',
    'current_context' => null
]);

echo "Customer ID: {$customer->id}\n";
echo "Current Context: {$customer->current_context}\n\n";

// Initialize services using Laravel container
$extractionService = app(ParameterExtractionService::class);
$validationService = app(ParameterValidationService::class);
$draftService = app(OrderDraftService::class);
$fuzzyBotService = app(FuzzyBotService::class);

// Test parameter extraction
echo "=== Parameter Extraction ===\n";
$extractedParams = $extractionService->extractParameters($testMessage);
print_r($extractedParams);

echo "\n=== Parameter Validation ===\n";
$validation = $validationService->validateOrderParameters($extractedParams);
echo "Is Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
echo "Missing Fields: " . implode(', ', $validation['missing']) . "\n";
if (!empty($validation['errors'])) {
    echo "Errors: " . implode(', ', $validation['errors']) . "\n";
}

echo "\n=== Order Draft Management ===\n";
// Test draft creation/update
$draft = $draftService->getOrCreateDraft($customer);
echo "Draft ID: {$draft->id}\n";
echo "Draft Data: {$draft->draft_data}\n";

// Update draft with extracted params
$updateResult = $draftService->updateDraftWithExtraction($draft, $extractedParams);
$updatedDraft = $updateResult['draft'];
echo "Updated Draft Data: " . json_encode($updatedDraft->data, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Fuzzy Bot Processing ===\n";
// Test fuzzy bot processing
$result = $fuzzyBotService->processOrderCollection($testMessage, $customer);
echo "Response: {$result['response']}\n";
if (isset($result['new_context'])) {
    echo "New Context: {$result['new_context']}\n";
}
echo "Result Keys: " . implode(', ', array_keys($result)) . "\n";

echo "\n=== Test Complete ===\n";