<?php

namespace Popov\ZfcFileUpload;

return [

    'default' => [
        'assets' => [
            //'@file_upload_folder_mnemos',
            '@file_upload_js',
        ]
    ],

    'modules' => [
        __NAMESPACE__ => [
            'root_path' => __DIR__ . '/../view/assets',
            'collections' => [
                'file_upload_js' => [
                    'assets' => [
                        'js/file_upload.js',
                    ],
                ],
                'file_upload_folder_mnemos' => [
                    'assets' => [
                        //'js/file_upload_folder_mnemos.js',
                    ],
                ],
            ],
        ],
    ],
];