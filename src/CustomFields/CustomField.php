<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use WP_Term;
use function get_post;
use function get_post_meta;
use function get_term_meta;
use function intval;
use function term_exists;

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
     * @var bool
     */
    private $multiValue;

    /**
     * CustomField constructor.
     *
     * @param string $key
     * @param string $label
     * @param string $description
     * @param mixed $defaultValue
     * @param bool $multiValue
     */
    public function __construct(string $key, string $label, string $description, $defaultValue = false, bool $multiValue = false)
    {
        $this->key = $key;
        $this->label = $label;
        $this->description = $description;
        $this->defaultValue = $defaultValue;
        $this->multiValue = $multiValue;
    }

    /**
     * @return string The markup for the form field shown when adding a new term.
     */
    public function getAddTermMarkup(): string
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
     * @param WP_Term $tag Current taxonomy term object.
     * @return string The markup for the form field shown when editing an existing term.
     */
    public function getEditTermMarkup($tag): string
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
     * @param int $objectId
     *
     * @return mixed
     */
    public function getValue($objectId)
    {
        $value = '';
        if (term_exists(intval($objectId)) !== null) {
            $value = get_term_meta($objectId, $this->key, true);
        } elseif (get_post($objectId) instanceof WP_Post) {
            $value = get_post_meta($objectId, $this->key, true);
        }

        return (false === $value ? $this->defaultValue : $value);
    }

    /**
     * @param int $objectId
     *
     * @return array
     */
    public function getValues(int $objectId): array
    {
        $values = [];
        if (term_exists($objectId) !== null) {
            $values = get_term_meta($objectId, $this->key, false);
        } elseif (get_post($objectId) instanceof WP_Post) {
            $values = get_post_meta($objectId, $this->key, false);
        }

        return (false === $values ? $this->defaultValue : $values);
    }

    /**
     * @return string The markup for the input shown when adding a new term.
     */
    abstract public function getAddTermInput(): string;

    /**
     * @param int $termId
     * @return string
     */
    abstract public function getColumnContent($termId): string;

    /**
     * @param WP_Post $post Currently edited post object
     * @return string HTML markup for the input
     */
    abstract public function getEditPostInput(WP_Post $post): string;

    /**
     * @param WP_Term $tag Current taxonomy term object.
     * @return string The markup for the input shown when editing an existing term.
     */
    abstract public function getEditTermInput($tag): string;

    /**
     * @return bool
     */
    public function isMultiValue(): bool
    {
        return $this->multiValue;
    }
}
