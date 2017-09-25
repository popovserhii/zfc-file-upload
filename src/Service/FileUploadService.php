<?php
namespace Popov\ZfcFileUpload\Service;

use Popov\ZfcEntity\Model\Entity;
use Popov\ZfcFile\Model\File;
use Popov\ZfcFile\Service\FileService;
#use Zend\File\Transfer\Adapter\Http as FileHttp;
use Popov\ZfcUser\Service\UserAwareInterface;
use Popov\ZfcUser\Service\UserAwareTrait;
use Zend\Session\Container as SessionContainer;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use DoctrineModule\Persistence\ProvidesObjectManager;
use Popov\ZfcFileUpload\Transfer\Adapter\Http as FileHttp;
//use Popov\Agere\Service\AbstractEntityService,
	//Popov\Agere\File\Transfer\Adapter\Http as AgereFileHttp,
	//Popov\Logs\Event\Logs as LogsEvent;


//class FileUploadService extends AbstractEntityService {
class FileUploadService implements ObjectManagerAwareInterface, UserAwareInterface
{
    use ProvidesObjectManager;
    use UserAwareTrait;

    const MAX_FILE_NAME_SIZE = 100;
    const UPLOAD_ERR_SIZE_FILE_NAME = 101;

	protected $_pathUploadFiles = 'uploads/';
	protected $_folderPublic = 'public/';
	protected $_sessionName = 'fileUpload';

	/** @var FileService */
	protected $fileService;

    public function __construct($fileService)
    {
        $this->fileService = $fileService;
    }

    /**
	 * @return string
	 */
	public function getPathUploadFiles()
	{
		return $this->_pathUploadFiles;
	}

	/**
	 * @return string
	 */
	public function getFolderPublic()
	{
		return $this->_folderPublic;
	}

	/**
	 * @return string
	 */
	public function getSessionName()
	{
		return $this->_sessionName;
	}

	public function getFileService()
    {
        return $this->fileService;
    }

	/**
	 * @param array $uploadFiles
	 * @param int $referrerId
	 * @param string $dir
	 * @param string $deleteUrl
	 * @param string $url
	 * @param null|Entity $entity
	 * @param string $childrenDeleteUrl
	 * @return array
	 */
	public function save(array $uploadFiles, $referrerId, $url, $deleteUrl, $dir, /*$service = null,*/ $entity = null, $childrenDeleteUrl = '')
	{
		$uploadedFiles = [];

		$upload = new FileHttp();
        #$upload->createFolder($dir);
        $upload->setDestination($dir);

		$session = new SessionContainer($this->_sessionName);
		$sessionFiles = [];

		if ($session->offsetExists('files'))
		{
			$sessionFiles = $session->files;
		}

        $fileService = $this->getFileService();

        $collections = [];
        foreach ($uploadFiles as $formName => $content) {
            foreach ($content as $args) {
                if (is_array($args) && isset($args['name'])) {
                    // Upload file
                    if (strlen($args['name']) > (self::MAX_FILE_NAME_SIZE * 2)) {
                        $args['error'] = self::UPLOAD_ERR_SIZE_FILE_NAME;
                    } elseif ($upload->uploadFile($args)) {
                        $name = $args['name'];
                        if (!is_null($entity) && !$args['error']) {
                            /** @var File $file */
                            $file = $fileService->getObjectModel();
                            $file->setName($url . '/' . $name)
                                ->setItemId($referrerId)
                                ->setEntity($entity)
                                ->setCreatedAt(new \DateTime('now'))
                                ->setCreatedBy($this->getUser());
                            $collections[$formName][] = $file;
                        }
                    }
                }
            }
        }

        // save all persisted files
        $fileService->getObjectManager()->flush();

        $i = 0;
        foreach ($collections as $formName => $collection) {
            foreach ($collection as $key => $file) {
                #if (!isset($args['error'])) {
                #    $sessionFiles[$entity->getMnemo()][] = $file->getName();
                #    end($sessionFiles[$entity->getMnemo()]);
                #    $id = key($sessionFiles[$entity->getMnemo()]);
                #}

                $uploadInfo = $uploadFiles[$formName][$key];

                $uploadedFiles[$formName][$i] = [
                    'url' => $file->getName(),
                    'name' => $uploadInfo['name'],
                    'type' => $uploadInfo['type'],
                    'size' => $uploadInfo['size'],
                    'deleteUrl' => $deleteUrl . $file->getId(),
                    'deleteType' => 'DELETE',
                    'creator' => $file->getCreatedBy()->getEmail(),
                    'dateCreate' => $file->getCreatedAt()->format('d/m/Y H:i:s'),
                ];

                if (isset($args['error']) && $args['error']) {
                    $uploadedFiles[$key][$i]['error'] = $upload->errorMessage($args['error']);
                }
                ++$i;
            }
        }


		if ($sessionFiles){
			$session->files = $sessionFiles;
		}

		return $uploadedFiles;
	}


	//------------------------------------------Events------------------------------------------
	/**
	 * Module Users
	 *
	 * @param $class
	 * @param $params
	 * @return mixed
	 */
	public function index($class, $params)
	{
	    die(__METHOD__);
		$event = new LogsEvent();
		return $event->events($class)->trigger('fileUpload.index', $this, $params);
	}

	/**
	 * Module FileUpload
	 *
	 * @param $class
	 * @param $params
	 * @return mixed
	 */
	public function loadFileSession($class, $params)
	{
	    die(__METHOD__);
		$event = new LogsEvent();
		return $event->events($class)->trigger('fileUpload.loadFileSession', $this, $params);
	}

}