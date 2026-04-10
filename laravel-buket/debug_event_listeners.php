<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$events = $app->make('events');
$listeners = $events->getListeners(App\Events\WhatsAppMessageReceived::class);
echo "Listener count: " . count($listeners) . "\n";
foreach ($listeners as $i => $listener) {
    echo "Listener #{$i}: ";
    if (is_array($listener)) {
        var_dump($listener);
    } elseif (is_object($listener)) {
        echo get_class($listener) . "\n";
    } else {
        var_dump($listener);
    }
}
