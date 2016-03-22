<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeHidden extends PttFormFieldType
{
	public function field()
	{
		$htmlField = '<input type="hidden" ';
		$htmlField .= $this->attributes();
		$htmlField .= 'value="' . $this->value . '"';
		$htmlField .= '>';

		return $htmlField;
	}
}