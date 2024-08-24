<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use function __;
use function esc_attr;
use function sprintf;
use function wp_get_attachment_image_url;

/**
 * Represents a single image from
 */
class MediaSelector extends CustomField
{
    /**
     * ImageSelector constructor.
     *
     * @param string $key
     * @param string $label
     * @param string $description
     */
    public function __construct(string $key, string $label, string $description)
    {
        parent::__construct($key, $label, $description, '0');
    }

    /**
     * @inheritDoc
     */
    public function getAddTermInput(): string
    {
        return sprintf(
            '<input id="%1$s" name="%2$s" type="hidden" value=""><img id="img-%1$s" src="" alt=""><br><input type="button" class="button" onclick="einsatzverwaltung_selectMedia(\'%1$s\')" value="%3$s"/> <input type="button" class="button" onclick="einsatzverwaltung_clearMedia(\'%1$s\')" value="%4$s"/>',
            esc_attr('evw-mediasel-' . $this->key),
            esc_attr($this->key),
            __('Select Image', 'einsatzverwaltung'),
            __('Clear Image', 'einsatzverwaltung')
        );
    }

    /**
     * @inheritDoc
     */
    public function getColumnContent($termId): string
    {
        $imageId = $this->getValue($termId);
        if (empty($imageId) || $imageId === $this->defaultValue) {
            return '';
        }

        return '<span class="fa-solid fa-image"></span>';
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post): string
    {
        return 'not yet implemented';
    }

    /**
     * @inheritDoc
     */
    public function getEditTermInput($term): string
    {
        $imageId = $this->getValue($term->term_id);
        if (empty($imageId)) {
            $imageId = $this->defaultValue;
            $previewUrl = '';
        } else {
            $previewUrl = wp_get_attachment_image_url($imageId);
            if ($previewUrl === false) {
                $previewUrl = '';
            }
        }

        return sprintf(
            '<input id="%1$s" name="%2$s" type="hidden" value="%4$d"><img id="img-%1$s" src="%5$s" alt=""><br><input type="button" class="button" onclick="einsatzverwaltung_selectMedia(\'%1$s\')" value="%3$s"/> <input type="button" class="button" onclick="einsatzverwaltung_clearMedia(\'%1$s\')" value="%6$s"/>',
            esc_attr('evw-mediasel-' . $this->key),
            esc_attr($this->key),
            __('Select Image', 'einsatzverwaltung'),
            esc_attr($imageId),
            esc_attr($previewUrl),
            __('Clear Image', 'einsatzverwaltung')
        );
    }
}
