<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationUrl extends PttFormValidation
{
    public function isValid()
    {
        $query = 'http://';
        $valid = (substr($this->_sentValue(), 0, strlen($query)) === $query);
        if (!$valid) {
            $query = 'https://';
            $valid = (substr($this->_sentValue(), 0, strlen($query)) === $query);
        }
        
        return $valid;
    }
}
