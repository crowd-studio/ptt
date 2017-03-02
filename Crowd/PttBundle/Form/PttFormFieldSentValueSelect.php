<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Symfony\Component\HttpFoundation\Request;

class PttFormFieldSentValueSelect extends PttFormFieldSentValue
{
    public function value()
    {
        if (isset($this->field->options['multiple'])) {
            $result = [];
            if ($this->sentData){
                foreach ($this->sentData as $value) {
                    $result[] = $this->entityInfo->getEntityManager()->getRepository($this->entityInfo->getBundle() . ':' . $this->field->options['entity'])
                    ->find($value);
                }
            }
            return $result;
        } elseif ($this->field->options['type'] == 'entity') {
            return $this->entityInfo->getEntityManager()->getRepository($this->entityInfo->getBundle() . ':' . $this->field->options['entity'])
                ->find($this->sentData);
        } else {
            return (isset($this->sentData)) ? $this->sentData : null;
        }
    }
}