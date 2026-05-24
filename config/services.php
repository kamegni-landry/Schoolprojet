<?php

return [
    'africas_talking' => [
        'api_key' => env('AFRICAS_TALKING_API_KEY', ''),
        'username' => env('AFRICAS_TALKING_USERNAME', 'sandbox'),
        'base_url' => env('AFRICAS_TALKING_ENV', 'sandbox') === 'sandbox'
            ? 'https://api.sandbox.africastalking.com'
            : 'https://api.africastalking.com',
    ],
];
