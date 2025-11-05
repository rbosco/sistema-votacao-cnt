<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:8088'),
        'http://localhost:8088',
        'https://conferencianacional.trabalho.gov.br',
        'https://conferencianacional-hml.trabalho.gov.br',
    ],

    'allowed_origins_patterns' => [
        '/^https?:\/\/.*\.trabalho\.gov\.br$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition', 'Content-Length', 'Content-Type'],

    'max_age' => 0,

    'supports_credentials' => true,

];
