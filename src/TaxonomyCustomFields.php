<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\CustomFields\ColorPicker;
use abrain\Einsatzverwaltung\CustomFields\CustomField;
use abrain\Einsatzverwaltung\CustomFields\TextInput;

/**
 * Class TaxonomyCustomFields
 * @package abrain\Einsatzverwaltung
 */
class TaxonomyCustomFields
{
    /**
     * @var array
     */
    private $fields;

    /**
     * TaxonomyCustomFields constructor.
     */
    public function __construct()
    {
        require 'CustomFields/CustomField.php';
        require 'CustomFields/ColorPicker.php';
        require 'CustomFields/TextInput.php';

        $this->fields = array();

        add_action('edited_term', array($this, 'saveTerm'), 10, 3);
        add_action('created_term', array($this, 'saveTerm'), 10, 3);
    }

    /**
     * @param string $taxonomy The slug of the taxonomy.
     * @param TextInput $textInput
     */
    public function addTextInput($taxonomy, TextInput $textInput)
    {
        $this->add($taxonomy, $textInput);
    }

    /**
     * @param string $taxonomy The slug of the taxonomy.
     * @param ColorPicker $colorPicker
     */
    public function addColorpicker($taxonomy, ColorPicker $colorPicker)
    {
        $this->add($taxonomy, $colorPicker);
    }

    /**
     * @param string $taxonomy
     * @param CustomField $customField
     */
    private function add($taxonomy, CustomField $customField)
    {
        if (!array_key_exists($taxonomy, $this->fields)) {
            $this->fields[$taxonomy] = array();
            add_action("{$taxonomy}_add_form_fields", array($this, 'onAddFormFields'));
            add_action("{$taxonomy}_edit_form_fields", array($this, 'onEditFormFields'), 10, 2);
        }

        $this->fields[$taxonomy][] = $customField;
    }

    /**
     * @param string $taxonomy The taxonomy slug.
     */
    public function onAddFormFields($taxonomy)
    {
        if (!array_key_exists($taxonomy, $this->fields) || !is_array($this->fields[$taxonomy])) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->fields[$taxonomy] as $field) {
            echo $field->getAddTermMarkup();
        }
    }

    /**
     * @param object $tag Current taxonomy term object.
     * @param string $taxonomy Current taxonomy slug.
     */
    public function onEditFormFields($tag, $taxonomy)
    {
        if (!array_key_exists($taxonomy, $this->fields) || !is_array($this->fields[$taxonomy])) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->fields[$taxonomy] as $field) {
            echo $field->getEditTermMarkup($tag);
        }
    }

    /**
     * Speichert zusätzliche Infos zu Terms als options ab
     *
     * @param int $termId Term ID
     * @param int $ttId Term taxonomy ID
     * @param string $taxonomy Taxonomy slug
     */
    public function saveTerm($termId, $ttId, $taxonomy)
    {
        if (!array_key_exists($taxonomy, $this->fields) || !is_array($this->fields[$taxonomy])) {
            return;
        }

        /** @var CustomField $field */
        foreach ($this->fields[$taxonomy] as $field) {
            $value = filter_input(INPUT_POST, $field->key, FILTER_SANITIZE_STRING);

            if (!empty($value)) {
                update_term_meta($termId, $field->key, $value);
            }
        }
    }
}
