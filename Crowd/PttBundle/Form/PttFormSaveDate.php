<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSaveDate extends PttFormSave
{
    public function value()
    {
        $value = $this->entityInfo->get($this->field->name, $this->languageCode);
        $date = null;
        if (is_string($value)) {
            try {
                $date = str_replace('/', '-', $value);
                $date = $date != '' ? new \DateTime(date($date)) : null;
            } catch (\Exception $ex) {
                $date = null;
            }
        }
        return $date;
    }
}
