<?php
namespace abrain\Einsatzverwaltung\CustomFields;

/**
 * Base class for additional fields of taxonomies
 * @package abrain\Einsatzverwaltung\CustomFields
 */
abstract class CustomField
{
    public $key;
    public $label;
    public $description;
    public $defaultValue;

    /**
     * CustomField constructor.
     * @param string $key
     * @param string $label
     * @param string $description
     * @param mixed $defaultValue
     */
    public function __construct($key, $label, $description, $defaultValue = false)
    {
        $this->key = $key;
        $this->label = $label;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string The markup for the form field shown when adding a new term.
     */
    public function getAddTermMarkup()
    {
        return sprintf(
            '<div class="form-field"><label for="tag-%1$s">%2$s</label>%4$s<p>%3$s</p></div>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->description),
            $this->getAddTermInput()
        );
    }

    /**
     * @param object $tag Current taxonomy term object.
     * @return string The markup for the form field shown when editing an existing term.
     */
    public function getEditTermMarkup($tag)
    {
        return sprintf(
            '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>%4$s<p class="description">%3$s</p></td></tr>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->description),
            $this->getEditTermInput($tag)
        );
    }

    /**
     * @param int $termId
     * @return mixed
     */
    public function getValue($termId)
    {
        $termMeta = get_term_meta($termId, $this->key, true);
        return (false === $termMeta ? $this->defaultValue : $termMeta);
    }

    /**
     * @return string The markup for the input shown when adding a new term.
     */
    abstract public function getAddTermInput();

    /**
     * @param int $termId
     * @return string
     */
    abstract public function getColumnContent($termId);

    /**
     * @param object $tag Current taxonomy term object.
     * @return string The markup for the input shown when editing an existing term.
     */
    abstract public function getEditTermInput($tag);
}
