<?php
namespace Popov\ZfcFileUpload\Controller;

use Agere\Document\Service\DocumentService;
use Zend\Filter\Word\CamelCaseToUnderscore;
use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Zend\View\Model\JsonModel,
    Popov\Agere\File\Transfer\Adapter\Http as AgereFileHttp;
use Popov\ZfcUser\Controller\Plugin\UserPlugin;
/**
 * Class FileUploadController
 *
 * @method UserPlugin user()
 */
class FileUploadController extends AbstractActionController {

    public $serviceName = 'FileUploadService';

    public function uploadAction()
    {
        // Create the form model.
        $form = new \Popov\ZfcFileUpload\From\UploadForm();

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
        //$acl = $e->getViewModel()->acl;
        $route = $e->getRouteMatch();
        $currentPage = $route->getParam('controller').'/'.$route->getParam('action');

        /** @var \Zend\Http\Request $request */
        $request = $this->getRequest();
        /** @var \Zend\ServiceManager\ServiceLocatorInterface $sm */
        $sm = $this->getServiceLocator();

        //$currentUser = $this->getCurrentUser($sm);

        if ($request->isXmlHttpRequest()) {
            /** @var \Popov\ZfcFileUpload\Service\FileUploadService $service */
            $service = $sm->get($this->serviceName);

            if (!$this->user()->isAdmin() && !$this->user()->hasAccess($currentPage)) {
                $result = new JsonModel([
                    'files' => [['error' => 'Access denied']],
                ]);
            } else {
                $pathUploadFiles = $service->getPathUploadFiles();

                // Get route match referrer
                $referrerRouteMatch = $this->getReferrerRouteMatch($request);

                // Set config params
                $mnemo = '';
                $action = '';
                $parentId = 0;
                $url = '';
                $deleteUrl = '';
                $dir = '';
                $showIfIssetFile = true;
                $serviceFiles = null;
                $itemObject = null;

                if ($referrerRouteMatch) {
                    $mnemo = $referrerRouteMatch->getParam('controller');
                    $action = $referrerRouteMatch->getParam('action');
                    $parentId = ($action != 'add') ? (int) $referrerRouteMatch->getParam('id', 0) : 0;
                }

                if ($mnemo) {
                    switch ($mnemo) {
                        case 'documents':
                            // @deprecated
                            $url = "{$pathUploadFiles}{$mnemo}/" . ($parentId ? $parentId : session_id());
                            $deleteUrl = "/{$mnemo}/delete-file/";
                            $childrenDeleteUrl = '';
                            break;
                        default:
                            // Table entity
                            /** @var \Popov\Entity\Service\EntityService $serviceEntity */
                            $serviceEntity = $sm->get('EntityService');
                            $itemEntity = $serviceEntity->getOneItem($mnemo, 'mnemo');

                            // Mnemo указан checkout-booking, но по сути это все имеет отношение к checkout-document
                            // Поэтому папка сохранения документов и все сервисы и тд берутся именно оттуда
                            if (in_array($mnemo, ['order-sale', 'checkout-booking', 'logistics', 'store'])) {
                                $url = "/{$mnemo}/{$action}/{$parentId}/type/attach/" . $route->getParam('document');

                                switch ($mnemo) {
                                    case 'order-sale':
                                        /** @var \Popov\OrderSale\Service\OrderSaleService $serviceOrderSale */
                                        $serviceOrderSale = $sm->get('OrderSaleService');
                                        $pathUploadFiles = $serviceOrderSale->getPathUploadFiles();
                                        break;
                                    case 'logistics':
                                        /** @var \Popov\Logistics\Service\LogisticsService $serviceLogistics */
                                        $serviceLogistics = $sm->get('LogisticsService');
                                        $pathUploadFiles = $serviceLogistics->getPathUploadFiles();
                                        break;
                                    case 'checkout-booking':
                                        $url = "/checkout-document/download/{$parentId}/type/attach/".$route->getParam('document');
                                        /** @var \Agere\CheckoutDocument\Service\CheckoutDocumentService $serviceCheckoutDocument */
                                        $serviceCheckoutDocument = $sm->get('CheckoutDocumentService');
                                        $pathUploadFiles = $serviceCheckoutDocument->getPathUploadFiles();
                                        break;
                                    default:
                                        // @todo Дореалізувати DocumentController::downloadAction
                                        // Це необхідно щоб була можливість накладати права доступу на файли.
                                        // Зараз цей момент упущено і дозволено завантажувати всі документи по праямому шляху
                                        //'route' => '/:referrerController/:referrerAction/:itemId/download/:type',
                                        //$url = "/{$mnemo}/{$action}/{$parentId}/download/" . $route->getParam('document');

                                        /** @var DocumentService $documentService */
                                        $documentService = $sm->get('DocumentService');
                                        //$itemObject = $documentService->getPathUploadFiles($mnemo);

                                        $url = "/{$mnemo}/{$action}/{$parentId}/download/" . $route->getParam('document');
                                        //$pathUploadFiles = './var/documents/'. (new CamelCaseToUnderscore())->filter($mnemo) . '/';
                                        $pathUploadFiles = $documentService->getPathUploadFiles($mnemo) . '/';

                                }
                                $dir = $pathUploadFiles . $parentId . '/' . $route->getParam('document') . '/attach';
                                $showIfIssetFile = false;
                            } else {
                                $url = "{$pathUploadFiles}{$mnemo}/" . ($parentId ? $parentId : session_id());
                            }

                            $deleteUrl = "/files/delete-file/";
                            $childrenDeleteUrl = "/parent/{$itemEntity->getId()}";
                            break;
                    }

                    if (!$dir) {
                        $dir = $service->getFolderPublic() . $url;
                    } else {
                        $childrenDeleteUrl .= '/document/' . $route->getParam('document');
                    }

                    if ($parentId) {
                        switch ($mnemo) {
                            case 'documents':
                                /** @var \Popov\Documents\Service\DocumentFilesService $serviceFiles */
                                $serviceFiles = $sm->get('DocumentFilesService');

                                /** @var \Popov\Documents\Service\DocumentsService $serviceDocuments */
                                $serviceDocuments = $sm->get('DocumentsService');
                                $itemObject = $serviceDocuments->getOneItem($parentId);
                                break;
                            default:
                                /** @var \Popov\Files\Service\FilesService $serviceFiles */
                                $serviceFiles = $sm->get('FilesService');

                                $itemObject = $itemEntity;
                                break;
                        }
                    }
                }
                // END Set config params


                if ($request->isPost()) {
                    // Access to page for current user
                    $responseEvent = $service->index(__CLASS__, []);
                    $message = $responseEvent->first()['message'];
                    // END Access to page for current user

                    if ($message) {
                        $result = new JsonModel([
                            'files' => [['error' => $message]],
                        ]);
                    } else {
                        $files = $request->getFiles()->toArray();

                        // Upload or (upload and save) files
                        $uploadFiles = $service->save($files, $mnemo, $parentId, $url, $deleteUrl, $dir, $serviceFiles, $itemObject, $childrenDeleteUrl);

                        $result = new JsonModel($uploadFiles);
                    }
                } else if ($request->isGet()) {
                    // Set files items
                    $items = [];

                    if (! is_null($serviceFiles)) {
                        $items = $serviceFiles->getItemsCollection($mnemo, $parentId);
                    }

                    if (! $items) {
                        // Session Module FileUpload
                        $params = [
                            'locator' => $sm,
                            'mnemo' => $mnemo,
                            'id' => $referrerRouteMatch->getParam('id'),
                        ];

                        $responseEvent = $service->loadFileSession(__CLASS__, $params);
                        $items = $responseEvent->first();
                        // END Session Module FileUpload
                    }
                    // END Set files items

                    $data['files'] = $this->_initDataJson($items, $mnemo, $url, $deleteUrl, $dir, $childrenDeleteUrl, $showIfIssetFile);

                    $result = new JsonModel($data);
                }
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
    private function _initDataJson($items, $mnemo, $url, $deleteUrl, $dir, $childrenDeleteUrl, $showIfIssetFile = true)
    {
        $data = [];
        $i = 0;

        if ($items) {
            $filesHttp = new AgereFileHttp();
            $files = $filesHttp->getFiles($dir);

            foreach ($items as $key => $item) {
                $creator = '';
                $dateCreate = '';

                if (is_object($item)) {
                    $id = $item->getId();
                    $name = $item->getName();

                    $creatorMethod = 'getCreator';
                    if (method_exists($item, $creatorMethod) && $item->{$creatorMethod}()) {
                        $creator = $item->$creatorMethod()->getEmail();
                    }

                    $dateCreateMethod = 'getDateCreate';
                    if (method_exists($item, $dateCreateMethod) && $item->{$dateCreateMethod}()) {
                        $dateCreate = $item->{$dateCreateMethod}()->format('d.m.Y H:i:s');
                    }
                } else {
                    $id = $key;
                    $name = $item;
                }

                $data[$i] = [
                    'url' => "{$url}/{$name}",
                    'name' => $name,
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