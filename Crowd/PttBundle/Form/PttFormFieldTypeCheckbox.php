<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeCheckbox extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();
        $html .= $this->label();

        $delete = '';
        if (isset($this->field->options['delete'])) {
            $delete = "onclick=\"return confirm('" . $this->field->options['delete'] . "')\"";
        }

        $checked = ($this->value == 1) ? 'checked="checked"' : '';

        $htmlField = '<div><div class="switch">
      					<input type="checkbox" class="check-control switch-input" '. $this->attributes() . ' ' . $checked . ' value="1">
      					<label class="switch-label" '. str_replace('id=', 'for=', $this->attributes()) . ' ' . $delete . '>Switch</label>
    				  </div></div>';


        $html .= $htmlField;
        $html .= $this->end();

        return $html;
    }

    protected function extraClassesForFieldContainer()
    {
        return 'checkbox col-sm-' . $this->getWidth(6);
    }

    protected function extraClassesForField()
    {
        return '';
    }
}
