<?php
namespace abrain\Einsatzverwaltung\CustomFields;

/**
 * Represents an additional color picker of a taxonomy
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class ColorPicker extends CustomField
{
    /**
     * @inheritDoc
     */
    public function __construct($key, $label, $description, $defaultValue = '')
    {
        parent::__construct($key, $label, $description, $defaultValue);
    }

    /**
     * @inheritdoc
     */
    public function getAddTermInput()
    {
        return sprintf(
            '<input id="tag-%1$s" type="text" value="" name="%1$s" class="einsatzverwaltung-color-picker" />',
            esc_attr($this->key)
        );
    }

    /**
     * @inheritdoc
     */
    public function getEditTermInput($tag)
    {
        return sprintf(
            '<input name="%1$s" id="%1$s" type="text" value="%2$s" class="einsatzverwaltung-color-picker" />',
            esc_attr($this->key),
            esc_attr($this->getValue($tag->term_id))
        );
    }

    /**
     * @inheritdoc
     */
    public function getColumnContent($termId)
    {
        $value = $this->getValue($termId);
        if (empty($value)) {
            return '';
        }

        return sprintf(
            '<div style="width: 20px; height: 20px; border: 1px solid black; background-color: %s">&nbsp;</div>',
            esc_attr($value)
        );
    }
}
