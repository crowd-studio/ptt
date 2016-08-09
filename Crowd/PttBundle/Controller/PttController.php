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

use Crowd\PttBundle\Form\PttForm;
use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttCache;

class PttController extends Controller
{

    private $entityName;
    private $className;
    private $bundle;
    private $repositoryName;
    private $fields;
    private $self;

    //LIST
    public function listAction(Request $request, $page){
        $this->deleteTemp();
        list($allowed, $message) = $this->allowAccess('listAction');
        if (!$allowed) {
            $this->get('session')->getFlashBag()->add('error_persist', $message);
            return $this->_renderTemplateForActionInfo('error', array(
                'page' => array(
                    'title' => $this->get('pttTrans')->trans('error')
                    )
                ));
        }

        $response = $this->_order($request);
        if ($response) {
            return $response;
        }

        $response = $this->_filter($request);
        if ($response) {
            return $response;
        }

        $em = $this->get('doctrine')->getManager();

        if ($this->isSortable()) {
            $order = array('_order', $this->orderList());
        } else {
            $order = $this->_currentOrder($request);
        }

        $filters = $this->_currentFilters($request);

        list($pagination, $offset, $limit) = $this->_paginationForPage($page, $this->_repositoryName(), $filters);
        $entities = $this->_buildQuery($this->_repositoryName(), $filters, $order, $limit, $offset, $page);

        return $this->_renderTemplateForActionInfo('list', array(
            'entityInfo' => $this->entityInfo(),
            'fields' => $this->fieldsToList(),
            'rows' => $entities,
            'pagination' => $pagination,
            'filters' => $this->fieldsToFilter(),
            'page' => array(
                'title' => $this->listTitle()
                ),
            'sortable' => $this->isSortable(),
            'csvexport' => $this->isCsvExport(),
            'copy' => $this->isCopy()
            ));
    }

    //EDIT
    public function editAction(Request $request, $id){
        if ($id == null) {
            $entity = $this->_initEntity();
        } else {
            $em = $this->get('doctrine')->getManager();
            $entity = $em->getRepository($this->_repositoryName())->find($id);
            if ($entity == null) {
                throw $this->createNotFoundException($this->get('pttTrans')->trans('the_entity_does_not_exist', $this->_entityInfoValue('lowercase')));
            }
        }

        list($allowed, $message) = $this->allowAccess('editAction', $entity);
        if (!$allowed) {
            $this->get('session')->getFlashBag()->add('error_persist', $message);
            return $this->_renderTemplateForActionInfo('error', array(
                        'page' => array(
                            'title' => $this->get('pttTrans')->trans('error')
                            )
                        ));
        }

        $pttForm = $this->get('pttForm');
        
        $pttForm->setEntity($entity); // on es crea el ppttEntityInfo
        $pttForm->setTotalData($this->_totalEntities($this->_repositoryName()));


        if ($request->getMethod() == 'POST') {

            if ($pttForm->isValid()) {
                
                $pttForm->save();

                $this->afterSave($entity);
                $this->flushCache($entity);

                $this->get('session')->getFlashBag()->add('success', $pttForm->getSuccessMessage());

                $this->self = $this->get('session')->get('self');
                if($this->self == 1){
                    return $this->redirect($this->generateUrl($this->urlPath() . '_edit', array('id' => $id, 'self' => 1)));
                } else {
                    if ($id == null && $request->get('another') != null) {
                        return $this->redirect($this->generateUrl($this->urlPath() . '_edit'));
                    } else {
                        return $this->redirect($this->generateUrl($this->urlPath() . '_list'));
                    }
                }
                
            } else {
                $this->get('session')->getFlashBag()->add('error', $pttForm->getErrorMessage());
            }
        } else {
            $this->self = false;
            $this->self = $request->query->get('self');
            $this->get('session')->set('self', $this->self);
        }

        $this->deleteTemp();
        return $this->_renderTemplateForActionInfo('edit', array(
            'entityInfo' => $this->entityInfo(),
            'form' => $pttForm,
            'cancel' => $this->self,
            'page' => array(
                'title' => $this->editTitle($id)
                )
            ));
    }

    //DELETE
    public function deleteAction(Request $request, $id){
        $em = $this->get('doctrine')->getManager();
        $entity = $em->getRepository($this->_repositoryName())->find($id);
        if ($entity == null) {
            throw $this->createNotFoundException('The ' . $this->_entityInfoValue('lowercase') . ' does not exist');
        }

        list($allowed, $message) = $this->allowAccess('deleteAction', $entity);
        if (!$allowed) {
            $this->get('session')->getFlashBag()->add('error_persist', $message);
            return $this->_renderTemplateForActionInfo('error', array(
                        'page' => array(
                            'title' => $this->get('pttTrans')->trans('error')
                            )
                        ));
        }

        list($valid, $message) = $this->continueWithDeletion($entity);
        if ($valid) {

            $this->beforeDeletion($entity);

            $transClassName = $this->_className() . 'Trans';
            if (class_exists($transClassName)) {
                $transEntities = $em->getRepository($this->_repositoryName() . 'Trans')->findBy(array('relatedId' => $entity->getPttId()));
                foreach ($transEntities as $transEntity) {
                    $em->remove($transEntity);
                }
            }

            $this->flushCache($entity);

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $this->get('pttTrans')->trans('the_entity_was_deleted', $this->_entityInfoValue('lowercase')));
        } else {
            $this->get('session')->getFlashBag()->add('error', $message);
        }

        return $this->redirect($this->generateUrl($this->urlPath() . '_list'));

    }

    //COPY
    public function copyAction(Request $request){
        $title = $request->get('title');
        $id = $request->get('id');

        if ($title != '') {
            $em = $this->get('doctrine')->getManager();
            $entity = $em->getRepository($this->_repositoryName())->find($id);
            if ($entity == null) {
                throw $this->createNotFoundException('The ' . $this->_entityInfoValue('lowercase') . ' does not exist');
            }
            $entityB = clone $entity;

            $entityB->setTitle($title);

            if(method_exists($entityB, "set_Order")){
                $entityB->set_Order(-1);
            }


            $em->persist($entityB);
            $em->flush(); // Fem flush per obtenir el nou id

            if(method_exists($entityB, "getCopy")){
                foreach ($entity->getCopy() as $label => $type) {
                    switch ($type) {
                        case 'file':
                            # code...
                            break;
                        
                        default:
                            $findBy = 'findBy' . ucfirst($type);
                            $set = 'set' . ucfirst($type);
                            $related = $em->getRepository($this->_bundle() . ':' . $label)->$findBy($id);
                            foreach ($related as $key => $value) {
                                $relatedEntity = clone $value;
                                $relatedEntity->$set($entityB->getPttId());
                                $em->persist($relatedEntity);
                            }
                            break;
                    }
                }
            }

            $em->flush();

            // Copiem les entitats traduibles FALTA PROVAR!
            $transClassName = $this->_className() . 'Trans';
            if (class_exists($transClassName)) {
                $trans = $em->getRepository($this->_bundle() . ':' . $transClassName)->findByRelatedId($id);
                foreach ($trans as $key => $value) {
                    $transEntity = clone $value;
                    $transEntity->setRelatedId($entityB->getPttId());
                    $em->persist($transEntity);
                }
            }

            $em->flush();
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Title must be fill');
        }

        return $this->redirect($this->generateUrl($this->urlPath() . '_list'));
    }

    //ORDER
    public function orderAction(Request $request){
        if ($request->getMethod() == 'PUT') {
            $fields = JSON_decode($request->getContent());
            $em = $this->get('doctrine')->getManager();
            $response = array();


            $cache = new PttCache();
            $cache->removeAll();

            try {
                foreach($fields as $field){
                    $entity = $em->getRepository($this->_repositoryName())->find($field->id);
                    $entity->set_Order($field->_order);
                    $cache->remove($this->_entityName().$field->id);
                }
                $em->flush();  
                $response['success'] = true;
             } catch (Exception $e) {
                $response['success'] = false;
             }
            return new JsonResponse($response);
        } else {
            return $this->redirect($this->generateUrl($this->urlPath() . '_list'));
        }
    }

    //LAST
    public function lastAction(Request $request){
        $limit = $request->get('limit');
        $result = array(); 
        try {
            $objects = $this->_buildQueryLast($this->_repositoryName(), $limit);
            foreach ($objects as $object) {
                $result[] = array('id' => $object->getId(), 'title' => $object->getTitle());
            }

        } catch(Exception $e){
            $result = array('results' => 'Fail ' . $e);
        }
        
        // return new JsonResponse();
        return new JsonResponse($result);
    }

    //SEARCH
    public function searchAction(Request $request){
        $limit = $request->get('page_limit');
        $query = $request->get('q');
        $result = array(); 
        try {
            $objects = $this->_buildQuery($this->_repositoryName(), ['title' => $query], ['title', 'asc'], $limit, 0, 0);
            foreach ($objects as $object) {
                $result[] = array('id' => $object->getId(), 'title' => $object->getTitle());
            }

        } catch(Exception $e){
            $result = array('results' => 'Fail ' . $e);
        }
        
        // return new JsonResponse();
        return new JsonResponse($result);
    }

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

    protected function listTitle(){
        return $this->get('pttTrans')->trans('list') . ' ' . $this->_entityInfoValue('plural');
    }

    protected function afterSave($entity){}

    protected function flushCache($entity){}

    protected function deleteTemp(){
        $dir = BASE_DIR . "web/tmp/"; 
        $handle = opendir($dir); 

        while ($file = readdir($handle))  {   
            if (is_file($dir.$file)) { 
                unlink($dir.$file); 
            }
        } 
    }

    protected function editTitle($id){
        $entityInfo = $this->entityInfo();
        $title = ($id != null) ? $this->get('pttTrans')->trans('edit') . ' ' : $this->get('pttTrans')->trans('create') . ' ';
        $title .= $this->_entityInfoValue('lowercase');
        return $title;
    }

    protected function fieldsToList(){
        return array(
            'title' => $this->get('pttTrans')->trans('title'),
            );
    }

    protected function orderList(){
        return 'asc';
    }

    protected function enableFilters(){
        return false;
    }

    protected function fieldsToFilter(){
        if (!$this->enableFilters()) {
            return array();
        }

        return array(
            'title' => array(
                        'label' => $this->get('pttTrans')->trans('title'),
                        'type' => 'text'
                        ),
            );
    }

    protected function continueWithDeletion($entity){
        return array(
            true,
            $this->get('pttTrans')->trans('the_entity_couldnt_be_deleted', $this->_entityInfoValue('lowercase'))
            );
    }

    protected function beforeDeletion($entity){}

    protected function entityInfo(){
        $entityName = $this->_entityName();

        return array(
            'simple' => $entityName,
            'lowercase' => strtolower($entityName),
            'plural' => $entityName . 's'
            );
    }

    protected function entityConfigurationInfo(){
        $entityName = $this->_entityName();

        return array(
            'entityName' => strtolower($entityName)
            );
    }

    protected function userIsRole($role){
        return ($this->getUser()->getRole() == $role);
    }

    protected function userRole(){
        return $this->getUser()->getRole();
    }

    protected function allowAccess($methodName, $entity = false){
        return array(true, $this->get('pttTrans')->trans('the_current_user_cant_access'));
    }

    protected function urlPath(){
        return strtolower($this->_entityName());
    }

    protected function _buildQuery($repositoryName, $filters, $order, $limit, $offset, $page){
        $em = $this->get('doctrine')->getManager();

        $dql = 'select ptt from ' . $this->_repositoryName() . ' ptt';

        if (count($filters)) {
            $dql .= ' where ';
        }

        $filterDql = array();

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

        $pagination = array(
            'currentPage' => $page,
            'numberOfPages' => $offset
            );

        return array($pagination, ($page - 1) * $offset, $limit);
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
            $name = $this->_entityName() . '-' . $field;
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
        $filters = array();
        foreach ($fields as $key => $field) {
            $name = 'filter-' . strtolower($this->_entityName()) . '-' . $key;
            if ($cookies->has($name) && trim($cookies->get($name, '')) != '') {
                $filters[$key] = $cookies->get($name);
            }
        }
        return $filters;
    }

    protected function _order(Request $request){
        if ($request->get('order') != null) {

            $cookies = $request->cookies;
            $name = $this->_entityName() . '-' . $request->get('order');
            if ($cookies->has($name)) {
                $oldValue = $cookies->get($name);
                $value = ($oldValue == 'asc') ? 'desc' : 'asc';
            } else {
                $value = $this->orderList();
            }

            $url = $this->generateUrl(strtolower($this->_entityName()) . '_list');
            $response = new RedirectResponse($url);

            $allCookies = $cookies->all();
            foreach ($allCookies as $cookie => $cookieValue) {
                if (strpos($cookie, $this->_entityName()) !== false && $cookie != $name) {
                    $response->headers->clearCookie($cookie);
                }
            }

            $response->headers->setCookie(new Cookie($name, $value, time() + (315360000))); // 10 * 365 * 24 * 60 * 60 = 315360000
            return $response;
        } else {
            return false;
        }
    }

    protected function _filter(Request $request){
        $filters = $this->fieldsToFilter();

        $url = $this->generateUrl(strtolower($this->_entityName()) . '_list');
        $response = new RedirectResponse($url);

        if ($request->getMethod() == 'POST' && count($filters)) {
            $cookies = $request->cookies;
            foreach ($filters as $key => $filter) {
                $fieldName = 'filter-' . strtolower($this->_entityName()) . '-' . $key;
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
                    $fieldName = 'filter-' . strtolower($this->_entityName()) . '-' . $key;
                    $response->headers->clearCookie($fieldName);
                }
                return $response;
            } else {
                return false;
            }
        }
    }

    protected function _renderTemplateForActionInfo($action, $info = array()){
        $filename = $action . '.html.twig';

        try {
            $kernel = $this->container->get('kernel');
            $filePath = $kernel->locateResource('@' . $this->_bundle() . '/Resources/views/' . $this->_entityName() . '/' . $filename);
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
        return new $className();
    }

    protected function _className(){
        if ($this->className == null) {
            $controllerClass = get_class($this);
            $controllerClassArr = explode('\\', $controllerClass);

            $entityClassArr = array();
            foreach ($controllerClassArr as $controllerClassItem) {
                $entityClassArr[] = $controllerClassItem;
                if (strpos($controllerClassItem, 'Bundle') !== false) {
                    break;
                }
            }
            $entityClassArr[] = 'Entity';
            $entityClassArr[] = $this->_entityName();
            $this->className = implode('\\', $entityClassArr);
        }
        return $this->className;
    }

    protected function _entityName(){
        if ($this->entityName == null) {
            $fileArr = explode('\\', get_class($this));
            $filename = end($fileArr);
            $this->entityName = PttUtil::extractControllerName($filename);
        }
        return $this->entityName;
    }

    protected function _bundle(){
        if ($this->bundle == null) {
            $this->bundle = PttUtil::bundle($this->_className(), '\\');
        }
        return $this->bundle;
    }

    protected function _repositoryName(){
        if ($this->repositoryName == null) {
            $this->repositoryName = $this->_bundle() . ':' . $this->_entityName();
        }
        return $this->repositoryName;
    }

    public function generateCSV($query, $name){
        $em = $this->container->get('doctrine')->getManager();
        $query = $em->createQuery($query);
        $data = $query->getResult(); 

        $filename = $name . "_".date("Y_m_d_His").".csv"; 
        
        $response = $this->render('PttBundle:Default:csv.html.twig', array('data' => $data)); 

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Description', 'Submissions Export');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response; 
    }
}