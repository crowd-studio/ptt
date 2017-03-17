<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeMap extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();
        $html .= $this->label();

        $htmlFieldInput = '<input type="hidden" ';
        $htmlFieldInput .= $this->attributes();
        $htmlFieldInput .= 'value="' . $this->value . '"';
        $htmlFieldInput .= '>';

        $htmlField = '
		<div class="map">
			' . $htmlFieldInput . '
			<div class="panel">
				<div class="input-group input-group-sm">
					<input type="text" class="address form-control" placeholder="Address">
					<span class="input-group-btn">
				        <button class="btn btn-primary search" type="button">Search</button>
				    </span>
				</div>
			</div>
			<div class="map-canvas"></div>
		</div>
		';

        $html .= $htmlField;
        $html .= $this->end();

        return $html;
    }

    protected function extraClassesForField()
    {
        return 'coordinates';
    }

    protected function extraClassesForFieldContainer()
    {
        return 'form-group map col-sm-' . $this->getWidth();
    }
}
