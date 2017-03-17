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
        $pttServices = $this->container->get('pttServices');
        if (isset($this->field->options['multiple'])) {
            $result = [];
            if (isset($this->sentData[$this->field->name])) {
                foreach ($this->sentData[$this->field->name] as $value) {
                    $result[] = $pttServices->getOne($this->field->options['entity'], $value);
                }
            }
            return $result;
        } elseif ($this->field->options['type'] == 'entity') {
            return $pttServices->getOne($this->field->options['entity'], $this->sentData[$this->field->name]);
        } else {
            return (isset($this->sentData[$this->field->name])) ? $this->sentData[$this->field->name] : null;
        }
    }
}
