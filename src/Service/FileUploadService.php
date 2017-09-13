<?php
namespace Popov\ZfcFileUpload\Service;

use Popov\Agere\Service\AbstractEntityService,
	Popov\Agere\File\Transfer\Adapter\Http as AgereFileHttp,
	Zend\Session\Container as SessionContainer,
	Popov\Logs\Event\Logs as LogsEvent;

class FileUploadService extends AbstractEntityService {

	protected $_pathUploadFiles = '/uploads/';
	protected $_folderPublic = './public';
	protected $_sessionName = 'fileUpload';


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

	/**
	 * @param array $files
	 * @param string $mnemo
	 * @param int $parentId
	 * @param string $dir	 *
	 * @param string $deleteUrl
	 * @param string $url
	 * @param null|object $service
	 * @param null|object $itemObject
	 * @param string $childrenDeleteUrl
	 * @return array
	 */
	public function save(array $files, $mnemo, $parentId, $url, $deleteUrl, $dir, $service = null, $itemObject = null, $childrenDeleteUrl = '')
	{
		$uploadFiles = [];
		$i = 0;

		$upload = new AgereFileHttp();
		$upload->setDestination($dir);

		$session = new SessionContainer($this->_sessionName);
		$sessionFiles = [];

		if ($session->offsetExists('files'))
		{
			$sessionFiles = $session->files;
		}

		foreach ($files as $key => $content)
		{
			foreach ($content as $args)
			{
				if (is_array($args) && isset($args['name']))
				{
					$item = null;
					$id = null;
                    $creator = '';
                    $dateCreate = '';

					// Upload file
                    $name = '';

                    if (strlen($args['name']) > ($upload::MAX_FILE_NAME_SIZE * 2))
                    {
                        $args['error'] = $upload::UPLOAD_ERR_SIZE_FILE_NAME;
                    }
                    else
                    {
                        $name = $upload->uploadFile($args);
                    }

					if ($name == '')
					{
						$name = $args['name'];
					}

					if (! is_null($service) && ! is_null($itemObject) && ! $args['error'])
					{
						switch ($mnemo)
						{
							case 'documents':
								$item = $service->save($itemObject, $name);
								break;
							default:
								$item = $service->save($parentId, $name, $itemObject);
								break;
						}

						if (is_object($item))
						{
							$id = $item->getId();

                            $creatorMethod = 'getCreator';
                            if (method_exists($item, $creatorMethod) && $item->$creatorMethod())
                            {
                                $creator = $item->$creatorMethod()->getEmail();
                            }

                            $dateCreateMethod = 'getDateCreate';
                            if (method_exists($item, $dateCreateMethod) && $item->$dateCreateMethod())
                            {
                                $dateCreate = $item->$dateCreateMethod()->format('d.m.Y H:i:s');
                            }
						}
					}

					if (is_null($id))
					{
						if (! $args['error'])
						{
							$sessionFiles[$mnemo][] = $name;

							end($sessionFiles[$mnemo]);
							$id = key($sessionFiles[$mnemo]);
						}
						else
						{
							$id = 0;
						}
					}

					$uploadFiles[$key][$i] = [
						'url'			=> "{$url}/{$name}",
						'name'			=> $name,
						'type'			=> $args['type'],
						'size'			=> $args['size'],
						'deleteUrl'		=> $deleteUrl.$id.$childrenDeleteUrl,
						'deleteType'	=> 'DELETE',
                        'creator'       => $creator,
                        'dateCreate'    => $dateCreate,
					];

					if ($args['error'])
					{
						$uploadFiles[$key][$i]['error'] = $upload->errorMessage($args['error']);
					}

					++ $i;
				}
			}
		}

		if ($sessionFiles)
		{
			$session->files = $sessionFiles;
		}

		return $uploadFiles;
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
		$event = new LogsEvent();
		return $event->events($class)->trigger('fileUpload.loadFileSession', $this, $params);
	}

}