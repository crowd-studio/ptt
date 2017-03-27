<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldValueDatetime extends PttFormFieldValue
{
    public function value()
    {
        $value = $this->_get();
        if ($value) {
            return ((int)$value->format('Y') > 0) ? $value->format('d/m/Y H:i') : '';
        } else {
            return '';
        }
    }
}
