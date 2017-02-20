<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Crowd\PttBundle\Entity\PttEntity;

class PttMultipleEntity extends PttEntity
{

    /**
     * @var integer
     *
     * @ORM\Column(name="_order", type="integer")
     */
    protected $_order;

    /**
     * @var string
     *
     * @ORM\Column(name="_model", type="text")
     */
    protected $_model;

    /**
     * @var string
     *
     * @ORM\Column(name="relatedid", type="integer")
     */
    protected $relatedid;

    /**
     * Set _order
     *
     * @param integer $_order
     *
     * @return Entity
     */
    public function set_Order($_order)
    {
        $this->_order = $_order;

        return $this;
    }

    /**
     * Get _order
     *
     * @return integer
     */
    public function get_Order()
    {
        return $this->_order;
    }

    /**
     * Set _model
     *
     * @param integer $_model
     *
     * @return Entity
     */
    public function set_Model($_model)
    {
        $this->_model = $_model;

        return $this;
    }

    /**
     * Get _model
     *
     * @return integer
     */
    public function get_Model()
    {
        return $this->_model;
    }

    /**
     * Set relatedid
     *
     * @param integer $relatedid
     *
     * @return Entity
     */
    public function setRelatedid($relatedid)
    {
        $this->relatedid = $relatedid;

        return $this;
    }

    /**
     * Get relatedid
     *
     * @return integer
     */
    public function getRelatedid()
    {
        return $this->relatedid;
    }
}