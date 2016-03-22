<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationNotEmpty extends PttFormValidation
{
	public function isValid()
	{
        $multiple = (isset($this->field->options['multiple']));
        if ($multiple) {
            return (count($this->_sentValue(array())));
        } else {
            return ($this->_sentValue(-1) != -1);
        }
	}
}