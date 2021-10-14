<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use function array_map;
use function array_values;
use function esc_textarea;
use function implode;
use function sort;
use function sprintf;

/**
 * Stores a list of strings as separate meta values for better lookup
 */
class StringList extends CustomField
{
    /**
     * @inheritDoc
     */
    public function __construct(string $key, string $label, string $description, $defaultValue = [])
    {
        parent::__construct($key, $label, $description, $defaultValue, true);
    }

    /**
     * @inheritDoc
     */
    public function getAddTermInput(): string
    {
        $defaultNames = array_values($this->defaultValue);
        sort($defaultNames);

        return sprintf(
            '<textarea id="tag-%1$s" name="%1$s" rows="4">%2$s</textarea>',
            esc_attr($this->key),
            esc_textarea(implode("\n", $defaultNames))
        );
    }

    /**
     * @inheritDoc
     */
    public function getColumnContent($termId): string
    {
        $names = array_values($this->getValues($termId));
        if (empty($names)) {
            return '';
        }

        sort($names);
        $cleanNames = array_map('esc_html', $names);
        return sprintf(
            '<ul><li>%1$s</li></ul>',
            implode("</li><li>", $cleanNames)
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post): string
    {
        $names = array_values($this->getValues($post->ID));
        sort($names);

        return sprintf(
            '<textarea id="%1$s" name="%1$s" rows="4">%2$s</textarea>',
            esc_attr($this->key),
            esc_textarea(implode("\n", $names))
        );
    }

    /**
     * @inheritDoc
     */
    public function getEditTermInput($tag): string
    {
        $names = array_values($this->getValues($tag->term_id));
        sort($names);

        return sprintf(
            '<textarea id="tag-%1$s" name="%1$s" rows="4">%2$s</textarea>',
            esc_attr($this->key),
            esc_textarea(implode("\n", $names))
        );
    }
}
