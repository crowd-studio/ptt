<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormValidationUnique extends PttFormValidation
{
    private $pttServices;

    public function __construct(PttForm $pttForm, $entity)
    {
        parent::__construct($pttForm, $entity);
        $this->pttServices = $pttForm->getContainer()->get('pttServices');
    }

    public function isValid()
    {
        $exists = $this->pttServices->get($this->entityInfo->getEntityName(), [
                    'one' => true,
                    'where' => [
                            ['and' => [
                                    ['column' => 'id', 'operator' => '!=', 'value' => ($this->entityInfo->get('id')) ? $this->entityInfo->get('id') : -1],
                                    ['column' => $this->field->name, 'operator' => '=', 'value' => $this->_sentValue()]
                            ]]
                ]]);

        return ($exists);
    }
}
