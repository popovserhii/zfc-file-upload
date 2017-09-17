<?php
namespace Popov\ZfcFileUpload;

return [
    'default' => [
        'assets' => [
            '@fileUpload_css',
            '@fileUpload_js',
            //'@jquery_fileUpload_images',

            //'@file_upload_folder_mnemos',
            '@file_upload_js',
        ],
    ],
    'modules' => [
        __NAMESPACE__ . '_fileUpload' => [
            'root_path' => __DIR__ . '/../view/assets/jquery-file-upload-9.5.7',
            'collections' => [
                'fileUpload_css' => [
                    'assets' => [
                        'css/jquery.fileupload.css',
                        'css/jquery.fileupload-ui.css',
                    ],
                    //'filters' => ['?CssRewriteFilter' => ['name' => \Assetic\Filter\CssRewriteFilter::class]],
                    'options' => ['output' => 'css/fileUpload.css'],
                ],

                'fileUpload_js' => [
                    'assets' => [
                        'js/vendor/jquery.ui.widget.js',
                        'js/vendor/tmpl.min.js',
                        'js/jquery.iframe-transport.js',
                        'js/jquery.fileupload.js',
                        'js/jquery.fileupload-process.js',
                        'js/jquery.fileupload-ui.js',
                        //'media/js/jquery/jquery-file-upload-9.5.7/js/main.js'
                    ],
                ],

                'fileUpload_images' => [
                    'assets' => [
                        'img/*.png',
                        'img/*.gif',
                    ],
                    'options' => [
                        'move_raw' => true,
                        'disable_source_path' => true,
                        'targetPath' => 'img',
                    ]
                ],
            ],
        ],
        __NAMESPACE__ . '_module' => [
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