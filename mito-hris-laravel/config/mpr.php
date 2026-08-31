<?php

return [
    'domain' => config('hris.domains.mpr', env('MPR_DOMAIN', 'mpr.localhost')),
    'session_key' => env('MPR_SESSION_KEY', 'mpr_requestor_auth'),
    'requestor_sheet' => env('GOOGLE_SHEET_MPR_REQUESTOR', 'mpr_requestor'),
];
