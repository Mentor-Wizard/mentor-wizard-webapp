<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Calendar Credential Encryption Keys
    |--------------------------------------------------------------------------
    |
    | These AES-256 keys encrypt OAuth credentials stored in the database
    | (access_token, refresh_token, client_id, client_secret).
    |
    | KEY1 is the current active key. New data is always encrypted with KEY1.
    | KEY2 is the previous key, used only for decrypting data during rotation.
    |
    | Keys must be base64-encoded 32-byte random strings, e.g.:
    |   php -r "echo base64_encode(random_bytes(32));"
    |
    */
    'encryption_key1' => env('CALENDAR_ENCRYPTION_KEY1'),
    'encryption_key2' => env('CALENDAR_ENCRYPTION_KEY_PREVIOUS'),

    /*
    |--------------------------------------------------------------------------
    | Microsoft Outlook — Multi-Tenant Azure App Credentials
    |--------------------------------------------------------------------------
    |
    | Registered as a multi-tenant app in Azure AD. All users authenticate
    | via this single app; no per-user Azure credentials are required.
    |
    */
    'microsoft_client_id'     => env('MICROSOFT_CLIENT_ID'),
    'microsoft_client_secret' => env('MICROSOFT_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Google Calendar — App-Level OAuth Credentials
    |--------------------------------------------------------------------------
    |
    | When set, users connect via this shared OAuth app (Google provider).
    | Leave empty to use the per-user credential flow (GooglePersonalApp).
    | Only one of the two Google options should be configured at a time.
    |
    */
    'google_client_id'     => env('GOOGLE_CALENDAR_CLIENT_ID'),
    'google_client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Integration Test Credentials
    |--------------------------------------------------------------------------
    |
    | Real provider credentials used only when running the Integration test
    | suite against live APIs. Set the corresponding env variables in
    | .env.testing (or export them before running) and never commit real
    | tokens to the repository.
    |
    */
    'testing' => [
        'outlook' => [
            'access_token'  => env('TEST_OUTLOOK_ACCESS_TOKEN'),
            'refresh_token' => env('TEST_OUTLOOK_REFRESH_TOKEN'),
            'calendar_id'   => env('TEST_OUTLOOK_CALENDAR_ID', 'me'),
        ],
        'google' => [
            'access_token'  => env('TEST_GOOGLE_ACCESS_TOKEN'),
            'refresh_token' => env('TEST_GOOGLE_REFRESH_TOKEN'),
            'client_id'     => env('TEST_GOOGLE_CLIENT_ID'),
            'client_secret' => env('TEST_GOOGLE_CLIENT_SECRET'),
            'calendar_id'   => env('TEST_GOOGLE_CALENDAR_ID', 'primary'),
        ],
        'apple' => [
            'id'           => env('TEST_APPLE_ID'),
            'app_password' => env('TEST_APPLE_APP_PASSWORD'),
            'calendar_url' => env('TEST_APPLE_CALENDAR_URL'),
        ],
    ],
];
