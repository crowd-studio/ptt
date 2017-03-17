<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttFormValidationUnique extends PttFormValidation
{
    public function isValid()
    {
        $exists = $this->entityInfo->getPttServices()->get($this->entityInfo->getEntityName(), [
                    'where' => [
                            ['and' => [
                                    ['column' => 'id', 'operator' => '!=', 'value' => ($this->entityInfo->get('id')) ? $this->entityInfo->get('id') : -1],
                                    ['column' => $this->field->name, 'operator' => '=', 'value' => $this->_sentValue()]
                            ]]
                ]]);
        return (!isset($exists[0]));
    }
}
