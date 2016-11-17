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
    		// $entities = $this->entityInfo->get($this->field->name);
    		// if($entities){
    		// 	$entitiesArr = $entities->toArray();
    		// 	foreach ($this->sentData[$this->field->name] as $key => $value) {
	    	// 		if (isset($value['id'])) {
	    	// 			$entity = 0;
	    	// 			foreach ($entitiesArr as $row => $val) {
	    	// 				if($val->getPttId() == $value['id']){
	    	// 					$entity = $val;
	    	// 				}
	    	// 			}

	    	// 			foreach ($value as $meth => $setter) {
	    	// 				if($meth != 'id'){
	    	// 					$method = 'set' . ucfirst($meth);
	    	// 					$entity->$method($setter);
	    	// 				}
	    	// 			}
	    	// 			$this->sentData[$this->field->name][$key] = $entity;
	    	// 		}
	    	// 	}
    		// }
    		return ($this->languageCode) ? $this->sentData[$this->languageCode][$this->field->name] : $this->sentData[$this->field->name];
    	} else {
    		return [];
    	}
    }
}