<?php

/*
 * COPYRIGHT © 2014 THE CROWD CAVE S.L.
 * All rights reserved. No part of this publication may be reproduced, distributed, or transmitted in any form or by any means, including photocopying, recording, or other electronic or mechanical methods, without the prior written permission of the publisher.
 */

namespace Crowd\PttBundle\Form;

use Crowd\PttBundle\Util\PttUtil;

class PttErrors
{
    public $errors = [];
    private $pttTrans;

    public function __construct($pttTrans)
    {
        $this->pttTrans = $pttTrans;
    }

    public function add($key, $message, $languageCode = false)
    {
        if ($languageCode) {
            if (!is_array($message)) {
                if (!isset($this->errors[$languageCode][$key])) {
                    $this->errors[$languageCode][$key] = [];
                }
                $this->errors[$languageCode][$key][] = $this->pttTrans->trans($message);
            } else {
                $this->errors[$languageCode][$key] = $this->pttTrans->trans($message);
            }
        } else {
            if (!is_array($message)) {
                if (!isset($this->errors[$key])) {
                    $this->errors['static'][$key] = [];
                }
                $this->errors['static'][$key][] = $this->pttTrans->trans($message);
            } else {
                $this->errors['static'][$key] = $this->pttTrans->trans($message);
            }
        }
    }

    public function get($fieldName = false, $languageCode = false)
    {
        if ($fieldName != false) {
            if ($languageCode) {
                return (isset($this->errors[$languageCode][$fieldName])) ? $this->errors[$languageCode][$fieldName] : false;
            } else {
                return (isset($this->errors['static'][$fieldName])) ? $this->errors['static'][$fieldName] : false;
            }
        }
        return $this->errors;
    }

    public function set($errors)
    {
        $this->errors = $errors;
    }

    public function hasErrors($languageCode = false)
    {
        if ($languageCode) {
            return (isset($this->errors[$languageCode]));
        } else {
            return (count($this->errors) > 0);
        }
    }
}
