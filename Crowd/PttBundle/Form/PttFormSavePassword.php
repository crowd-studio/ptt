<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSavePassword extends PttFormSave
{
    public function value()
    {
        $factory = $this->container->get('security.encoder_factory');
        $entity = $this->entityInfo->getEntity();
        $encoder = $factory->getEncoder($entity);
        $password = $encoder->encodePassword($this->sentData[$this->field->name]["first"], $entity->getSalt());

        return $password;
    }
}