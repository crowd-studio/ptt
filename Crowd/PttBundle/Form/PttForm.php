<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttFormRender;
use Crowd\PttBundle\Util\PttSave;
use Crowd\PttBundle\Util\PttFormValidations;
use Crowd\PttBundle\Util\PttTrans;

class PttForm
{
    private $securityContext;
    private $container;
    private $sentData;
    private $errors;
    private $request;
    private $languages;
    private $preferredLanguage;
    private $pttTrans;
    private $twig;
    private $formName;
    private $fields;
    private $userId;
    private $session;
    private $metadata;
    private $entity;
    private $pttServices;

    public function __construct(TokenStorage $securityContext, ContainerInterface $serviceContainer)
    {
        $this->securityContext = $securityContext;
        $this->container = $serviceContainer;
        $this->twig = $this->container->get('twig');
        $this->session = $this->container->get('session');
        $this->pttServices = $this->container->get('pttServices');

        $this->metadata = $this->container->get('pttEntityMetadata');
        $this->languages = $this->metadata->getLanguages();
        $this->preferredLanguage = $this->metadata->getPreferredLanguage();

        $this->userId = -1;
        if ($this->securityContext->getToken() != null && method_exists($this->securityContext->getToken()->getUser(), 'getId')) {
            $this->userId = $this->securityContext->getToken()->getUser()->getId();
        }
    }

    public function setRequest($requestObj)
    {
        if (is_a($requestObj, 'Symfony\Component\HttpFoundation\RequestStack')) {
            $this->request = $requestObj->getCurrentRequest();
        } elseif (is_a($requestObj, 'Symfony\Component\HttpFoundation\Request')) {
            $this->request = $requestObj;
        }
    }

    public function setPttTrans($pttTrans)
    {
        $this->pttTrans = $pttTrans;
        $this->errors = new PttErrors($pttTrans);
    }

    public function getPttTrans()
    {
        return $this->pttTrans;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getPttServices()
    {
        return $this->pttServices;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
        $this->formName = $entity->getClassName();
        $this->fields = PttUtil::fields($this->container->get('kernel'), $this->metadata->bundle($entity), $this->formName);
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setFormName($formName)
    {
        $this->formName = $formName;
    }

    public function getFormName()
    {
        return $this->formName;
    }

    public function getSentData($fieldName = false, $languageCode = false)
    {
        if ($fieldName != false) {
            return PttUtil::getFieldData($this->sentData, $this->formName, $fieldName, null, $languageCode);
        } else {
            return $this->sentData;
        }
    }

    public function getLanguages()
    {
        return $this->languages;
    }

    public function getPreferredLanguage()
    {
        return $this->preferredLanguage;
    }

    public function getTwig()
    {
        return $this->twig;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setErrors($errors)
    {
        return $this->errors->set($errors);
    }

    public function getErrors($fieldName = false, $languageCode = false)
    {
        return $this->errors->get($fieldName, $languageCode);
    }

    public function addError($key, $message, $languageCode = false)
    {
        $this->errors->add($key, $message, $languageCode);
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getBundle()
    {
        return $this->metadata->bundle($this->entity);
    }

    public function createView($key = false)
    {
        $formRender = new PttFormRender($this, $this->entity, $this->fields);
        return $formRender->perform();
    }

    public function isValid()
    {
        $this->sentData = $this->request->request->all();

        $this->entity->beforeSave($this->sentData);
        $pttFormValidation = new PttFormValidations($this, $this->entity, $this->fields, $this->sentData);
        $this->entity = $pttFormValidation->perform();

        return !$this->errors->hasErrors();
    }

    public function save()
    {
        $pttFormSave = new PttSave($this, $this->entity, $this->fields, $this->sentData);
        $this->entity = $pttFormSave->perform();
        $this->container->get('pttServices')->persist($this->entity);
        $this->entity->afterSave($this->sentData);
    }
}
