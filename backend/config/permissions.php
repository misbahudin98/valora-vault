<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Client-Customizable Roles & Permissions Matrix
    |--------------------------------------------------------------------------
    |
    | Definisikan pemetaan peran (roles) dan hak akses (permissions) di sini.
    | Klien dapat menambahkan peran kustom atau mengubah matriks izin tanpa
    | perlu melakukan migrasi database.
    |
    | Struktur:
    | 'role_name' => ['permission.one', 'permission.two', ...]
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
        'manager' => [
            'users.view',
            'users.create',
            'users.edit',
            'sessions.view',
            'reports.view',
            'reports.export',
        ],
        'staff' => [
            'users.view',
            'reports.view',
        ],
        'auditor' => [
            'users.view',
            'sessions.view',
            'reports.view',
            'reports.export',
        ],
        'user' => [
            'profile.view',
            'profile.edit',
        ],
    ],
];
