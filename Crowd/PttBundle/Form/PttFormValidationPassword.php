<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormValidationPassword extends PttFormValidation {
		public function isValid(){
				$value = (isset($this->sentData['first'])) ? $this->sentData['first'] : '';
		    $repeatedValue = (isset($this->sentData['repeat'])) ? $this->sentData['repeat'] : '';

				$originalValue = $this->entityInfo->get($this->field->name);


		    if (trim($value) != '') {
		        if (strlen(trim($value)) < 6) {
		            return false;
		        } else {
		            return (trim($value) == trim($repeatedValue));
		        }
		    } else {
		        return ($originalValue != '');
		    }
		}
}
