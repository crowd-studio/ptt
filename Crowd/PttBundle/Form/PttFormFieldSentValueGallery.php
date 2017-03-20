<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldSentValueGallery extends PttFormFieldSentValue
{
    public function value()
    {
        if ($this->sentData) {
            foreach ($this->sentData as $key => $entity) {
                if ($entity['id'] != '') {
                    $this->sentData[$key] = $this->entityInfo->getPttServices()->getOne($this->field['options']['entity'], $entity['id']);
                }
            }
        } else {
            return [];
        }
    }
}
