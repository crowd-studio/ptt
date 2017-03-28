<?php

namespace Crowd\PttBundle\Util;

use Crowd\PttBundle\Form\PttForm;
use Crowd\PttBundle\Form\PttClassNameGenerator;

class PttSave
{
    private $fields;
    private $languages;
    private $form;
    private $entity;
    private $sentData;
    private $userId;

    public function __construct(PttForm $form, $entity, $fields, $sentData)
    {
        $this->form = $form;
        $this->languages = $form->getLanguages();
        $this->preferredLanguage = $form->getPreferredLanguage();
        $this->userId = $form->getUserId();

        $this->entity = $entity;
        $this->fields = $fields;
        $this->sentData = $sentData;
    }

    public function perform()
    {
        $this->_performFieldsLoopAndCallMethodNamed('_saveForField');

        if (method_exists($this->entity, 'setTitle') && method_exists($this->entity, 'getTitle')) {
            $title = $this->getSentData('title', $this->preferredLanguage->getCode());
            if ($title) {
                $this->entity->setTitle($title);
            }
        }

        if (method_exists($this->entity, 'setSlug') && method_exists($this->entity, 'getSlug')) {
            $this->entity->setSlug(PttUtil::slugify((string)$this->entity));
        }

        if (is_subclass_of($this->entity, 'Crowd\PttBundle\Entity\PttEntity')) {
            $this->set('updateObjectValues', $this->userId);
        }

        if (!$this->entity->getPttId()) {
            if (method_exists($this->entity, 'set_Order')) {
                if (!$this->entity->get_Order()) {
                    $this->entity->set_Order(-1);
                }
            }
        }

        return $this->entity;
    }

    public function getSentData($fieldName = false, $languageCode = false)
    {
        if ($fieldName != false) {
            return PttUtil::getFieldData($this->sentData, $this->entity->getClassName(), $fieldName, null, $languageCode);
        } else {
            return $this->sentData;
        }
    }

    public function getForm()
    {
        return $this->form;
    }

    private function _performFieldsLoopAndCallMethodNamed($nameOfMethod)
    {
        foreach ($this->fields['block'] as $key => $block) {
            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    $sentData = ($field['type'] == 'entity') ? $this->form->getSentData($field['entity']) : $this->sentData;
                    PttClassNameGenerator::saveForField($field, $this->entity, $this, $this->form->getRequest(), $sentData, $this->form->getContainer(), false);
                }
            }

            if ($this->languages && isset($block['trans'])) {
                foreach ($this->languages as $language) {
                    foreach ($block['trans'] as $field) {
                        $sentData = ($field['type'] == 'entity') ? $this->form->getSentData($field['entity'], $language->getCode()) : $this->sentData;
                        PttClassNameGenerator::saveForField($field, $this->entity, $this, $this->form->getRequest(), $sentData, $this->form->getContainer(), $language->getCode());
                    }
                }
            }
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
        foreach ($this->entity->getTrans() as $val) {
            if ($languageCode == $val->getLanguage()->getCode()) {
                $val->$name($value);
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
