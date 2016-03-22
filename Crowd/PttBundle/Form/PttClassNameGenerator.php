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
        $className = 'Crowd\PttBundle\Form\PttFormSave' . ucfirst($type);
        if (!class_exists($className)) {
            $className = 'Crowd\PttBundle\Form\PttFormSaveDefault';
        }
        return $className;
    }

    public static function sentValue($type)
    {
        $className = 'Crowd\PttBundle\Form\PttFormFieldSentValue' . ucfirst($type);
        if (!class_exists($className)) {
            $className = 'Crowd\PttBundle\Form\PttFormFieldSentValueDefault';
        }
        return $className;
    }

    public static function afterSave($type)
    {
        $className = 'Crowd\PttBundle\Form\PttFormAfterSave' . ucfirst($type);
        if (!class_exists($className)) {
            return false;
        } else {
            return $className;
        }
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