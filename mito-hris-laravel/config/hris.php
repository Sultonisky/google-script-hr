<?php

return [
    'auth' => [
        'valid_roles' => [
            'Super Admin',
            'HR Manager',
            'HR Recruitment',
            'HR Staff',
        ],
        // Manager role is NOT an internal HRIS role.
        // Manager accounts live in the mpr_requestor sheet, not Users.
        'valid_roles_internal' => [
            'Super Admin',
            'HR Manager',
            'HR Recruitment',
            'HR Staff',
        ],
        'valid_roles_requestor' => [
            'Manager',
        ],
        'role_permissions' => [
            'Super Admin' => ['*'],
            'HR Manager' => ['manage_recruitment', 'manage_employees', 'manage_probation', 'manage_settings', 'view_reports', 'view_employees', 'view_recruitment', 'view_mpr', 'create_mpr', 'export_mpr'],
            'HR Recruitment' => ['view_recruitment', 'update_candidates', 'create_offering', 'manage_hold_blacklist', 'view_mpr', 'export_mpr'],
            'HR Staff' => ['view_recruitment', 'view_employees', 'view_reports', 'view_mpr', 'export_mpr'],
            'Manager' => ['view_mpr', 'create_mpr', 'export_mpr'],
        ],
    ],
    'schemas' => [
        'Employee' => [
            'Employee ID', 'Full Name', 'Branch Name', 'Division', 'Department',
            'Job Position (Location)', 'Job Position', 'Area Kerja', 'Lokasi Kerja',
            'Job Level', 'Grade', 'Join Date', 'Status Employee', 'Direct Superior',
            'Indirect Superior', 'Personal Email', 'Working Email', 'End Date (Contract)',
            'Birth Place', 'Birth Date', 'Citizen ID Address', 'Residential Address',
            'NIK - NPWP 16 digit', 'NPWP', 'PTKP Status', 'Bank Name', 'Bank Account',
            'Bank Account Holder', 'BPJS Ketenagakerjaan', 'BPJS Kesehatan',
            'Mobile Phone', 'Religion', 'Gender', 'Marital Status', 'Blood Type',
            'Cost Center', 'Job Position (Former)', 'Type of Rotation',
            'Tanggal Mutasi/Demosi/Promosi', 'Nomor SK', 'Resign Date', 'HR Notes',
            'Offboarding Type', 'Offboarding Reason', 'Offboarding Approved By',
            'Offboarding Documents Folder', 'Offboarding Document Links',
            'Outsource Vendor', 'Created By', 'Created At', 'Updated At'
        ],
        'data_kandidat' => [
            'Recruitment ID', 'Created Date', 'Full Name', 'NIK', 'Birth Date',
            'Age', 'Gender', 'Marital Status', 'Email', 'Phone', 'Address',
            'City', 'Position Applied', 'Education', 'Work Experience',
            'Last Company', 'Current Employment Status', 'Available to Join',
            'Expected Salary', 'Recruitment Source', 'CV Link', 'Status',
            'HR Notes', 'Created By', 'Updated At',
            // Extra columns appended by GAS (EXTRA_HEADERS in Config.gs)
            'Hold Reason', 'Hold Follow Up Date', 'Blacklist Reason',
            'Blacklist Date', 'Blacklist Updated By', 'Employee ID',
        ],
        'Audit_Log' => [
            'Recruitment ID', 'Action', 'Field', 'Old Value', 'New Value', 'User', 'Timestamp'
        ],
        'Users' => [
            'Email', 'Username', 'Full Name', 'Role', 'Status',
            'Password Hash', 'Last Login', 'Created At', 'Updated At', 'Created By',
            'Entities', 'Branch',
        ],
        'kandidat_probation' => [
            'Probation ID', 'Employee ID', 'Recruitment ID', 'Contract Number',
            'Contract Duration', 'Contract Start', 'Contract End', 'Join Date',
            'Status', 'Onboarding Date', 'Onboarding By', 'Eval ID', 'Eval Date',
            'Score Performance', 'Score Discipline', 'Score Communication',
            'Score Initiative', 'Score Teamwork', 'Average Score', 'Decision',
            'Extension Duration', 'New Contract Start', 'New Contract End',
            'Evaluator Notes', 'Evaluator', 'SK Status', 'Notes', 'Created At', 'Updated At'
        ],
        'kandidat_hold' => [
            'Recruitment ID', 'Created Date', 'Full Name', 'NIK', 'Birth Date',
            'Age', 'Gender', 'Marital Status', 'Email', 'Phone', 'Address',
            'City', 'Position Applied', 'Education', 'Work Experience',
            'Last Company', 'Current Employment Status', 'Available to Join',
            'Expected Salary', 'Recruitment Source', 'CV Link', 'Status',
            'HR Notes', 'Created By', 'Updated At', 'Hold Reason',
            'Hold Follow Up Date', 'Blacklist Reason', 'Blacklist Date',
            'Blacklist Updated By', 'Employee ID', 'Processed Date', 'Processed By'
        ],
        'kandidat_accepted' => [
            'Recruitment ID', 'Created Date', 'Full Name', 'NIK', 'Birth Date',
            'Age', 'Gender', 'Marital Status', 'Email', 'Phone', 'Address',
            'City', 'Position Applied', 'Education', 'Work Experience',
            'Last Company', 'Current Employment Status', 'Available to Join',
            'Expected Salary', 'Recruitment Source', 'CV Link', 'Status',
            'HR Notes', 'Created By', 'Updated At', 'Hold Reason',
            'Hold Follow Up Date', 'Blacklist Reason', 'Blacklist Date',
            'Blacklist Updated By', 'Employee ID', 'Processed Date', 'Processed By',
            'Offering Created', 'Offering Updated', 'Offering Created By',
            'Offering Updated By', 'Offering Company Entity', 'Offering Position',
            'Offering Salary', 'Offering Join Date', 'Offering Notes',
            'Offering Response', 'Offering Response Notes', 'Offering Response Date',
            'Offering Response By', 'Onboarding Status', 'Onboarding Date',
            'Onboarding By', 'Offering Division', 'Offering Job Level',
            'Offering Lokasi Kerja', 'Offering Salary Basic', 'Offering Allow Pulsa',
            'Offering Allow Transport', 'Offering Employment Status',
            'Offering Contract Duration', 'Offering Working Hours'
        ],
        'kandidat_blacklist' => [
            'Recruitment ID', 'Created Date', 'Full Name', 'NIK', 'Birth Date',
            'Age', 'Gender', 'Marital Status', 'Email', 'Phone', 'Address',
            'City', 'Position Applied', 'Education', 'Work Experience',
            'Last Company', 'Current Employment Status', 'Available to Join',
            'Expected Salary', 'Recruitment Source', 'CV Link', 'Status',
            'HR Notes', 'Created By', 'Updated At', 'Hold Reason',
            'Hold Follow Up Date', 'Blacklist Reason', 'Blacklist Date',
            'Blacklist Updated By', 'Employee ID', 'Processed Date', 'Processed By'
        ],
        'MPR' => [
            'MPR Number', 'Request Date', 'Requestor Name', 'Requestor Email',
            'Entity', 'Branch',
            'Department', 'Division', 'Position', 'Job Level', 'Work Location',
            'Employment Type', 'Quantity', 'Expected Join Date', 'Reason',
            'Replacement For', 'Job Description', 'Requirements', 'Notes',
            'Status', 'Created By', 'Created At', 'Updated At',
        ],
        'mpr_requestor' => [
            'Requestor ID', 'Email', 'Username', 'Full Name', 'Role',
            'Status', 'Password Hash', 'Entity', 'Branch',
            'Last Login', 'Created At', 'Updated At', 'Created By',
        ],
    ],
];
