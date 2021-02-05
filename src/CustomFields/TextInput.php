<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use function sprintf;

/**
 * Represents an additional text input of a taxonomy
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class TextInput extends CustomField
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
    public function getAddTermInput(): string
    {
        return sprintf(
            '<input id="tag-%1$s" type="text" size="40" value="" name="%1$s">',
            esc_attr($this->key)
        );
    }

    /**
     * @inheritdoc
     */
    public function getEditTermInput($tag): string
    {
        return sprintf(
            '<input name="%1$s" id="%1$s" type="text" value="%2$s" size="40" />',
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
            '<input name="%1$s" id="%1$s" type="text" value="%2$s" size="40" />',
            esc_attr($this->key),
            esc_attr($this->getValue($post->ID))
        );
    }
}
