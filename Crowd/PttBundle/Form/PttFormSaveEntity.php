<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Form\PttFormSave;

class PttFormSaveEntity extends PttFormSave
{
    public function value()
    {
    	if(isset($this->sentData[$this->field->name])){
    		return ($this->languageCode) ? $this->sentData[$this->languageCode][$this->field->name] : $this->sentData[$this->field->name];
    	} else {
    		return [];
    	}
    }
}