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
use Crowd\PttBundle\Util\PttFormSave;
use Crowd\PttBundle\Util\PttTrans;

class PttForm
{
    private $securityContext;
    private $container;
    private $entityInfo;
    private $sentData;
    private $sentDataTrans;
    private $errors;
    private $request;
    private $languages;
    private $preferredLanguage;
    private $pttTrans;
    private $totalData = 0;
    private $twig;
    private $formName;
    private $fields;
    private $userId;

    public function __construct(EntityManager $entityManager, TokenStorage $securityContext, ContainerInterface $serviceContainer)
    {
        $this->securityContext = $securityContext;
        $this->container = $serviceContainer;
        $this->twig = $this->container->get('twig');

        $metadata = $this->container->get('pttEntityMetadata');
        $this->languages = $metadata->getLanguages();
        $this->preferredLanguage = $metadata->getPreferredLanguage();

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

    public function setEntity($entity)
    {
        $this->entityInfo = new PttEntityInfo($entity, $this->container, $this->languages, $this->pttTrans);
        $this->formName = $this->entityInfo->getEntityName();
        $this->fields = PttUtil::fields($this->container->get('kernel'), $this->entityInfo->getBundle(), $this->entityInfo->getEntityName());
    }

    public function setFormName($formName)
    {
        $this->entityInfo->setFormName($formName);
    }

    public function getEntityInfo()
    {
        return $this->entityInfo;
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

    public function createView($key = false)
    {
        $formRender = new PttFormRender($this, $this->entityInfo->getEntity(), $this->fields);
        return $formRender->perform($key);
    }

    public function isValid()
    {
        $this->sentData = $this->request->request->all();

        $this->entityInfo->getEntity()->beforeSave($this->sentData);
        $this->_validateFields();

        return !$this->errors->hasErrors();
    }

    public function save()
    {
        $pttFormSave = new PttFormSave($this, $this->entityInfo->getEntity(), $this->fields, $this->sentData);
        $entityPrincipal = $pttFormSave->perform();
        $this->container->get('pttServices')->persist($entityPrincipal);
        $entityPrincipal->afterSave($this->sentData);
    }

    private function _validateFields()
    {
        foreach ($this->fields['block'] as $key => $block) {
            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    $this->_validateField($field);
                }
            }

            if ($this->languages && isset($block['trans'])) {
                foreach ($this->languages as $language) {
                    foreach ($block['trans'] as $field) {
                        $this->_validateField($field, $language->getCode());
                    }
                }
            }
        }
    }

    private function _validateField($field, $languageCode = false)
    {
        PttClassNameGenerator::validation($field, $this, $languageCode);
        if (PttUtil::isMapped($field)) {
            $value = PttClassNameGenerator::sentValue($field, $this, $languageCode);
            $this->entityInfo->set($field['name'], $value, $languageCode);
        }
    }
}
