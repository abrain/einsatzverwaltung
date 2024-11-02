<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use function checked;
use function esc_html;
use function sprintf;

/**
 * Represents a custom checkbox on a term or post
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class Checkbox extends CustomField
{
    /**
     * @var string
     */
    private $checkboxLabel;

    /**
     * @inheritDoc
     */
    public function __construct($key, $label, $checkboxLabel, $description, $defaultValue = false)
    {
        parent::__construct($key, $label, $description, $defaultValue);
        $this->checkboxLabel = $checkboxLabel;
    }

    /**
     * @inheritDoc
     */
    public function getAddTermInput(): string
    {
        return sprintf(
            '<input id="tag-%1$s" type="checkbox" value="1" name="%1$s" %2$s />',
            esc_attr($this->key),
            checked($this->defaultValue, '1', false)
        );
    }

    /**
     * @inheritDoc
     */
    public function getAddTermMarkup(): string
    {
        return sprintf(
            '<div class="form-field"><span class="fakelabel">%2$s</span>%4$s<label for="tag-%1$s" class="checkboxlabel">%3$s</label><p>%5$s</p></div>',
            esc_attr($this->key),
            esc_html($this->label),
            esc_html($this->checkboxLabel),
            $this->getAddTermInput(),
            esc_html($this->description)
        );
    }

    /**
     * @inheritDoc
     */
    public function getColumnContent($termId): string
    {
        $value = $this->getValue($termId);
        return ($value === '1' ? __('Yes', 'einsatzverwaltung') : __('No', 'einsatzverwaltung'));
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post): string
    {
        return sprintf(
            '<input name="%1$s" id="%1$s" type="checkbox" value="1" %2$s />',
            esc_attr($this->key),
            checked($this->getValue($post->ID), '1', false)
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditTermInput($tag): string
    {
        return sprintf(
            '<input name="%1$s" id="%1$s" type="checkbox" value="1" %2$s />',
            esc_attr($this->key),
            checked($this->getValue($tag->term_id), '1', false)
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditTermMarkup($tag): string
    {
        return sprintf(
            '<tr class="form-field"><th scope="row">%2$s</th><td>%3$s<label for="%1$s">%4$s</label><p class="description">%5$s</p></td></tr>',
            esc_attr($this->key),
            esc_html($this->label),
            $this->getEditTermInput($tag),
            esc_html($this->checkboxLabel),
            esc_html($this->description)
        );
    }
}
