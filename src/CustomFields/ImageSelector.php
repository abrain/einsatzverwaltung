<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use WP_Term;
use function __;
use function array_key_exists;
use function esc_attr;
use function get_option;
use function intval;
use function sprintf;
use function wp_get_additional_image_sizes;
use function wp_get_attachment_image_url;

class ImageSelector extends CustomField
{
    /**
     * The expected height of the image.
     *
     * @var int
     */
    private $height;

    /**
     * @var string
     */
    private $imageSizeName;

    /**
     * The expected width of the image.
     *
     * @var int
     */
    private $width;

    /**
     * ImageSelector constructor.
     *
     * @param string $key
     * @param string $label
     * @param string $description
     * @param string $imageSizeName The name of a custom image size. Falls back to thumbnail if unknown.
     */
    public function __construct($key, $label, $description, $imageSizeName)
    {
        parent::__construct($key, $label, $description, '-1');
        $additionalImageSizes = wp_get_additional_image_sizes();
        if (array_key_exists($imageSizeName, $additionalImageSizes)) {
            $this->imageSizeName = $imageSizeName;
            $imageSize = $additionalImageSizes[$imageSizeName];
            $this->height = $imageSize['height'];
            $this->width = $imageSize['width'];
        } else {
            $this->imageSizeName = 'thumbnail';
            $this->height = intval(get_option('thumbnail_size_h', 150));
            $this->width = intval(get_option('thumbnail_size_w', 150));
        }
    }

    /**
     * @inheritDoc
     */
    public function getAddTermInput()
    {
        return sprintf(
            '<input id="%1$s" name="%2$s" type="hidden" value=""><img id="img-%1$s" src="" alt=""><br><input type="button" class="button" onclick="selectVehicleMedia(\'%1$s\', \'%4$s\')" value="%3$s"/> <input type="button" class="button" onclick="clearVehicleMedia(\'%1$s\')" value="%5$s"/>',
            esc_attr('evw-mediasel-' . $this->key),
            esc_attr($this->key),
            __('Select Image', 'einsatzverwaltung'),
            esc_attr($this->imageSizeName),
            __('Clear Image', 'einsatzverwaltung')
        );
    }

    /**
     * @inheritDoc
     */
    public function getColumnContent($termId)
    {
        $imageId = $this->getValue($termId);
        if (empty($imageId) || $imageId === $this->defaultValue) {
            return '';
        }

        return '<span class="fa fa-picture-o"></span>';
    }

    /**
     * @inheritDoc
     */
    public function getEditPostInput(WP_Post $post)
    {
        // TODO: Implement getEditPostInput() method.
    }

    /**
     * @inheritDoc
     */
    public function getEditTermInput(WP_Term $term)
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
            '<input id="%1$s" name="%2$s" type="hidden" value="%4$d"><img id="img-%1$s" src="%5$s" alt=""><br><input type="button" class="button" onclick="selectVehicleMedia(\'%1$s\', \'%6$s\')" value="%3$s"/> <input type="button" class="button" onclick="clearVehicleMedia(\'%1$s\')" value="%7$s"/>',
            esc_attr('evw-mediasel-' . $this->key),
            esc_attr($this->key),
            __('Select Image', 'einsatzverwaltung'),
            esc_attr($imageId),
            esc_attr($previewUrl),
            esc_attr($this->imageSizeName),
            __('Clear Image', 'einsatzverwaltung')
        );
    }
}
