<?php

return [
    'path'             => 'logs/sela',
    'path_date_format' => 'Y_m_d',
    'use_storage'      => true,
    'load_migrations'  => false,
    'directories'      => [
        'App' => base_path('app/Http/Controllers')
    ]
];
