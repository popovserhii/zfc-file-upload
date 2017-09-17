<?php
namespace Popov\ZfcFileUpload\Transfer\Adapter;

use Zend\I18n\View\Helper\Translate;

//Magere\Agere\String\StringUtils;
/**
 * Class Http
 *
 * @package Popov\ZfcFileUpload\Transfer\Adapter
 *
 * @deprecated Use https://olegkrivtsov.github.io/using-zend-framework-3-book/html/en/Uploading_Files_with_Forms.html
 * @see https://github.com/zendframework/zendframework/issues/6291#issuecomment-43485150
 */
class Http
{
    const MAX_FILE_NAME_SIZE = 100;

    const UPLOAD_ERR_SIZE_FILE_NAME = 101;

    protected $_destination = '';

    protected $_prefixFileName = '';

    /** @var Translate $translate */
    protected static $translate;

    public static function setTranslate(Translate $translate)
    {
        self::$translate = $translate;
        //self::$translate->setTranslatorTextDomain('Magere\Agere');
    }

    public static function getTranslate()
    {
        return self::$translate;
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->_destination = rtrim($destination, DIRECTORY_SEPARATOR);
        $dir = preg_replace('/^(.*)\/([0-9])+$/', '$1', $this->_destination);
        @mkdir($dir);
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->_destination;
    }

    /**
     * @param string $prefixFileName
     */
    public function setPrefixFileName($prefixFileName)
    {
        $this->_prefixFileName = $prefixFileName;
    }

    /**
     * @return string
     */
    public function getPrefixFileName()
    {
        return $this->_prefixFileName;
    }

    /**
     * @param string $dir
     * @return array
     */
    public function getFiles($dir)
    {
        $files = [];
        $dh = @opendir($dir);
        $dir = rtrim($dir, '/');
        if ($dh !== false) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            while (false !== ($filename = readdir($dh))) {
                if (!in_array($filename, ['.', '..'])) {
                    $filePath = $dir . '/' . $filename;
                    $files[$filename] = [
                        'type' => finfo_file($finfo, $filePath), //filetype($filePath),
                        'size' => filesize($filePath),
                    ];
                }
            }
            finfo_close($finfo);
        }

        return $files;
    }

    /**
     * Receive the file from the client (Upload)
     *
     * @param array $files
     * @return array
     */
    public function receive($files)
    {
        $uploadFiles = [];
        foreach ($files as $content) {
            if (!isset($content['name'])) {
                for ($i = 0, $len = count($content); $i < $len; ++$i) {
                    $data = [
                        'name' => $content[$i]['name'],
                        'tmp_name' => $content[$i]['tmp_name'],
                    ];
                    if ($newFileName = $this->uploadFile($data)) {
                        $uploadFiles[] = $newFileName;
                    }
                }
            } else {
                if ($newFileName = $this->uploadFile($content)) {
                    $uploadFiles[] = $newFileName;
                }
            }
        }

        return $uploadFiles;
    }

    /**
     * @param array $content
     * @return string
     */
    public function uploadFile($content)
    {
        $dir = $this->_destination;
        $this->createFolder($dir);

        // New file name
        $content['name'] = str_replace('%', '', $content['name']);
        $filename = $dir . DIRECTORY_SEPARATOR . $content['name'];//$newFileName;
        return move_uploaded_file($content['tmp_name'], $filename);//$newFileName : '';
    }

    /**
     * @param int $errorCode
     * @return string
     */
    public function errorMessage($errorCode)
    {
        $errorMessage = '';
        $translate = self::getTranslate();
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage = $translate("File can not be loaded because file size exceeds '%s'");
                $errorMessage = sprintf($errorMessage, ini_get('upload_max_filesize'));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage =
                    $translate('File can not be loaded because file exceeds MAX_FILE_SIZE directive that was specified in the HTML form.');
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = $translate('The uploaded file was only partially uploaded. Remove and refresh.');
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = $translate('No file was uploaded.');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage = $translate('Failed to write file to disk.');
                break;
            case self::UPLOAD_ERR_SIZE_FILE_NAME:
                $errorMessage = $translate("File can not be loaded because file name more than '%s' of characters");
                $errorMessage = sprintf($errorMessage, self::MAX_FILE_NAME_SIZE);
                break;
            default:
                if (!$errorCode) {
                    $errorMessage = $translate('File can not be loaded.');
                }
        }

        return $errorMessage;
    }

    /**
     * @param string $dir
     * @return bool
     */
    public function emptyDir($dir)
    {
        $files = is_dir($dir) ? scandir($dir) : [];

        return !(is_array($files) && count($files) > 2);
    }

    /**
     * @param string $pathFile
     */
    public function delete($pathFile)
    {
        @unlink($pathFile);
    }

    /**
     * @param string $pathFolder
     */
    public function createFolder($pathFolder)
    {
        @mkdir($pathFolder, 0777, true);
    }

    /**
     * @param string $pathFolder
     */
    public function deleteEmptyFolder($pathFolder)
    {
        if (is_dir($pathFolder)) {
            $files = scandir($pathFolder);
            if (count($files) == 2) {
                @rmdir($pathFolder);
            }
        }
    }

    /**
     * @param string $pathFolder
     */
    public function deleteFolder($pathFolder)
    {
        if (is_dir($pathFolder)) {
            $dh = opendir($pathFolder);
            while (false !== ($filename = readdir($dh))) {
                if (!in_array($filename, ['.', '..'])) {
                    $tmpDir = $pathFolder . '/' . $filename;
                    if (is_dir($tmpDir)) {
                        $this->deleteFolder($tmpDir);
                    } else {
                        $this->delete($tmpDir);
                    }
                }
            }
            closedir($dh);
            @rmdir($pathFolder);
        }
    }
}
