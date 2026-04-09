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
];
