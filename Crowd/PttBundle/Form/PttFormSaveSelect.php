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
    	if (isset($this->field->options['multiple'])) {
            $result = [];
            if (isset($this->sentData[$this->field->name])){
                foreach ($this->sentData[$this->field->name] as $value) {
                    $result[] = $this->entityInfo->getEntityManager()->getRepository($this->entityInfo->getBundle() . ':' . $this->field->options['entity'])->find($value);
                }
            }
            return $result;
        } elseif ($this->field->options['type'] == 'entity') {
            $result = $this->entityInfo->getEntityManager()->getRepository($this->entityInfo->getBundle() . ':' . $this->field->options['entity'])
                ->find($this->sentData[$this->field->name]);
            return $result;
        } else {
            return (isset($this->sentData[$this->field->name])) ? $this->sentData[$this->field->name] : null;
        }
    }
}