<?php
namespace Popov\ZfcFileUpload;

return [
    'assetic_configuration' => require_once 'assets.config.php',

    'controllers' => [
        'invokables' => [
            'file-upload' => Controller\FileUploadController::class,
        ],
    ],

    'view_manager' => [
        'template_map' => [
            'template/file-upload' => __DIR__ . '/../view/template/file-upload.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],

    'service_manager' => [
        'aliases' => [
            'FileUploadService' => Service\FileUploadService::class,
        ],
        'factories' => [
            Service\FileUploadService::class => Service\Factory\FileUploadServiceFactory::class,
        ],
    ],
];