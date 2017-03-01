<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

class PttClassNameGenerator
{
    public static function field($type)
    {
        return 'Crowd\PttBundle\Form\PttFormFieldType' . ucfirst($type);
    }

    public static function save($type)
    {
        $name = 'Crowd\PttBundle\Form\PttFormSave';
        $className = $name . ucfirst($type);
        return (class_exists($className)) ? $className : $name . 'Default';
    }

    public static function sentValue($type)
    {
        $name = 'Crowd\PttBundle\Form\PttFormFieldSentValue';
        $className = $name . ucfirst($type);
        return (class_exists($className)) ? $className : $name . 'Default';
    }

    public static function afterSave($type)
    {
        $className = 'Crowd\PttBundle\Form\PttFormAfterSave' . ucfirst($type);
        return (!class_exists($className)) ? false : $className;
    }

    public static function validation($type)
    {
        $capitalizedType = '';
        $typeArr = explode('_', $type);
        foreach ($typeArr as $type) {
            $capitalizedType .= ucfirst($type);
        }
        return 'Crowd\PttBundle\Form\PttFormValidation' . $capitalizedType;
    }
}