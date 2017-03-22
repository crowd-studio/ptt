<?php

namespace Crowd\PttBundle\Util;

use Doctrine\ORM\EntityManager;
use Crowd\PttBundle\Util\PttCache;

class PttEntityMetadata
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    //CLASS NAME

    public function className($entity)
    {
        if (is_string($entity)) {
            return $this->_stringClassName($entity);
        } elseif (is_object($entity)) {
            return $this->_objectClassName($entity);
        }
    }

    private function _stringClassName($entity)
    {
        if (strpos($entity, ':') !== false) {
            $metadata = $this->em->getClassMetadata($entity);
            return '\\' . $metadata->name;
        } else {
            return 'AdminBundle\Entity\\' .$entity;
        }
    }

    private function _objectClassName($entity)
    {
        return get_class($entity);
    }

    //ENTITY NAME

    public function entityName($entity)
    {
        if (is_string($entity)) {
            return $this->_stringEntityName($entity);
        } elseif (is_object($entity)) {
            return $this->_objectEntityName($entity);
        }
    }

    private function _stringEntityName($entity)
    {
        if (strpos($entity, ':') !== false) {
            $parts = explode(':', $entity);
            return end($parts);
        } else {
            return $entity;
        }
    }

    private function _objectEntityName($entity)
    {
        $className = $this->className($entity);
        $parts = explode('\\', $className);
        return end($parts);
    }

    //REPOSITORY NAME

    public function respositoryName($entity)
    {
        if (is_string($entity)) {
            return $this->_stringRepositoryName($entity);
        } elseif (is_object($entity)) {
            return $this->_objectRepositoryName($entity);
        }
    }

    private function _stringRepositoryName($entity)
    {
        if (strpos($entity, ':') !== false) {
            return $entity;
        } else {
            $className = $this->className($entity);
            $parts = explode('\\', $className);
            $respoArr = array();
            foreach ($parts as $part) {
                if (strpos($part, 'Bundle') !== false) {
                    $respoArr[] = $part;
                }
            }
            $respoArr[] = end($parts);
            return implode(':', $respoArr);
        }
    }

    private function _objectRepositoryName($entity)
    {
        $className = $this->className($entity);
        $parts = explode('\\', $className);
        $respoArr = array();
        foreach ($parts as $part) {
            if (strpos($part, 'Bundle') !== false) {
                $respoArr[] = $part;
            }
        }
        $respoArr[] = end($parts);
        return implode(':', $respoArr);
    }

    //NAMESPACE

    public function entityNamespace($entity)
    {
        $metadata = $this->em->getClassMetadata($entity);
        return $metadata->namespace;
    }

    //BUNDLE

    public function bundle($entity)
    {
        if (is_string($entity)) {
            return $this->_stringBundle($entity);
        } elseif (is_object($entity)) {
            return $this->_objectBundle($entity);
        }
    }

    private function _stringBundle($entity)
    {
        $metadata = $this->em->getClassMetadata($entity);
        $namespace = $metadata->namespace;
        $parts = explode('\\', $namespace);
        foreach ($parts as $part) {
            if (strpos($part, 'Bundle') !== false) {
                return $part;
            }
        }
        return false;
    }

    private function _objectBundle($entity)
    {
        $className = $this->className($entity);
        $parts = explode('\\', $className);
        foreach ($parts as $part) {
            if (strpos($part, 'Bundle') !== false) {
                return $part;
            }
        }
        return false;
    }

    public function getLanguages()
    {
        return $this->em->getRepository($this->_getLanguageBundle())->findAll();
    }

    public function getPreferredLanguage()
    {
        $data = $this->em->getRepository($this->_getLanguageBundle())->findBy(['preferred' => 1]);
        return (isset($data[0])) ? $data[0] : null;
    }

    private function _getLanguageBundle()
    {
        return PttUtil::pttConfiguration('bundles')[0]['bundle'] . ':Language';
    }
}
