<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173', // ← Vite
        'http://127.0.0.1:5173', // ← Optional kalau pakai IP
        'http://localhost:8000', // ← Kalau frontend & backend gabung
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // ✅ WAJIB TRUE jika pakai cookie
];

