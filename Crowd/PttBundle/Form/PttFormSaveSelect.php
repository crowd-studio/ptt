<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSaveSelect extends PttFormSave
{
    public function value()
    {
        $pttServices = $this->entityInfo->getPttServices();
        if (isset($this->field['multiple'])) {
            $result = [];
            foreach ($this->_sentValue([]) as $value) {
                $result[] = $pttServices->getOne($this->field['entity'], $value);
            }
            return $result;
        } elseif (isset($this->field['entity'])) {
            return $pttServices->getOne($this->field['entity'], $this->_sentValue(0));
        } else {
            return $this->_sentValue(null);
        }
    }
}
