<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class PttEntity
{
    protected $prepared;
    public function __toString()
    {
        if (method_exists($this, 'getTitle')) {
            return (string)$this->getTitle();
        } else if (method_exists($this, 'getReference')) {
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

    public function getPttParameters($type = false){
        $this->prepared = true;
        return [];

    }

    public function isPrepared(){
        return $this->prepared;
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

    public function getClassName(){
        return (new \ReflectionClass($this))->getShortName();
    }
}