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
            'FileUploadService' => 'Popov\FileUpload\Service\FileUploadService',
        ],
        'factories' => [
            'Popov\FileUpload\Service\FileUploadService' => function ($sm) {
                $em = $sm->get('Doctrine\ORM\EntityManager');
                $service = \Popov\Agere\Service\Factory\Helper::create('fileUpload/fileUpload', $em);

                return $service;
            },
        ],
    ],
];