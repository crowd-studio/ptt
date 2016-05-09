<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSaveCheckbox extends PttFormSave
{
    public function value()
    {
    	$return = $this->entityInfo->get($this->field->name, $this->languageCode);
    	if ($return){
    		return $return;
    	} else {
    		return false;
    	}
    }
}