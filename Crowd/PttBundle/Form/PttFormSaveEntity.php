<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSaveEntity extends PttFormSave
{
    public function value()
    {
        $method = 'get' . $this->field['name'];
        $entity = $this->entity->$method();
        $helper = new PttHelperFormFieldTypeEntity($this->entityInfo, $this->entityInfo->getForm(), $entity, '', '', $this->sentData);
        return $helper->save();
    }
}
