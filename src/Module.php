<?php
namespace Popov\ZfcFileUpload;

use Zend\ModuleManager\ModuleManager;
use Zend\EventManager\Event;
use Zend\Session\Container as SessionContainer;

class Module
{
    /**
     * @param ModuleManager $mm
     */
    public function init(ModuleManager $mm)
    {
        #$mm->getEventManager()->getSharedManager()
        #    ->attach('Popov\Documents\Controller\DocumentsController', ['documents.fileSession'], function (Event $evt) {
        #        return $this->getSessionFiles($evt);
        #    });
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * @param $evt
     * @param bool $removeSession
     * @return array|string
     */
    protected function getSessionFiles($evt, $removeSession = true)
    {
        $locator = $evt->getParam('locator');
        /** @var \Popov\ZfcFileUpload\Service\FileUploadService $serviceFileUpload */
        $serviceFileUpload = $locator->get('FileUploadService');
        $session = new SessionContainer($serviceFileUpload->getSessionName());
        $sessionFiles = [];
        $mnemo = $evt->getParam('mnemo');
        $id = $evt->getParam('id');
        if ($session->offsetExists('files')) {
            $sessionFiles = $session->files;
        }

        // Files
        if (is_null($id)) {
            if (isset($sessionFiles[$mnemo])) {
                $tmpFiles = $sessionFiles[$mnemo];
                if ($removeSession) {
                    unset($sessionFiles[$mnemo]);
                    $session->files = $sessionFiles;
                }

                return $tmpFiles;
            }

            return [];
        }

        // One file
        if (isset($sessionFiles[$mnemo][$id])) {
            $tmpFile = $sessionFiles[$mnemo][$id];
            if ($removeSession) {
                unset($sessionFiles[$mnemo][$id]);
                $session->files = $sessionFiles;
            }

            return $tmpFile;
        }

        return '';
    }
}