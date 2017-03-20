<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Crowd\PttBundle\Util\PttUtil;

class PttController extends Controller
{
    private $entityName;
    private $className;
    private $bundle;
    private $repositoryName;
    private $pttServices;

    /**
     * @Route("{entity}/list/{page}", name="list");
     * @Template()
     */
    public function listAction(Request $request, $entity, $page = 1)
    {
        $this->deleteTemp();
        $this->entityName = $entity;

        $order = ($this->isSortable()) ? ['_order', 'asc'] : $this->_currentOrder($request);
        $filters = $this->_currentFilters($request);
        $limit = PttUtil::pttConfiguration('admin')['numberOfResultsPerPage'];

        $result = $this->_buildQuery($this->entityName, $filters, $order, $limit, $page);

        return $this->_renderTemplateForActionInfo('list', [
            'entityInfo' => $this->entityInfo(),
            'rows' => $result['content'],
            'fields' => $this->fieldsToList(),
            'pagination' => $result['pagination'],
            'activeFilters' => $filters,
            'filters' => $this->fieldsToFilter(),
            'order' => $order,
            'page' => [
                'title' => $this->listTitle(),
                'path' => 'list',
                'parameters' => [
                  'entity' => $entity,
                  'page' => $page
                ]
            ],
            'pttVersion' => PttUtil::pttVersion(),
            'sortable' => $this->isSortable(),
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
            $saveEntity = $this->getPttServices()->getOne($entity, $id);
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

                $route = ($id == null && $request->get('another') != null) ? $this->generateUrl('edit', ['entity' => $entity, 'id' => $id]) : $this->generateUrl('list', ['entity' => $entity]);
                return $this->redirect($route);
            } else {
                $this->get('session')->getFlashBag()->add('error', $pttForm->getErrorMessage());
            }
        }

        $this->deleteTemp();
        return $this->_renderTemplateForActionInfo('edit', [
            'entityInfo' => $this->entityInfo(),
            'form' => $pttForm,
            'page' => [
                'title' => $this->editTitle($id),
                'path' => $request->get('_route'),
                 'parameters' => [
                   'entity' => $entity,
                   'id' => $id
                ]
              ],
            'pttVersion' => PttUtil::pttVersion(),
        ]);
    }

    /**
     * @Route("/{entity}/delete/{id}", name="delete");
     * @Template()
     */
    public function deleteAction(Request $request, $entity, $id)
    {
        $this->entityName = ucfirst($entity);
        $deleteEntity = $this->getPttServices()->getOne($entity, $id);
        if ($deleteEntity == null) {
            throw $this->createNotFoundException('The ' . $this->_entityInfoValue('lowercase') . ' does not exist');
        }

        list($valid, $message) = $this->continueWithDeletion($deleteEntity);
        if ($valid) {
            $this->getPttServices()->remove($deleteEntity);
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
    public function copyAction(Request $request, $entity, $id)
    {
        $this->entityName = ucfirst($entity);
        $entityObj = $this->getPttServices()->getOne($entity, $id);
        if ($entityObj) {
            $entityB = clone $entityObj;
            $this->getPttServices()->create($entityB);
            return $this->redirect($this->generateUrl('list', ['entity' => $entity]));
        } else {
            throw $this->createNotFoundException('The ' . $this->_entityInfoValue('lowercase') . ' does not exist');
        }
    }

    /**
     * @Route("/{entity}/order", name="order");
     * @Template()
     */
    public function orderAction(Request $request, $entity)
    {
        $this->entityName = ucfirst($entity);
        if ($request->getMethod() == 'PUT') {
            $fields = JSON_decode($request->getContent());
            return new JsonResponse(['success' => $this->getPttServices()->order($entity, $fields)]);
        } else {
            return $this->redirect($this->generateUrl('list', ['entity' => $entity]));
        }
    }

    /**
     * @Route("/{entity}/last", name="last");
     * @Template()
     */
    public function lastAction(Request $request, $entity)
    {
        $this->entityName = ucfirst($entity);
        $limit = $request->get('limit');
        $result = [];

        $objects = $this->getPttServices()->getByPag($entity, [
            'order' => [['order' => 'updateDate', 'orderDir' => 'desc']],
            'limit' => $limit
          ])['content'];
        foreach ($objects as $object) {
            $result[] = [
                'id' => $object->getId(),
                'title' => $object->getTitle()
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/{entity}/search", name="search");
     * @Template()
     */
    public function searchAction(Request $request, $entity)
    {
        $this->entityName = ucfirst($entity);
        $limit = $request->get('page_limit');
        $query = $request->get('q');
        $result = [];

        try {
            $objects = $this->_buildQuery($entity, ['title' => $query], ['title', 'asc'], $limit, 0)['content'];
            foreach ($objects as $object) {
                $result[] = [
                    'id' => $object->getId(),
                    'title' => $object->getTitle()
                ];
            }
        } catch (Exception $e) {
            $result = ['results' => 'Fail ' . $e];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/s3-sign", name="sign");
     * @Template()
     */
     public function signAction(Request $request)
     {
         $secret = PttUtil::pttConfiguration('s3')['secretKey'];
         $to_sign = $request->query->get('to_sign');

         if (isset($to_sign)) {
             $hmac_sha1 = hash_hmac('sha1', $to_sign, $secret, true);
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
        if ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $configuration = PttUtil::pttConfiguration('admin');

            $route = (isset($configuration) && isset($configuration['sidebar'])) ? $this->generateUrl($configuration['default_url'], ['entity' => $configuration['default_entity']]) : $this->generateUrl('list', ['entity' => 'user']);
        } else {
            $route = $this->generateUrl('admin_login');
        }

        return $this->redirect($route);
    }

    // Indica si la llista es pot ordenar mitjançant Drag&Drop
    protected function isSortable()
    {
        return method_exists($this->_initEntity(), "get_Order");
    }

    protected function isCopy()
    {
        return method_exists($this->_initEntity(), "getCopy");
    }

    protected function flushCache($entity)
    {
        $entity->flushCache($entity);
    }

    protected function deleteTemp()
    {
        $dir = __DIR__ . "/../../../../../../web/tmp/";
        $handle = opendir($dir);

        while ($file = readdir($handle)) {
            if (is_file($dir.$file)) {
                unlink($dir.$file);
            }
        }
    }

    protected function listTitle()
    {
        return $this->get('pttTrans')->trans('list') . ' ' . $this->_entityInfoValue('plural');
    }

    protected function editTitle($id)
    {
        return $this->get('pttTrans')->trans(($id != null) ? 'edit' : 'create') . ' ' . $this->_entityInfoValue('lowercase');
    }

    protected function fieldsToList()
    {
        return $this->_initEntity()->fieldsToList();
    }

    protected function orderList()
    {
        return $this->_initEntity()->orderList();
    }

    protected function enableFilters()
    {
        return $this->_initEntity()->enableFilters();
    }

    protected function fieldsToFilter()
    {
        return $this->_initEntity()->fieldsToFilter();
    }

    protected function continueWithDeletion($entity)
    {
        return [
            true,
            $this->get('pttTrans')->trans('the_entity_couldnt_be_deleted', $this->_entityInfoValue('lowercase'))
        ];
    }

    protected function entityInfo()
    {
        return $this->_initEntity()->entityInfo($this->entityName);
    }

    protected function _buildQuery($entity, $filters, $order, $limit, $page)
    {
        $params = [];

        if ($filters) {
            $where = [];
            foreach ($filters as $key => $filter) {
                $keyArr = explode('-', $key);
                $where[] = ['column' => array_pop($keyArr), 'operator' => 'LIKE', 'value' => '%'.$filter.'%'];
            }

            $params['where'] = [['and' => $where]];
        }

        if ($order) {
            $params['order'] = [['order' => $order[0], 'orderDir' => $order[1]]];
        }

        if ($this->isSortable()) {
            $limit = 0;
            $page = 1;
        }

        $params['page'] = $page;
        $params['limit'] = $limit;

        return $this->getPttServices()->getByPag($entity, $params);
    }

    protected function _entityInfoValue($value)
    {
        $info = $this->entityInfo();
        return (isset($info[$value])) ? $info[$value] : '';
    }

    protected function _currentOrder(Request $request)
    {
        $session = $this->get('session');
        $orderName = $this->entityName . '-order';
        $field = $request->query->get('order');
        if ($field != null) {
            $old = $session->get($orderName);
            if ($old) {
                $direction = ($old[1] == 'asc') ? 'desc' : 'asc';
            } else {
                $direction = $this->orderList();
            }
            $order = [$field, $direction];
            $session->set($orderName, $order);
        } else {
            $order = $session->get($orderName);
        }

        return $order;
    }

    protected function _currentFilters(Request $request)
    {
        $session = $this->get('session');
        if ($request->getMethod() == 'POST') {
            $filters = $this->_setFilters($session, $request->request->all());
        } elseif ($request->query->get('filter', false) == 'reset') {
            $filters = $this->_clearFilters($session, $this->fieldsToFilter());
        } else {
            $filters = $this->_getFilters($session, $this->fieldsToFilter());
        }

        return $filters;
    }

    protected function _setFilters($session, $filters)
    {
        $activeFilters = [];
        foreach ($filters as $filter => $value) {
            $session->set($filter, $value);
            if ($value != '') {
                $activeFilters[$filter] = $value;
            }
        }

        return $activeFilters;
    }

    protected function _clearFilters($session, $fields)
    {
        foreach ($fields as $field) {
            $session->clear('filter-' . $this->entityName . '-' . $field['field']);
        }

        return false;
    }

    protected function _getFilters($session, $fields)
    {
        $activeFilters = [];
        foreach ($fields as $field) {
            $value = $session->get('filter-' . $this->entityName . '-' . $field['field']);
            if ($value != '') {
                $activeFilters[$field['field']] = $value;
            }
        }

        return $activeFilters;
    }

    protected function _renderTemplateForActionInfo($action, $info = [])
    {
        $filename = $action . '.html.twig';

        try {
            $kernel = $this->container->get('kernel');
            $filePath = $kernel->locateResource('@' . $this->_bundle() . '/Resources/views/' . ucfirst($this->entityName) . '/' . $filename);
            $template = $this->_repositoryName() . ':' . $action . '.html.twig';
        } catch (\Exception $e) {
            $defaultFileDir = __DIR__ . '/../Resources/views/'. ucfirst($action) . '/';
            $filePath = $defaultFileDir . $filename;
            if (file_exists($filePath) && is_file($filePath)) {
                $template = 'PttBundle:' . ucfirst($action) . ':' . $filename;
            } else {
                throw new \Exception('The requested template does not exist');
            }
        }

        if (!isset($info['entityConfigurationInfo'])) {
            $info['entityConfigurationInfo'] = ['entityName' => strtolower($this->entityName)];
        }

        $info["keymap"] = PttUtil::pttConfiguration('google')["key"];
        return $this->render($template, $info);
    }

    protected function getPttServices()
    {
        if (!$this->pttServices) {
            $this->pttServices = $this->get('pttservices');
        }
        return $this->pttServices;
    }

    protected function _initEntity()
    {
        $className = $this->_className();
        return new $className();
    }

    protected function _className()
    {
        if ($this->className == null) {
            $this->className = implode('\\', [$this->_bundle(), 'Entity', ucfirst($this->entityName)]);
        }
        return $this->className;
    }

    protected function _bundle()
    {
        if ($this->bundle == null) {
            $this->bundle = PttUtil::pttConfiguration('bundles')[0]["bundle"];
        }
        return $this->bundle;
    }

    protected function _repositoryName()
    {
        if ($this->repositoryName == null) {
            $this->repositoryName = $this->_bundle() . ':' . ucfirst($this->entityName);
        }
        return $this->repositoryName;
    }
}
