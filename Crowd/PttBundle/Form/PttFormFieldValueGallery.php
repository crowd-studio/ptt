<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormValue;

class PttFormFieldValueGallery extends PttFormFieldValue
{
    public function value()
    {
        if ($this->request->getMethod() == 'POST') {
            return ($this->sentData != null) ? $this->sentData : [];
        } else {
            return $this->_get('get' . ucfirst($this->field->name));
        }
    }
}
