<?php
namespace abrain\Einsatzverwaltung\CustomFields;

/**
 * Base class for additional fields of taxonomies
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class CustomField
{
    public $key;
    protected $label;
    protected $description;
    protected $defaultValue;

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
     * @return string
     */
    public function getAddTermMarkup()
    {
        return '<span class="evw_error">NOT IMPLEMENTED</span>';
    }

    /**
     * @param object $tag
     * @return string
     */
    public function getEditTermMarkup($tag)
    {
        return '<span class="evw_error">NOT IMPLEMENTED</span>';
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
}
