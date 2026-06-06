<?php return array (
  'App\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\WhatsAppMessageReceived' => 
    array (
      0 => 'App\\Listeners\\ProcessMessageWithFuzzyBot',
    ),
  ),
  'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider' => 
  array (
    'App\\Events\\WhatsAppMessageReceived' => 
    array (
      0 => 'App\\Listeners\\ProcessMessageWithFuzzyBot@handle',
    ),
  ),
);