<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = \Illuminate\Http\Request::capture());

// Get all customers
$customers = \DB::table('customers')->select('id', 'name', 'phone')->get();

echo "=== CUSTOMERS IN DATABASE ===\n";
foreach($customers as $c) {
    echo "ID: {$c->id} | Name: {$c->name} | Phone: {$c->phone}\n";
}

echo "\n=== MESSAGES IN DATABASE ===\n";
$messages = \DB::table('messages')->select('id', 'customer_id', 'from', 'body')->orderBy('created_at', 'desc')->limit(10)->get();

foreach($messages as $m) {
    echo "ID: {$m->id} | Customer: {$m->customer_id} | From: {$m->from} | Body: " . substr($m->body, 0, 30) . "...\n";
}

echo "\n=== TEST: NORMALIZE & MATCH ===\n";
echo "Test phone: 6283824665074\n";
echo "Normalized: " . \App\Models\Customer::normalizePhone('6283824665074') . "\n";
echo "Match in DB? " . (\DB::table('customers')->where('phone', \App\Models\Customer::normalizePhone('6283824665074'))->count() ? 'YES' : 'NO') . "\n";
