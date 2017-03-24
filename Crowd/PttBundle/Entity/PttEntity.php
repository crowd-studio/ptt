<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

use Crowd\PttBundle\Util\PttUtil;
use Crowd\PttBundle\Util\PttCache;

/** @ORM\MappedSuperclass @ORM\HasLifecycleCallbacks */
class PttEntity
{
    protected $uploadUrl;
    public function __toString()
    {
        if (method_exists($this, 'getTitle')) {
            return (string)$this->getTitle();
        } elseif (method_exists($this, 'getReference')) {
            return (string)$this->getReference();
        } else {
            return (string)$this->getId();
        }
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creationDate", type="datetime")
     */
    protected $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updateDate", type="datetime")
     */
    protected $updateDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="creationUserId", type="integer")
     */
    protected $creationUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="updateUserId", type="integer")
     */
    protected $updateUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    protected $slug;


    public function getPttId()
    {
        return $this->getId();
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return Self
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return Self
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set creationUserId
     *
     * @param integer $creationUserId
     * @return Tramo
     */
    public function setCreationUserId($creationUserId)
    {
        $this->creationUserId = $creationUserId;

        return $this;
    }

    /**
     * Get creationUserId
     *
     * @return integer
     */
    public function getCreationUserId()
    {
        return $this->creationUserId;
    }

    /**
     * Set updateUserId
     *
     * @param integer $updateUserId
     * @return Tramo
     */
    public function setUpdateUserId($updateUserId)
    {
        $this->updateUserId = $updateUserId;

        return $this;
    }

    /**
     * Get updateUserId
     *
     * @return integer
     */
    public function getUpdateUserId()
    {
        return $this->updateUserId;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Page
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /** @ORM\PostLoad */
    public function doPostLoad()
    {
        // do stuff
    }

    public function beforeSave()
    {
    }

    public function afterSave($entity = false)
    {
        return [];
    }

    public function fieldsToFilter()
    {
        return [];
    }

    public function enableFilters()
    {
        return false;
    }

    public function orderList()
    {
        return 'asc';
    }

    public function fieldsToList()
    {
        return  [['field' => 'id', 'label' => 'Id', 'primary' => true]];
    }

    public function entityInfo($entityName = false)
    {
        $entityName = ($entityName) ? $entityName : $this->getClassName();
        return [
            'simple' => $entityName,
            'plural' => $entityName . 's'
        ];
    }

    public function flushCache($entity)
    {
        $cache = new PttCache();
        $cache->removeAll();
    }

    public function setUpdateObjectValues($userId = -1)
    {
        $dateTime = new \DateTime();
        if ($this->getId() == null) {
            $this->creationDate = $dateTime;
            $this->creationUserId = $userId;
        }
        $this->updateDate = $dateTime;
        $this->updateUserId = $userId;
    }

    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    protected function setOne($array, $objects, $entity, $newMethod)
    {
        if (is_array($objects)) {
            // Esborrem els sobrers
            for ($iterator = $array->getIterator(); $iterator->valid(); $iterator->next()) {
                $exists = false;
                foreach ($objects as $key => $obj) {
                    if (isset($obj['id'])) {
                        if ($iterator->current()->getPttId() == $obj['id']) {
                            $exists = true;
                        }
                    }
                }
                if (!$exists) {
                    $array->removeElement($iterator->current());
                }
            }

            // Sobreescrivim
            foreach ($objects as $key => $obj) {
                $feat = false;
                if (isset($obj['id']) && $obj['id'] != '') {
                    for ($iterator = $array->getIterator(); $iterator->valid(); $iterator->next()) {
                        if ($iterator->current()->getPttId() == $obj['id']) {
                            $feat = $iterator->current();
                            $index = $iterator->key();
                        }
                    }
                }

                $update = ($feat) ? true : false;
                if (!$update) {
                    $name = PttUtil::pttConfiguration('bundles')[0]["bundle"] . '\\Entity\\' . $entity;
                    $feat = new $name();
                }

                foreach ($obj as $meth => $value) {
                    $method = 'set' . ucfirst($meth);
                    if (method_exists($feat, $method)) {
                        $feat->$method($value);
                    }
                }

                if ($update) {
                    $array->set($index, $feat);
                } else {
                    $array = $this->addOne($array, $feat, $newMethod);
                }
            }
        } else {
            $array = $objects;
        }

        return $array;
    }

    protected function addOne($array, $new, $setMethod)
    {
        if ($array->contains($new)) {
            return $array;
        }
        $array->add($new);

        $setMethod = 'set' . $setMethod;
        if (method_exists($new, $setMethod)) {
            $new->$setMethod($this);
        }

        if (method_exists($new, 'set_Order')) {
            if (!$new->get_Order()) {
                $new->set_Order(-1);
            }
        }

        if (method_exists($new, 'set_Model')) {
            $new->set_Model($this->getClassName());
        }

        if (method_exists($new, 'setUpdateObjectValues')) {
            $new->setUpdateObjectValues(1);
        }

        if (method_exists($new, 'setSlug')) {
            $new->setSlug(PttUtil::slugify((string)$new));
        }

        return $array;
    }

    protected function setMany($array, $objects, $method)
    {
        if (is_array($objects)) {
            // Esborrem els sobrers
            foreach ($array as $key => $obj) {
                if (!in_array($obj, $objects)) {
                    $array->removeElement($obj);
                }
            }

            // Afegim els que falten
            foreach ($objects as $value) {
                if (!$array->contains($value)) {
                    $array = $this->addMany($array, $value, $method);
                }
            }
        } else {
            $array = $objects;
        }

        return $array;
    }

    protected function addMany($array, $new, $setMethod)
    {
        if ($array->contains($new)) {
            return $array;
        }
        $array->add($new);
        $setMethod = 'set' . $setMethod;
        if (method_exists($new, $setMethod)) {
            $new->$setMethod($this);
        }
        return $array;
    }

    public function getJSON()
    {
        return 'This entity does not have JSON parser';
    }
}
