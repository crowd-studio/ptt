<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldSentValueSelect extends PttFormFieldSentValue
{
    public function value()
    {
        if (isset($this->field->options['multiple'])) {
            $result = [];
            if ($this->sentData){
                foreach ($this->sentData as $value) {
                    $result[] = $this->entityInfo->getPttServices()->getOne($this->field->options['entity'], $value);
                }
            }
            return $result;
        } elseif ($this->field->options['type'] == 'entity') {
            return $this->entityInfo->getPttServices()->getOne($this->field->options['entity'], $this->sentData);
        } else {
            return (isset($this->sentData)) ? $this->sentData : null;
        }
    }
}
