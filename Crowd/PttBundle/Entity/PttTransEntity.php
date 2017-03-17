<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class PttTransEntity
{
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
     * @ORM\ManyToOne(targetEntity="Language", fetch="EAGER")
     * @ORM\JoinColumn(name="language", referencedColumnName="id")
     */
    protected $language;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255)
     */
    protected $slug;

    protected $relatedid;

    /**
     * Set language
     *
     * @param \AdminBundle\Entity\Language $language
     *
     * @return Trans
     */
    public function setLanguage(\AdminBundle\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return \AdminBundle\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return PttTransEntity
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

    /**
     * Set relatedid
     *
     * @param Country $relatedid
     * @return PttTransEntity
     */
    public function setRelatedid($relatedid)
    {
        $this->relatedid = $relatedid;

        return $this;
    }

    /**
     * Get relatedid
     *
     * @return string
     */
    public function getRelatedid()
    {
        return $this->relatedid;
    }
}
