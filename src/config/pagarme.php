<?php
/**
 * Define environment to working with pagarMe
 *
 * Dashboard https://dashboard.pagar.me/
 * Docs https://docs.pagar.me/
 */

return [

    'environment' => 'local',

    'key' => [

        'local' => [
            'api_key' => env('API_KEY_PAGARME_SANDBOX'),
            'crypto_key' => env('CRYPTO_KEY_PAGARME_SANDBOX'),
        ],

        'production' => [
            'api_key' => env('API_KEY_PAGARME'),
            'crypto_key' => env('CRYPTO_KEY_PAGARME'),
        ]

    ]

];
