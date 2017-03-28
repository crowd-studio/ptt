<?php

namespace Crowd\PttBundle\Util;

use Crowd\PttBundle\Form\PttForm;
use Crowd\PttBundle\Form\PttClassNameGenerator;

use Crowd\PttBundle\Form\PttHelperFormFieldTypeEntity;

class PttFormRender
{
    private $htmlFields;
    private $fields;
    private $languages;
    private $entity;
    private $form;
    private $formName;

    public function __construct(PttForm $form, $entity, $fields, $formName = '', $formId = '')
    {
        $this->form = $form;
        $this->entity = $entity;
        $this->languages = $form->getLanguages();
        $this->fields = $fields;
        $this->formName = $formName;
        $this->formId = $formId;
        $this->htmlFields = [];

        if (method_exists($entity, 'getTrans')) {
            if (!count($entity->getTrans())) {
                foreach ($languages as $language) {
                    $this->entity->createTrans($language);
                }
            }
        }
    }

    public function perform($key)
    {
        $this->_makeHtmlFields();
        return ($key != false && $key != 'multi') ? $this->_createSingleView($key) : $this->_createGlobalView($key);
    }

    private function _createSingleView($key)
    {
        return (isset($this->htmlFields[$key])) ? $this->htmlFields[$key] : 'Input ' . $key . ' not found';
    }

    private function _createGlobalView($key)
    {
        $html = '';
        $entityName = $this->entity->getClassName();

        foreach ($this->fields['block'] as $i => $block) {
            if ($key == false) {
                $html .= '<div class="block-container well container-fluid"><div class="block-header header-line"><h4>' . $block['title'] . '</h4></div><div class="block-body row">';
            } else {
                $html .= '<div><div>';
            }

            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    if (isset($this->htmlFields[$field['name']])) {
                        $html .= $this->htmlFields[$field['name']];
                    } else {
                        $html .= 'pending to do ' . $field['type'] ;
                    }
                }
            }

            if ($this->languages && isset($this->htmlFields['Trans'])) {
                $html .= '<div class="form-group col-xs-12"><div class="tabs"><ul class="tab-nav list-inline">';

                foreach ($this->languages as $k => $language) {
                    $error = ($this->form->getErrors(false, $language->getCode())) ? ' error' : '';
                    $html .= '<li class="' . $error . '"><a data-toggle="#' . strtolower($block['title']) . '-' . $language->getCode() . '" ng-click="changeTabEvent($event)">' . $language->getTitle() . '</a></li>';
                }

                $html .= '</ul><div class="tab-content">';
                foreach ($this->languages as $k => $language) {
                    $id = strtolower($block['title']) . '-' . $language->getCode();
                    $html .= '<div id="' . $id . '" class="tab-pane"><div class="container-fluid"><div class="row">';
                    foreach ($this->htmlFields['Trans'][$language->getCode()] as $fields) {
                        $html .= $fields;
                    }
                    $html .= '</div></div></div>';
                }

                $html .= '</div></div></div>';
            }
            $html .= '</div></div>';
        }


        return $html;
    }

    private function _makeHtmlFields()
    {
        if (!count($this->htmlFields)) {
            foreach ($this->fields['block'] as $key => $block) {
                if ($block['static']) {
                    foreach ($block['static'] as $field) {
                        $this->htmlFields[$field['name']] = $this->_renderField($field);
                    }
                }

                if (isset($block['trans'])) {
                    foreach ($this->languages as $k => $language) {
                        foreach ($block['trans'] as $field) {
                            $this->htmlFields['Trans'][$language->getCode()][$field['name']] = $this->_renderField($field, $language->getCode());
                        }
                    }
                }
            }
        }
    }

    private function _renderField($field, $language = false)
    {
        if (false) {
            $entity = $this->form->getContainer()->get('pttServices')->getOne(strtolower($field['entity']), 1);
            $formName = ($this->formName != '') ? $this->formName : $this->entity->getClassName();
            $formId = ($this->formId != '') ? $this->formId  : $this->entity->getClassName();
            $helper = new PttHelperFormFieldTypeEntity($this, $this->form, $entity, $formName, $formId);

            return $helper->formForEntity($entity, 1);
        } else {
            $field['value'] = PttClassNameGenerator::value($field, $this, $this->entity, $this->form->getSentData(), $this->form->getRequest(), $language);

            if ($field['type'] == 'image') {
                if (isset($field['options']['sizes'][0])) {
                    $w = $field['options']['sizes'][0]['w'];
                    $h = $field['options']['sizes'][0]['h'];
                } else {
                    $w = $h = 0;
                }
                $field['url'] = ($field['value'] != '') ? $this->_urlPrefix($field) . $w . '-' . $h . '-' . $field['value'] : null;
            }

            if ($field['type'] == 'select') {
                if (isset($field['entity'])) {
                    $field['list'] = $this->_selectEntity($field);
                } else {
                    $method = 'getList' . ucfirst($field['name']);
                    $field['list'] = $this->entity->$method();
                }
            }

            $entityName = ($this->formId != '') ? $this->formId . '_' .$this->entity->getClassName() : $this->entity->getClassName();
            $field['id'] = PttUtil::fieldId($entityName, $field['name'], $language);
            if ($field['type'] == 'image' || $field['type'] == 'file') {
                $field['check'] = PttUtil::fieldCheck($entityName, $field['name'], $language);
            }
            $entityName = ($this->formName != '') ? $this->formName . '[' . $this->entity->getClassName() . ']' : $this->entity->getClassName();
            $field['name'] = PttUtil::fieldName($entityName, $field['name'], $language);

            $info = [
                'type' => $this->_getFieldType($field),
                'params' => $field
            ];

            if (isset($field['validations'])) {
                $info['validations'] = $field['validations'];
                unset($field['validations']);
            }
            return $this->form->getTwig()->render('PttBundle:Form:factory.html.twig', $info);
        }
    }

    private function _selectEntity($field)
    {
        $options = [];
        $entities = $this->form->getContainer()->get('pttServices')->getSimpleFilter($field['entity'], [
            'where' => (isset($field['options']['filterBy']) && is_array($field['options']['filterBy'])) ? $field['options']['filterBy'] : [],
            'orderBy' => (isset($field['options']['sortBy']) && is_array($field['options']['sortBy'])) ? $field['options']['sortBy'] : ['id' => 'asc']
        ]);

        $methodKey = (isset($field['identifier'])) ? 'get' . $field['identifier'] : 'getId';
        $methodValue = 'get' . $field['field'];
        foreach ($entities as $entity) {
            $options[$entity->$methodKey()] = $entity->$methodValue();
        }

        return $options;
    }

    private function _urlPrefix($field)
    {
        if (isset($field['options']['s3']) && $field['options']['s3']) {
            return PttUtil::pttConfiguration('s3')['prodUrl'] . PttUtil::pttConfiguration('s3')['dir'] . '/';
        } else {
            return (isset($field['options']['cdn']) && $field['options']['cdn']) ? PttUtil::pttConfiguration('cdn')['prodUrl'] : PttUtil::pttConfiguration('prefix') . PttUtil::pttConfiguration('images');
        }
    }

    private function _getFieldType($field)
    {
        switch ($field['type']) {
        case 'text':
        case 'disabled':
        case 'email':
        case 'hidden':
            return 'input';
            break;
        default:
            return $field['type'];
            break;
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

    public function getForm()
    {
        return $this->form;
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
