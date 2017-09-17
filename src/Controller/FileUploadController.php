<?php
namespace Popov\ZfcFileUpload\Controller;

use Agere\Document\Service\DocumentService;
use Zend\Filter\Word\CamelCaseToUnderscore;
use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Zend\View\Model\JsonModel;
use \Popov\ZfcFileUpload\Transfer\Adapter\Http as FileHttp;
use Popov\ZfcUser\Controller\Plugin\UserPlugin;
use Popov\ZfcEntity\Controller\Plugin\EntityPlugin;

/**
 * Class FileUploadController
 *
 * @method UserPlugin user()
 * @method EntityPlugin entity()
 */
class FileUploadController extends AbstractActionController {

    public $serviceName = 'FileUploadService';

    public function uploadAction()
    {
        // Create the form model.
        $form = new \Popov\ZfcFileUpload\Form\UploadForm();
        $form->init();

        // Check if user has submitted the form.
        if($this->getRequest()->isPost()) {

            // Make certain to merge the files info!
            $request = $this->getRequest();
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // Pass data to form.
            $form->setData($data);

            // Validate form.
            if($form->isValid()) {

                // Move uploaded file to its destination directory.
                $data = $form->getData();

                // Redirect the user to "Image Gallery" page.
                return $this->redirect()->toRoute('images');
            }
        }

        // Render the page.
        return new ViewModel([
            'form' => $form
        ]);
    }

    public function indexAction()
    {
        $e = $this->getEvent();

        /** @var \Zend\Http\Request $request */
        $request = $this->getRequest();
        /** @var \Zend\ServiceManager\ServiceLocatorInterface $sm */
        $sm = $this->getServiceLocator();

        if ($request->isXmlHttpRequest()) {
            $route = $e->getRouteMatch();
            $currentPage = $route->getParam('controller') . '/' . $route->getParam('action');

            /** @var \Popov\ZfcFileUpload\Service\FileUploadService $service */
            $service = $sm->get($this->serviceName);
            /** @var \Popov\ZfcFile\Service\FileService $serviceFile */
            $serviceFile = $sm->get('FileService');

            if (!$this->user()->isAdmin() && !$this->user()->hasAccess($currentPage)) {
                $result = new JsonModel([
                    'files' => [['error' => 'Access denied']],
                ]);
            } else {
                $pathUploadFiles = $service->getPathUploadFiles();

                // Get route match referrer
                $referrerRouteMatch = $this->getReferrerRouteMatch($request);

                // Set config params
                $referrerMnemo = '';
                $referrerId = 0;
                $showIfIssetFile = true;

                if ($referrerRouteMatch) {
                    $referrerMnemo = $referrerRouteMatch->getParam('controller');
                    $referrerAction = $referrerRouteMatch->getParam('action');
                    $referrerId = ($referrerAction != 'add') ? (int) $referrerRouteMatch->getParam('id', 0) : 0;
                }

                if ($referrerMnemo) {
                    $entity = $this->entity()->getBy($referrerMnemo, 'mnemo');

                    $url = "{$pathUploadFiles}{$referrerMnemo}/" . ($referrerId ? $referrerId : session_id());
                    $deleteUrl = "/file/delete-file/";
                    $childrenDeleteUrl = "/parent/{$entity->getId()}";

                    $dir = $service->getFolderPublic() . $url;



                    if ($request->isPost()) {
                        #$responseEvent = $this->getEventManager()->trigger('upload.pre', $this, $params);
                        //$items = $responseEvent->first();
                        #if ($message) {
                        #    $result = new JsonModel([
                        #        'files' => [['error' => $message]],
                        #    ]);

                        $files = $request->getFiles()->toArray();


                        // Upload or (upload and save) files
                        $uploadFiles = $service->save($files, $referrerId, $url, $deleteUrl, $dir, $entity/*, $childrenDeleteUrl*/);

                        $result = new JsonModel($uploadFiles);
                    } else if ($request->isGet()) {
                        // Set files items
                        $items = $serviceFile->getRepository()->findBy([
                            'itemId' => $referrerId,
                            'entity' => $entity
                        ]);

                        #if (!$items) {
                            // Session Module FileUpload
                        #    $params = [
                        #        'locator' => $sm,
                        #        'mnemo' => $referrerMnemo,
                        #        'id' => $referrerRouteMatch->getParam('id'),
                        #    ];

                        #    $responseEvent = $this->getEventManager()->trigger('loadFileSession', $this, $params);
                            //$responseEvent = $service->loadFileSession(__CLASS__, $params);
                        #    $items = $responseEvent->first();
                            // END Session Module FileUpload
                        #}
                        // END Set files items

                        $data['files'] = $this->_initDataJson($items, /*$referrerMnemo, $url,*/ $deleteUrl, $dir, $childrenDeleteUrl, $showIfIssetFile);

                        $result = new JsonModel($data);
                    }
                }
                // END Set config params
            }

            return $result;
        } else {
            $this->getResponse()->setStatusCode(404);
        }
    }


    //------------------------------------PRIVATE----------------------------------------
    /**
     * @param \Zend\Http\Request $request
     * @param string $keyHeader
     * @return null|\Zend\Mvc\Router\RouteMatch
     */
    private function getReferrerRouteMatch($request, $keyHeader = 'referer')
    {
        $sm = $this->getServiceLocator();
        /** @var \Zend\Http\Header\Referer $paramHeader */
        $paramHeader = $request->getHeader($keyHeader);
        $uri = $paramHeader->uri()->getPath();
        $request->setUri($uri);
        /** @var \Zend\Mvc\Router\RouteInterface $router */
        $router = $sm->get('router');
        $routeMatch = $router->match($request);

        return $routeMatch;
    }

    /**
     * @param array|\Popov\Documents\Service\DocumentFilesService|\Popov\Files\Service\FilesService $items
     * @param string $mnemo
     * @param string $url
     * @param string $deleteUrl
     * @param string $dir
     * @param string $childrenDeleteUrl
     * @param bool $showIfIssetFile
     * @return array
     */
    private function _initDataJson($items, /*$mnemo, $url, */$deleteUrl, $dir, $childrenDeleteUrl, $showIfIssetFile = true)
    {
        $data = [];
        $i = 0;

        if ($items) {
            $filesHttp = new FileHttp();
            $files = $filesHttp->getFiles($dir);

            foreach ($items as $key => $item) {
                $creator = '';
                $dateCreate = '';

                $id = $item->getId();
                $name = $item->getName();

                $data[$i] = [
                    'url' => $item->getName(),
                    'name' => pathinfo($item->getName(), PATHINFO_FILENAME),
                    'deleteUrl' => "{$deleteUrl}{$id}{$childrenDeleteUrl}",
                    'deleteType' => 'DELETE',
                    'creator' => $creator,
                    'dateCreate' => $dateCreate,
                ];

                if (isset($files[$name])) {
                    $data[$i]['type'] = $files[$name]['type'];
                    $data[$i]['size'] = $files[$name]['size'];
                } elseif (! $showIfIssetFile) {
                    unset($data[$i]);
                }

                ++ $i;
            }
        }

        return array_values($data);
    }

}