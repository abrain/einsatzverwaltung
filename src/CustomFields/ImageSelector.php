<?php
namespace abrain\Einsatzverwaltung\CustomFields;

use WP_Post;
use WP_Term;
use function __;
use function esc_attr;

class ImageSelector extends CustomField
{
    /**
     * @inheritDoc
     */
    public function getAddTermInput()
    {
        return sprintf(
            '<input id="%1$s" name="%2$s" type="hidden" value=""><img id="img-%1$s" src="" alt=""><input type="button" onclick="selectVehicleMedia(\'%1$s\')" value="%3$s"/>',
            esc_attr('evw-mediasel-' . $this->key),
            esc_attr($this->key),
            __('Select Image', 'einsatzverwaltung')
        );
    }

    /**
     * @inheritDoc
     */
    public function getColumnContent($termId)
    {
        // TODO: Implement getColumnContent() method.
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
        // TODO: Implement getEditTermInput() method.
    }
}
