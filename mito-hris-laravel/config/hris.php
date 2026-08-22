<?php

return [
    'auth' => [
        'valid_roles' => [
            'Super Admin',
            'HR Manager',
            'HR Recruitment',
            'HR Staff',
        ],
        'role_permissions' => [
            'Super Admin' => ['*'],
            'HR Manager' => ['manage_recruitment', 'manage_employees', 'manage_probation', 'manage_settings', 'view_reports'],
            'HR Recruitment' => ['view_recruitment', 'update_candidates', 'create_offering', 'manage_hold_blacklist'],
            'HR Staff' => ['view_recruitment', 'view_employees', 'view_reports'],
        ],
    ],
];
