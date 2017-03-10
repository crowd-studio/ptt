<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Doctrine\Common\Annotations\AnnotationReader;

use Crowd\PttBundle\Form\PttForm;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttCache;
use Crowd\PttBundle\Annotations\PttAnnotation;

class PttController extends Controller
{
    private $entityName;
    private $className;
    private $bundle;
    private $repositoryName;
    private $fields;
    private $self;

    /**
     * @Route("{entity}/list/{page}", name="list");
     * @Template()
     */
    public function listAction(Request $request, $entity, $page = null){
        $this->deleteTemp();
        $this->entityName = $entity;

        $response = $this->_order($request);
        if ($response) {
            return $response;
        }

        $response = $this->_filter($request, $entity);
        if ($response) {
            return $response;
        }

        $em = $this->get('doctrine')->getManager();

        if ($this->isSortable()) {
            $order = [
                '_order',
                $this->orderList()
            ];
        } else {
            $order = $this->_currentOrder($request);
        }

        $filters = $this->_currentFilters($request);

        list($pagination, $offset, $limit) = $this->_paginationForPage($page, $this->_repositoryName(), $filters);
        $entities = $this->_buildQuery($this->_repositoryName(), $filters, $order, $limit, $offset, $page);

        return $this->_renderTemplateForActionInfo('list', [
            'entityInfo' => $this->entityInfo(),
            'fields' => $this->fieldsToList(),
            'rows' => $entities,
            'pagination' => $pagination,
            'filters' => $this->fieldsToFilter(),
            'page' => [
                'title' => $this->listTitle()
            ],
            'sortable' => $this->isSortable(),
            'csvexport' => $this->isCsvExport(),
            'copy' => $this->isCopy()
        ]);
    }

    /**
     * @Route("/{entity}/edit/{id}", name="edit");
     * @Route("/{entity}/create", name="create");
     * @Template()
     */
    public function editAction(Request $request, $entity, $id = null)
    {
        $this->entityName = ucfirst($entity);
        if ($id == null) {
            $saveEntity = $this->_initEntity();
        } else {
            $em = $this->get('doctrine')->getManager();
            $saveEntity = $em->getRepository($this->_repositoryName())->find($id);
            if ($saveEntity == null) {
                throw $this->createNotFoundException($this->get('pttTrans')->trans('the_entity_does_not_exist', $this->_entityInfoValue('lowercase')));
            }
        }

        $pttForm = $this->get('pttForm');
        $pttForm->setEntity($saveEntity); // on es crea el ppttEntityInfo

        if ($request->getMethod() == 'POST') {
            if ($pttForm->isValid()) {
                $pttForm->save();

                $this->flushCache($saveEntity);
                $this->get('session')->getFlashBag()->add('success', $pttForm->getSuccessMessage());

                $this->self = $this->get('session')->get('self');
                if($this->self == 1){
                    return $this->redirect($this->generateUrl('edit', ['entity' => $entity, 'id' => $id, 'self' => 1]));
                } else {
                    if ($id == null && $request->get('another') != null) {
                        return $this->redirect($this->generateUrl('edit', ['entity' => $entity, 'id' => $id]));
                    } else {
                        return $this->redirect($this->generateUrl('list', ['entity' => $entity]));
                    }
                }
            } else {
                $this->get('session')->getFlashBag()->add('error', $pttForm->getErrorMessage());
            }
        } else {
            $this->self = $request->query->get('self');
            $this->get('session')->set('self', $this->self);
        }

        $this->deleteTemp();
        return $this->_renderTemplateForActionInfo('edit', [
            'entityInfo' => $this->entityInfo(),
            'form' => $pttForm,
            'cancel' => $this->self,
            'page' => [
                'title' => $this->editTitle($id)
                ]
        ]);
    }

    /**
     * @Route("/{entity}/delete/{id}", name="delete");
     * @Template()
     */
    public function deleteAction(Request $request, $entity, $id){
        $this->entityName = ucfirst($entity);
        $em = $this->get('doctrine')->getManager();
        $deleteEntity = $em->getRepository($this->_repositoryName())->find($id);
        if ($deleteEntity == null) {
            throw $this->createNotFoundException('The ' . $this->_entityInfoValue('lowercase') . ' does not exist');
        }

        list($valid, $message) = $this->continueWithDeletion($deleteEntity);
        if ($valid) {
            $this->beforeDeletion($deleteEntity);
            $this->flushCache($deleteEntity);

            $em->remove($deleteEntity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $this->get('pttTrans')->trans('the_entity_was_deleted', $this->_entityInfoValue('lowercase')));
        } else {
            $this->get('session')->getFlashBag()->add('error', $message);
        }

        return $this->redirect($this->generateUrl('list', ['entity' => $entity]));
    }

    /**
     * @Route("/{entity}/copy/{id}", name="copy");
     * @Template()
     */
    public function copyAction(Request $request, $entity, $id){
        $this->entityName = ucfirst($entity);
        $em = $this->get('doctrine')->getManager();
        $entity = $em->getRepository($this->_repositoryName())->find($id);
        if ($entity == null) {
            throw $this->createNotFoundException('The ' . $this->_entityInfoValue('lowercase') . ' does not exist');
        }

        $entityB = clone $entity;
        $em->persist($entityB);
        $em->flush();
        return $this->redirect($this->generateUrl('list', ['entity' => $entity]));
    }

    /**
     * @Route("/{entity}/order/", name="order");
     * @Template()
     */
    public function orderAction(Request $request, $entity){
        $this->entityName = ucfirst($entity);
        if ($request->getMethod() == 'PUT') {
            $fields = JSON_decode($request->getContent());
            $em = $this->get('doctrine')->getManager();
            $response = [];


            $cache = new PttCache();
            $cache->removeAll();

            try {
                foreach($fields as $field){
                    $entity = $em->getRepository($this->_repositoryName())->find($field->id);
                    $entity->set_Order($field->_order);
                    $cache->remove($this->entityName.$field->id);
                }
                $em->flush();
                $response['success'] = true;
             } catch (Exception $e) {
                $response['success'] = false;
             }
            return new JsonResponse($response);
        } else {
            return $this->redirect($this->generateUrl('list', ['entity' => $entity]));
        }
    }

    /**
     * @Route("/{entity}/last", name="last");
     * @Template()
     */
    public function lastAction(Request $request, $entity){
        $this->entityName = ucfirst($entity);
        $limit = $request->get('limit');
        $result = [];
        try {
            $objects = $this->_buildQueryLast($this->_repositoryName(), $limit);
            foreach ($objects as $object) {
                $result[] = [
                    'id' => $object->getId(),
                    'title' => $object->getTitle()
                ];
            }

        } catch(Exception $e){
            $result = ['results' => 'Fail ' . $e];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/{entity}/search", name="search");
     * @Template()
     */
    public function searchAction(Request $request, $entity){
        $this->entityName = ucfirst($entity);
        $limit = $request->get('page_limit');
        $query = $request->get('q');
        $result = [];
        try {
            $objects = $this->_buildQuery($this->_repositoryName(), ['title' => $query], ['title', 'asc'], $limit, 0, 0);
            foreach ($objects as $object) {
                $result[] = [
                    'id' => $object->getId(),
                    'title' => $object->getTitle()
                ];
            }

        } catch(Exception $e){
            $result = ['results' => 'Fail ' . $e];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/s3-sign", name="sign");
     * @Template()
     */
     public function signAction(Request $request){
        $secret = PttUtil::pttConfiguration('s3')['secretKey'];
        $to_sign = $request->query->get('to_sign');
 
        if(isset($to_sign)){

            $hmac_sha1 = hash_hmac('sha1',$to_sign,$secret,true);
            $signature = base64_encode($hmac_sha1);
            $response = new Response($signature, 200);
            $response->headers->set('Content-Type', 'text/HTML');

            return $response;
        } else {
            return new Response('Missing to_sign param', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/login/", name="admin_login")
     * @Template()
     */
    public function loginAction(Request $request)
    {
        $helper = $this->get('security.authentication_utils');

        return $this->render('AdminBundle:Login:login.html.twig', [
            'last_username' => $helper->getLastUsername(),
            'error'         => $helper->getLastAuthenticationError(),
            'keymap'        => ''
        ]);
    }

    /**
     * @Route("/", name="admin_router")
     */
    public function routerAction(Request $request)
    {
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')){
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $configuration = PttUtil::pttConfiguration();
            if (isset($configuration['admin']) && isset($configuration['admin']['sidebar'])) {
                return $this->redirect($this->generateUrl($configuration['admin']['default_url'], ['entity' => $configuration['admin']['default_entity']]));
            } else {
                return $this->redirect($this->generateUrl('list', ['entity' => 'user']));
            }
        } else {
            return $this->redirect($this->generateUrl('admin_login'));
        }
    }

    // public function generateCSV($query, $name){
    //     $em = $this->container->get('doctrine')->getManager();
    //     $query = $em->createQuery($query);
    //     $data = $query->getResult();

    //     $filename = $name . "_".date("Y_m_d_His").".csv";

    //     $response = $this->render('PttBundle:Default:csv.html.twig', array('data' => $data));

    //     $response->setStatusCode(200);
    //     $response->headers->set('Content-Type', 'text/csv');
    //     $response->headers->set('Content-Description', 'Submissions Export');
    //     $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
    //     $response->headers->set('Content-Transfer-Encoding', 'binary');
    //     $response->headers->set('Pragma', 'no-cache');
    //     $response->headers->set('Expires', '0');

    //     return $response;
    // }

    //SHOULD CREATE DEFAULT METHODS
    //list, create, edit, delete
    public function shouldCreateDefaultMethods(){
        return true;
    }

    //THE CONTROLLER USES ENTITY
    public function usesEntityWithSameName(){
        return true;
    }

    // Indica si la llista es pot ordenar mitjançant Drag&Drop
    protected function isSortable(){
        return method_exists($this->_initEntity(), "get_Order");
    }

    protected function isCsvExport(){
        return method_exists($this->_initEntity(), "getCsvExport");
    }

    protected function isCopy(){
        return method_exists($this->_initEntity(), "getCopy");
    }

    protected function afterSave($entity){
        $entity->afterSave($entity);
    }

    protected function flushCache($entity){
        $entity->flushCache($entity);
    }

    protected function deleteTemp(){
        $dir = __DIR__ . "/../../../../../../web/tmp/";
        $handle = opendir($dir);

        while ($file = readdir($handle))  {
            if (is_file($dir.$file)) {
                unlink($dir.$file);
            }
        }
    }

    protected function listTitle(){
        return $this->get('pttTrans')->trans('list') . ' ' . $this->_entityInfoValue('plural');
    }

    protected function editTitle($id){
        return $this->get('pttTrans')->trans(($id != null) ? 'edit' : 'create') . ' ' . $this->_entityInfoValue('lowercase');
    }

    protected function fieldsToList(){
        $fields = $this->_initEntity()->fieldsToList();
        return ($fields) ? $fields : ['title' => $this->get('pttTrans')->trans('title')];
    }

    protected function orderList(){
        return $this->_initEntity()->orderList();
    }

    protected function enableFilters(){
        return $this->_initEntity()->enableFilters();
    }

    protected function fieldsToFilter(){
        if($this->enableFilters()){
            $fields = $this->_initEntity()->fieldsToFilter();
            if($fields){
                return $fields;
            } else {
                return [
                    'title' => [
                        'label' => $this->get('pttTrans')->trans('title'),
                        'type' => 'text'
                    ]
                ];
            }
        } else {
            return [];
        }

    }

    protected function continueWithDeletion($entity){
        return [
            true,
            $this->get('pttTrans')->trans('the_entity_couldnt_be_deleted', $this->_entityInfoValue('lowercase'))
        ];
    }

    protected function beforeDeletion($entity){
    }

    protected function entityInfo(){
        return $this->_initEntity()->entityInfo($this->entityName);
    }

    protected function entityConfigurationInfo(){
        return [
            'entityName' => strtolower($this->entityName)
        ];
    }

    protected function userIsRole($role){
        return ($this->getUser()->getRole() == $role);
    }

    protected function userRole(){
        return $this->getUser()->getRole();
    }

    protected function allowAccess($methodName, $entity = false){
        return [
            true,
            $this->get('pttTrans')->trans('the_current_user_cant_access')
        ];
    }

    protected function urlPath(){
        return strtolower($this->entityName);
    }

    protected function _buildQuery($repositoryName, $filters, $order, $limit, $offset, $page){
        $em = $this->get('doctrine')->getManager();

        $dql = 'select ptt from ' . $this->_repositoryName() . ' ptt';

        if (count($filters)) {
            $dql .= ' where ';
        }

        $filterDql = [];

        foreach ($filters as $key => $value) {
            $filterDql[] = 'ptt.' . $key . ' like :' . $key;
        }

        $dql .= implode(' and ', $filterDql);

        $dql .= ' order by ptt.' . $order[0] . ' ' . $order[1];

        $query = $em->createQuery($dql);

        foreach ($filters as $key => $value) {
            $query->setParameter($key, '%' . $value . '%');
        }

        if($limit > 0){
            if($offset > 0){$query->setFirstResult(($page - 1) * $limit);}
            $query->setMaxResults($limit);
        }

        $results = $query->getResult();

        return $results;
    }

    protected function _buildQueryLast($repositoryName, $limit){
        $em = $this->get('doctrine')->getManager();

        $dql = 'select ptt FROM ' . $this->_repositoryName() . ' ptt ORDER BY ptt.updateDate DESC';

        $query = $em->createQuery($dql);
        $query->setMaxResults($limit);
        $results = $query->getResult();

        return $results;
    }

    protected function _paginationForPage($page, $repositoryName, $filters){
        $fields = $this->_fields();
        $total = $this->_totalEntities($repositoryName, $filters);

        if($this->isSortable()) {
            $offset = 0;
            $limit = 0;
        } else {
            $offset = ceil($total / $fields['admin']['numberOfResultsPerPage']);
            $limit = $fields['admin']['numberOfResultsPerPage'];
        }

        $pagination = [
            'currentPage' => $page,
            'numberOfPages' => $offset
        ];

        return [
            $pagination,
            ($page - 1) * $offset,
            $limit
        ];
    }

    protected function _totalEntities($repositoryName, $filters = null){
        $em = $this->get('doctrine')->getManager();

        $query = $em->createQueryBuilder()
              ->select('count(p.id)')
              ->from($repositoryName, 'p');

        if ($filters){
            foreach ($filters as $key => $value) {
                $query->andWhere('p.' . $key . ' like :' . $key);
                $query->setParameter($key, '%' . $value . '%');
            }
        }

        $total = $query->getQuery()->getSingleScalarResult();
        return $total;
    }

    protected function _entityInfoValue($value){
        $info = $this->entityInfo();
        return (isset($info[$value])) ? $info[$value] : '';
    }

    protected function _currentOrder(Request $request){
        $cookies = $request->cookies;
        $fields = $this->fieldsToList();
        foreach ($fields as $field => $label) {
            $name = $this->entityName . '-' . $field;
            if ($cookies->has($name)) {
                return array($field, $cookies->get($name));
            }
        }
        $fieldsKeys = array_keys($fields);
        return array($fieldsKeys[0], $this->orderList());
    }

    protected function _currentFilters(Request $request){
        $cookies = $request->cookies;
        $fields = $this->fieldsToFilter();
        $filters = [];
        foreach ($fields as $key => $field) {
            $name = 'filter-' . strtolower($this->entityName) . '-' . $key;
            if ($cookies->has($name) && trim($cookies->get($name, '')) != '') {
                $filters[$key] = $cookies->get($name);
            }
        }
        return $filters;
    }

    protected function _order(Request $request){
        if ($request->get('order') != null) {

            $cookies = $request->cookies;
            $name = $this->entityName . '-' . $request->get('order');
            if ($cookies->has($name)) {
                $oldValue = $cookies->get($name);
                $value = ($oldValue == 'asc') ? 'desc' : 'asc';
            } else {
                $value = $this->orderList();
            }

            $url = $this->generateUrl('list', ['entity' => strtolower($this->entityName)]);
            $response = new RedirectResponse($url);

            $allCookies = $cookies->all();
            foreach ($allCookies as $cookie => $cookieValue) {
                if (strpos($cookie, $this->entityName) !== false && $cookie != $name) {
                    $response->headers->clearCookie($cookie);
                }
            }

            $response->headers->setCookie(new Cookie($name, $value, time() + (315360000))); // 10 * 365 * 24 * 60 * 60 = 315360000
            return $response;
        } else {
            return false;
        }
    }

    protected function _filter(Request $request, $entity){
        $filters = $this->fieldsToFilter();

        $url = $this->generateUrl('list', ['entity' => $entity]);
        $response = new RedirectResponse($url);

        if ($request->getMethod() == 'POST' && count($filters)) {
            $cookies = $request->cookies;
            foreach ($filters as $key => $filter) {
                $fieldName = 'filter-' . strtolower($this->entityName) . '-' . $key;
                $value = trim($request->get($fieldName, ''));
                if ($value == '' && $cookies->has($fieldName)) {
                    $response->headers->clearCookie($fieldName);
                } else {
                    $response->headers->setCookie(new Cookie($fieldName, $value, time() + (315360000))); // 10 * 365 * 24 * 60 * 60 = 315360000
                }
            }
            return $response;
        } else {
            if ($request->get('filter', false) == 'reset') {
                foreach ($filters as $key => $filter) {
                    $fieldName = 'filter-' . strtolower($this->entityName) . '-' . $key;
                    $response->headers->clearCookie($fieldName);
                }
                return $response;
            } else {
                return false;
            }
        }
    }

    protected function _renderTemplateForActionInfo($action, $info = []){
        $filename = $action . '.html.twig';

        try {
            $kernel = $this->container->get('kernel');
             $filePath = $kernel->locateResource('@' . $this->_bundle() . '/Resources/views/' . ucfirst($this->entityName) . '/' . $filename);
            $template = $this->_repositoryName() . ':' . $action . '.html.twig';

        } catch (\Exception $e) {
            $defaultFileDir = __DIR__ . '/../Resources/views/Default/';
            $filePath = $defaultFileDir . $filename;
            if (file_exists($filePath) && is_file($filePath)) {
                $template = 'PttBundle:Default:' . $filename;
            } else {
                throw new \Exception('The requested template does not exist');
            }
        }

        if (!isset($info['entityConfigurationInfo'])) {
            $info['entityConfigurationInfo'] = $this->entityConfigurationInfo();
        }

        $info["keymap"] = PttUtil::pttConfiguration('google')["key"];
        return $this->render($template, $info);
    }

    protected function _fields(){
        if ($this->fields == null) {
            $this->fields = PttUtil::pttConfiguration();
        }
        return $this->fields;
    }

    protected function _initEntity(){
        $className = $this->_className();

        // var_dump($className);die();
        return new $className();
    }

    protected function _className(){


        if ($this->className == null) {
            $entityClassArr[] = $this->_bundle();
            $entityClassArr[] = 'Entity';
            $entityClassArr[] = ucfirst($this->entityName);
            $this->className = implode('\\', $entityClassArr);
        }
        return $this->className;
    }

    protected function _bundle(){
        if($this->bundle == null){
            $this->bundle = PttUtil::pttConfiguration('bundles')[0]["bundle"];
        }
        return $this->bundle;
    }

    protected function _repositoryName(){
        if ($this->repositoryName == null) {
            $this->repositoryName = $this->_bundle() . ':' . ucfirst($this->entityName);
        }
        return $this->repositoryName;
    }

    private function _getAnnotation($field = false){
        $reader = new AnnotationReader();
        $class = $this->_className();

        $pttAnnotation = $reader->getClassAnnotation(new \ReflectionClass(new $class), PttAnnotation::class);
        if(!$pttAnnotation) {
            return false;
        }

        if ($field){
            return $pttAnnotation->$field;
        } else {
            return $pttAnnotation;
        }
    }
}
