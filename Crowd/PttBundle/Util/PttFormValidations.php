<?php

namespace Crowd\PttBundle\Util;

use Crowd\PttBundle\Form\PttForm;
use Crowd\PttBundle\Form\PttClassNameGenerator;

class PttFormValidations
{
    private $form;
    private $languages;
    private $entity;
    private $fields;
    private $sentData;
    private $formName;

    public function __construct(PttForm $form, $entity, $fields, $sentData, $formName = '')
    {
        $this->form = $form;
        $this->formName = ($formName != '') ? $formName : $form->getFormName();
        $this->languages = $form->getLanguages();

        $this->entity = $entity;
        $this->fields = $fields;
        $this->sentData = $sentData;
    }

    public function perform()
    {
        foreach ($this->fields['block'] as $key => $block) {
            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    $this->_validateField($field);
                }
            }

            if ($this->languages && isset($block['trans'])) {
                foreach ($this->languages as $language) {
                    foreach ($block['trans'] as $field) {
                        $this->_validateField($field, $language->getCode());
                    }
                }
            }
        }

        return $this->entity;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    private function _validateField($field, $languageCode = false)
    {
        if ($field['type'] == 'url') {
            if (isset($field['type']['validations'])) {
                $field['type']['validations'][] = ['url' => true];
            } else {
                $field['validations'] = ['url' => true];
            }
        }

        $sentData = PttUtil::getFieldData($this->sentData, $this->formName, $field['name'], null, $languageCode);
        PttClassNameGenerator::validation($field, $this, $sentData, $languageCode);
        if (PttUtil::isMapped($field)) {
            $value = PttClassNameGenerator::sentValue($field, $this, $sentData, $languageCode);
            $this->set($field['name'], $value, $languageCode);
        }
    }

    private function _methodExists($name, $languageCode)
    {
        $exists = ($languageCode) ? method_exists($this->entity->getTrans()[0], $name) : method_exists($this->entity, $name);

        if ($exists) {
            return true;
        } else {
            $string = ($languageCode) ? ' does not exist for trans entity ' : ' does not exist for entity ';
            throw new \Exception('The method ' . $name . $string . $this->entity->getClassName());
        }
    }

    public function set($name, $value, $languageCode = false)
    {
        $name = 'set' . ucfirst($name);

        if ($this->_methodExists($name, $languageCode)) {
            if ($languageCode) {
                $this->_transSet($name, $value, $languageCode);
            } else {
                $this->entity->$name($value);
            }
        }
    }

    private function _transSet($name, $value, $languageCode)
    {
        foreach ($this->entity->getTrans() as $key => $val) {
            if ($languageCode == $val->getLanguage()->getCode()) {
                $this->entity->getTrans()[$key]->$name($value);
            }
        }
    }

    public function get($name, $languageCode = false)
    {
        $name = 'get' . ucfirst($name);

        return ($this->_methodExists($name, $languageCode)) ? $this->_fieldGet($name, $languageCode) : null;
    }

    private function _fieldGet($name, $languageCode)
    {
        return ($languageCode) ? $this->_transValue($name, $languageCode) : $this->entity->$name();
    }

    private function _transValue($name, $languageCode)
    {
        $val = null;
        foreach ($this->entity->getTrans() as $value) {
            if ($languageCode == $value->getLanguage()->getCode()) {
                $val = $value->$name();
            }
        }

        return $val;
    }
}
