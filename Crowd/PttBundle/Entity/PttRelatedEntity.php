<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Crowd\PttBundle\Entity\PttEntity;

class PttRelatedEntity extends PttEntity
{

    /**
     * @var integer
     *
     * @ORM\Column(name="_order", type="integer")
     */
    protected $_order;

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
}
