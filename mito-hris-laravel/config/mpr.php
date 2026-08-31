<?php

$defaultHost = parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';

return [
    'domain' => env('MPR_DOMAIN', 'mpr.' . $defaultHost),
    'session_key' => env('MPR_SESSION_KEY', 'mpr_requestor_auth'),
    'requestor_sheet' => env('GOOGLE_SHEET_MPR_REQUESTOR', 'mpr_requestor'),
];
