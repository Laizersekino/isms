<?php

return [
    'passing_threshold' => 50,
    'grades' => [
        ['code' => 'A', 'min_percentage' => 80, 'max_percentage' => 100],
        ['code' => 'B', 'min_percentage' => 70, 'max_percentage' => 79.99],
        ['code' => 'C', 'min_percentage' => 60, 'max_percentage' => 69.99],
        ['code' => 'D', 'min_percentage' => 50, 'max_percentage' => 59.99],
        ['code' => 'F', 'min_percentage' => 0, 'max_percentage' => 49.99],
    ],
    'school' => [
        'name' => env('SCHOOL_NAME', config('app.name', 'Integrated School Management System')),
        'address' => env('SCHOOL_ADDRESS'),
        'phone' => env('SCHOOL_PHONE'),
        'email' => env('SCHOOL_EMAIL'),
        'logo_path' => env('SCHOOL_LOGO_PATH'),
    ],
];
