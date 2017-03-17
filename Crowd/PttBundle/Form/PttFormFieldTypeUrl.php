<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeUrl extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();
        $html .= $this->label();

        $htmlField = '<div class="picker-body"><input class="url-text form-control" type="text" ';
        $htmlField .= $this->attributes();
        $htmlField .= 'value="' . $this->value . '"';
        $htmlField .= '>';

        if (isset($this->field->options['button']) && $this->field->options['button'] == 'true') {
            $htmlField .= '<a class="btn btn-md btn-primary open-link">' . $this->pttTrans->trans('open') . '</a>';
        }

        $htmlField .= '</div><p class="help-block">Remember to add <strong>http://</strong> or <strong>https://</strong></p>';

        $html .= $htmlField;
        $html .= $this->end();

        return $html;
    }

    protected function extraClassesForFieldContainer()
    {
        return 'form-group url col-sm-' . $this->getWidth();
    }
}
