<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Checking Customers Table Structure ===\n";

try {
    $columns = \DB::select('DESCRIBE customers');
    foreach ($columns as $column) {
        echo "{$column->Field}: {$column->Type} " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}