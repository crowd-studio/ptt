<?php

namespace Crowd\PttBundle\Util;

use Crowd\PttBundle\Form\PttEntityInfo;
use Crowd\PttBundle\Form\PttForm;
use Crowd\PttBundle\Form\PttClassNameGenerator;

class PttFormSave
{
    private $entityInfo;
    private $fields;
    private $languages;
    private $form;
    private $entity;
    private $sentData;
    private $userId;

    public function __construct(PttForm $form, $entity, $fields, $sentData)
    {
        $this->form = $form;
        $this->entityInfo = $form->getEntityInfo();
        $this->languages = $form->getLanguages();
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
            $this->entity->setSlug(PttUtil::slugify((string)$this->entityInfo->getEntity()));
        }

        if (is_subclass_of($this->entity, 'Crowd\PttBundle\Entity\PttEntity')) {
            $this->entityInfo->set('updateObjectValues', $this->userId);
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
            return PttUtil::getFieldData($this->sentData, $entity->getClassName(), $fieldName, null, $languageCode);
        } else {
            return $this->sentData;
        }
    }

    private function _performFieldsLoopAndCallMethodNamed($nameOfMethod)
    {
        foreach ($this->fields['block'] as $key => $block) {
            if ($block['static']) {
                foreach ($block['static'] as $field) {
                    PttClassNameGenerator::saveForField($field, $this->entity->getClassName(), $this->entityInfo, $this->form->getRequest(), $this->sentData, $this->form->getContainer(), false);
                }
            }

            if ($this->languages && isset($block['trans'])) {
                foreach ($this->languages as $language) {
                    foreach ($block['trans'] as $field) {
                        PttClassNameGenerator::saveForField($field, $this->entity->getClassName(), $this->entityInfo, $this->form->getRequest(), $this->sentData, $this->form->getContainer(), $language->getCode());
                    }
                }
            }
        }
    }
}
