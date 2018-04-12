<?php
namespace abrain\Einsatzverwaltung\CustomFields;

/**
 * Represents an additional number input of a taxonomy
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class NumberInput extends CustomField
{
    /**
     * @inheritDoc
     */
    public function __construct($key, $label, $description, $defaultValue = 0)
    {
        parent::__construct($key, $label, $description, $defaultValue);
    }

    /**
     * @inheritdoc
     */
    public function getAddTermInput()
    {
        return sprintf(
            '<input id="tag-%1$s" type="number" min="0" value="%2$d" name="%1$s">',
            esc_attr($this->key),
            esc_attr($this->defaultValue)
        );
    }

    /**
     * @inheritdoc
     */
    public function getEditTermInput($tag)
    {
        return sprintf(
            '<input id="tag-%1$s" type="number" min="0" value="%2$d" name="%1$s">',
            esc_attr($this->key),
            esc_attr($this->getValue($tag->term_id))
        );
    }
}
