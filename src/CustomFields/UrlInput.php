<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use function esc_html;
use function esc_url;
use function sprintf;

/**
 * Represents an additional URL input on a term or post
 * @package abrain\Einsatzverwaltung\CustomFields
 */
class UrlInput extends CustomField
{
    /**
     * @inheritDoc
     */
    public function __construct($key, $label, $description, $defaultValue = '')
    {
        parent::__construct($key, $label, $description, $defaultValue);
    }

    /**
     * @inheritDoc
     */
    public function getAddTermInput(): string
    {
        return sprintf(
            '<input id="tag-%1$s" type="url" value="%2$s" name="%1$s">',
            esc_attr($this->key),
            esc_url($this->defaultValue)
        );
    }

    /**
     * @inheritDoc
     */
    public function getColumnContent($termId): string
    {
        $url = $this->getValue($termId);
        if (empty($url)) {
            return '';
        }

        return sprintf(
            '<a href="%1$s">%2$s</a>',
            esc_url($url),
            esc_html($this->label)
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post): string
    {
        return sprintf(
            '<input id="%1$s" type="url" value="%2$s" name="%1$s">',
            esc_attr($this->key),
            esc_url($this->getValue($post->ID))
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditTermInput($tag): string
    {
        return sprintf(
            '<input id="tag-%1$s" type="url" value="%2$s" name="%1$s">',
            esc_attr($this->key),
            esc_url($this->getValue($tag->term_id))
        );
    }
}
