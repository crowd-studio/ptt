<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeColor extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();
        $html .= $this->label();

        $html .= '<div class="picker-body"><input type="text" ' . $this->attributes() . 'value="' . $this->value . '"><span class="input-group-addon"><i></i></span></div>';
        $html .= $this->end();

        return $html;
    }

    protected function extraClassesForFieldContainer()
    {
        return 'form-group colorPicker col-sm-' . $this->getWidth(6);
    }

    protected function extraClassesForField()
    {
        return 'input-group form-control colorPicker';
    }
}
