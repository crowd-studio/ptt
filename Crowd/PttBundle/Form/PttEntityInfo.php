<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Component\Yaml\Parser;

use Crowd\PttBundle\Util\PttUtil;

class PttEntityInfo
{
    private $entity;
    private $transEntities;
    private $entityName;
    private $bundle;
    private $repositoryName;
    private $className;
    private $fields;
    private $formName;
    private $container;
    private $pttTrans;
    private $pttEntityMetadata;
    private $pttServices;

    public function __construct($entity, ContainerInterface $container, $languages = false, $pttTrans)
    {
        $this->container = $container;
        $this->pttServices = $this->container->get('pttServices');
        $this->pttEntityMetadata = $this->container->get('pttEntityMetadata');

        $this->className = $this->pttEntityMetadata->className($entity);

        $this->entityName = $this->pttEntityMetadata->entityName($entity);

        $this->bundle = $this->pttEntityMetadata->bundle($entity);

        $this->repositoryName = $this->pttEntityMetadata->respositoryName($entity);

        $this->entity = $entity;

        $this->formName = $this->entityName;

        $this->transEntities = [];

        if (method_exists($entity, 'getTrans')) {
            $trans = $entity->getTrans();

            if (!count($trans)) {
                foreach ($languages as $language) {
                    $this->entity->createTrans($language);
                }

                $trans = $entity->getTrans();
            }

            foreach ($trans as $key => $value) {
                if ($value->getLanguage()) {
                    $lang = $value->getLanguage()->getCode();
                } else {
                    $lang = $languages[$key % 2]->getCode();
                    $value->setLanguage($languages[$key % 2]);
                    $value->setRelatedid($this->entity);
                }
                $this->transEntities[$lang] = $value;
            }
        }

        $this->pttTrans = $pttTrans;

        $this->fields = $this->getFieldsNew();
    }

    public function getPttServices()
    {
        return $this->pttServices;
    }

    public function setFormName($formName)
    {
        $this->formName = $formName;
        $this->_fetchFields();
    }

    public function getFormName()
    {
        return $this->formName;
    }

    public function getForm()
    {
        return $this->container->get('pttForm');
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getTransEntities()
    {
        return $this->transEntities;
    }

    public function getEntityName()
    {
        return $this->entityName;
    }

    public function getRepositoryName()
    {
        return $this->repositoryName;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getBundle()
    {
        return $this->bundle;
    }

    public function getFields($property = false)
    {
        return ($property != false) ? $this->fields[$property] : $this->fields;
    }

    public function hasMethod($methodName, $languageCode = false)
    {
        if ($languageCode) {
            $entity = $this->_entityForLanguageCode($languageCode);
            return method_exists($entity, $methodName);
        } else {
            return method_exists($this->entity, $methodName);
        }
    }

    public function set($name, $value, $languageCode = false)
    {
        if ($name != 'id') {
            $methodName = 'set' . ucfirst($name);
            if ($languageCode) {
                if (!$this->hasMethod($methodName, $languageCode)) {
                    throw new \Exception('The method ' . $methodName . ' does not exist for trans entity ' . $this->getEntityName());
                } else {
                    $entity = $this->_entityForLanguageCode($languageCode);
                    $entity->{$methodName}($value);
                }
            } else {
                if (!$this->hasMethod($methodName)) {
                    throw new \Exception('The method ' . $methodName . ' does not exist for entity ' . $this->getEntityName());
                } else {
                    $this->entity->{$methodName}($value);
                }
            }
        }
    }

    public function get($name, $languageCode = false)
    {
        $methodName = 'get' . ucfirst($name);

        if ($languageCode) {
            if (!$this->hasMethod($methodName, $languageCode)) {
                throw new \Exception('The method ' . $methodName . ' does not exist for trans entity ' . $this->getEntityName());
            } else {
                $entity = $this->_entityForLanguageCode($languageCode);
                return $entity->{$methodName}();
            }
        } else {
            if (!$this->hasMethod($methodName)) {
                throw new \Exception('The method ' . $methodName . ' does not exist for entity ' . $this->getEntityName());
            } else {
                return $this->entity->{$methodName}();
            }
        }
    }

    public function appendField($field)
    {
        $this->fields->addField($this->formName, $field);
    }

    private function _entityForLanguageCode($languageCode)
    {
        return $this->transEntities[$languageCode];
    }

    public function getFieldsNew()
    {
        $kernel = $this->container->get('kernel');
        $filePath = $kernel->locateResource('@' . $this->bundle . '/Form/' . $this->entityName . '.yml');
        $yaml = new Parser();

        return $yaml->parse(file_get_contents($filePath));
    }
}
