<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2017 Serhii Popov
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Popov
 * @package Popov_<package>
 * @author Serhii Popov <popow.sergiy@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace Popov\ZfcFileUpload\Service\Factory;

use Interop\Container\ContainerInterface;
use Popov\ZfcFileUpload\Service\FileUploadService;

class FileUploadServiceFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $fileService = $container->get('FileService');
        $userService = $container->get('UserService');

        $fileUploadService = new FileUploadService($fileService);
        $fileUploadService->setUser($userService->getCurrent());

        return $fileUploadService;
    }
}