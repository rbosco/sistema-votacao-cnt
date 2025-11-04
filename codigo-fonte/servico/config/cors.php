<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:8088', 'http://localhost:8090','https://conferencianacional-hml.trabalho.gov.br','https://conferencianacional.trabalho.gov.br'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition', 'Content-Length', 'Content-Type'],

    'max_age' => 600,

    'supports_credentials' => true,

];
