<?php

return [
    'school' => [
        'name' => env('SCHOOL_NAME', config('app.name', 'Integrated School Management System')),
        'address' => env('SCHOOL_ADDRESS'),
        'phone' => env('SCHOOL_PHONE'),
        'email' => env('SCHOOL_EMAIL'),
        'logo_path' => env('SCHOOL_LOGO_PATH'),
    ],
];
