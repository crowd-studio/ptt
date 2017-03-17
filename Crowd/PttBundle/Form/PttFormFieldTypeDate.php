<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttFormFieldTypeDate extends PttFormFieldType
{
    public function field()
    {
        $html = $this->start();
        $html .= $this->label();


        if ($this->value instanceof \DateTime) {
            $this->value = ($this->value->format('Y') > -1) ? $this->value->format('d/m/Y') : null;
        }

        $html .= '<input type="text" data-language="' . $this->preferredLanguage . '" ' . $this->attributes() . 'value="' . $this->value . '">';

        $html .= $this->end();

        return $html;
    }

    protected function extraClassesForField()
    {
        return 'form-control datepicker';
    }

    protected function extraClassesForFieldContainer()
    {
        return 'form-group col-sm-' . $this->getWidth(6);
    }
}
