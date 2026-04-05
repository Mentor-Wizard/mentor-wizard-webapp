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
    'encryption_key2' => env('CALENDAR_ENCRYPTION_KEY2'),
];
