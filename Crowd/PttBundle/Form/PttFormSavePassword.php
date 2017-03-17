<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSavePassword extends PttFormSave
{
    private $factory;

    public function __construct(PttField $field, PttEntityInfo $entityInfo, Request $request, $sentData, $container, $languageCode = false)
    {
        parent::__construct(PttField $field, PttEntityInfo $entityInfo, Request $request, $sentData, $container, $languageCode = false)
        $this->factory = $container->get('security.encoder_factory');
    }

    public function value()
    {
        $entity = $this->entityInfo->getEntity();
        if (isset($this->_sentValue([])["first"]) && $this->_sentValue()["first"] != '') {
            $encoder = $this->factory->getEncoder($entity);
            $password = $encoder->encodePassword($this->_sentValue([])["first"], $entity->getSalt());
        } else {
            $password = $entity->getPassword();
        }

        return $password;
    }
}
