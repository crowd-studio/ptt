<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;
use Symfony\Component\HttpFoundation\Request;

class PttFormSavePassword extends PttFormSave
{
    private $factory;

    public function __construct($field, $entity, $formSave, Request $request, $sentData, $container, $languageCode = false)
    {
        parent::__construct($field, $entity, $formSave, $request, $sentData, $container, $languageCode);
        $this->factory = $container->get('security.encoder_factory');
    }

    public function value()
    {
        $sentValue = $this->_sentValue([]);
        if (isset($sentValue['first']) && $sentValue['first'] != '') {
            $encoder = $this->factory->getEncoder($this->entity);
            $password = $encoder->encodePassword($sentValue['first'], $this->entity->getSalt());
        } else {
            $password = $this->entity->getPassword();
        }

        return $password;
    }
}
