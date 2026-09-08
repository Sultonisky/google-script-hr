<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Cloud Credentials & Service Account
    |--------------------------------------------------------------------------
    | Path to JSON key file generated from Google Cloud Console Service Account.
    */
    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', storage_path('app/google/service-account.json')),

    /*
    |--------------------------------------------------------------------------
    | Google Sheets Database ID
    |--------------------------------------------------------------------------
    | Spreadsheet ID for MITO HRIS master spreadsheet.
    | Example: https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit
    */
    'spreadsheet_id' => env('GOOGLE_SPREADSHEET_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Sheets Tab Names
    |--------------------------------------------------------------------------
    */
    'sheets' => [
        'candidates' => env('GOOGLE_SHEET_CANDIDATES', 'data_kandidat'),
        'candidates_hold' => env('GOOGLE_SHEET_CANDIDATES_HOLD', 'kandidat_hold'),
        'candidates_blacklist' => env('GOOGLE_SHEET_CANDIDATES_BLACKLIST', 'kandidat_blacklist'),
        'candidates_accepted' => env('GOOGLE_SHEET_CANDIDATES_ACCEPTED', 'kandidat_accepted'),
        'candidates_probation' => env('GOOGLE_SHEET_CANDIDATES_PROBATION', 'kandidat_probation'),
        'employees'  => env('GOOGLE_SHEET_EMPLOYEES', 'Employee'),
        'audit_log'  => env('GOOGLE_SHEET_AUDIT_LOG', 'Audit_Log'),
        'users'      => env('GOOGLE_SHEET_USERS', 'Users'),
        'permissions' => env('GOOGLE_SHEET_PERMISSIONS', 'Permissions'),
        'user_permissions' => env('GOOGLE_SHEET_USER_PERMISSIONS', 'User_Permissions'),
        'mpr'        => env('GOOGLE_SHEET_MPR', 'MPR'),
        'mpr_requestor' => env('GOOGLE_SHEET_MPR_REQUESTOR', 'mpr_requestor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Drive Storage Folders
    |--------------------------------------------------------------------------
    */
    'drive' => [
        'docs_folder_id'         => env('GOOGLE_DRIVE_DOCS_FOLDER_ID', ''),
        'offboarding_folder_id'  => env('GOOGLE_DRIVE_OFFBOARDING_FOLDER_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timezone & Timestamp
    |--------------------------------------------------------------------------
    */
    'timezone' => env('GOOGLE_SHEETS_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings for Sheets API
    |--------------------------------------------------------------------------
    | TTL in seconds to cache spreadsheet rows and avoid hitting API rate limits.
    */
    'cache_ttl' => env('GOOGLE_SHEETS_CACHE_TTL', 60),

    /*
    | Retry transient Sheets API failures such as quota rate limiting.
    | The setup command must still fail for permanent/authentication errors.
    */
    'schema_retry_attempts' => (int) env('GOOGLE_SHEETS_SCHEMA_RETRY_ATTEMPTS', 4),
    'schema_retry_backoff_seconds' => (int) env('GOOGLE_SHEETS_SCHEMA_RETRY_BACKOFF_SECONDS', 15),
];
