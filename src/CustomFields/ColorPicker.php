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
    public function getAddTermMarkup()
    {
        return sprintf(
            '<div class="form-field"><label for="tag-%1$s">%2$s</label><input id="tag-%1$s" type="text" value="" name="%1$s" class="einsatzverwaltung-color-picker" /><p>%3$s</p></div>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->description)
        );
    }

    /**
     * @inheritdoc
     */
    public function getEditTermMarkup($tag)
    {
        $value = $this->getValue($tag->term_id);

        return sprintf(
            '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td><input name="%1$s" id="%1$s" type="text" value="%4$s" class="einsatzverwaltung-color-picker" /><p class="description">%3$s</p></td></tr>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->description),
            esc_attr($value)
        );
    }
}
