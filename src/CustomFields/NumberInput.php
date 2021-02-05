<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use function sprintf;

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
    public function getAddTermInput(): string
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
    public function getEditTermInput($tag): string
    {
        return sprintf(
            '<input id="tag-%1$s" type="number" min="0" value="%2$d" name="%1$s">',
            esc_attr($this->key),
            esc_attr($this->getValue($tag->term_id))
        );
    }

    /**
     * @inheritdoc
     */
    public function getColumnContent($termId): string
    {
        return esc_html($this->getValue($termId));
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post): string
    {
        return sprintf(
            '<input id="%1$s" type="number" min="0" value="%2$d" name="%1$s">',
            esc_attr($this->key),
            esc_attr($this->getValue($post->ID))
        );
    }
}
