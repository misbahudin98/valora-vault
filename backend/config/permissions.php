<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Client-Customizable Roles & Permissions Matrix (Single Admin Role Architecture)
    |--------------------------------------------------------------------------
    |
    | Portal Manajemen Proyek ini dikonfigurasi dengan peran tunggal: 'admin'.
    | Seluruh hak akses dan kendali sistem dipegang secara penuh oleh Administrator.
    |
    */
    'roles' => [
        'admin' => [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.toggle_status',
            'users.revoke_sessions',
            'sessions.view',
            'sessions.revoke',
            'sessions.cleanup',
            'settings.manage',
            'reports.view',
            'reports.export',
        ],
    ],
];
