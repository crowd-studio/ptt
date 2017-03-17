<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationNif extends PttFormValidation
{
    public function isValid()
    {
        if ($this->_sentValue() == '') {
            return true;
        } elseif (strlen($this->_sentValue())<9) {
            return false;
        } else {
            return (substr("TRWAGMYFPDXBNJZSQVHLCKE", str_replace(array('X', 'Y', 'Z'), array(0, 1, 2), substr(strtoupper($this->_sentValue()), 0, 8)) % 23, 1)==substr(strtoupper($this->_sentValue()), -1, 1));
        }
    }
}
