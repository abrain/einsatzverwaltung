<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\CustomFieldsRepository;
use abrain\Einsatzverwaltung\Types\Unit;
use WP_Post;
use function add_meta_box;
use function array_key_exists;
use function esc_html;
use function printf;
use function wp_nonce_field;

/**
 * Customizations for the edit screen for the Unit custom post type.
 *
 * @package abrain\Einsatzverwaltung\Admin
 */
class UnitEditScreen extends EditScreen
{
    /**
     * @var CustomFieldsRepository
     */
    private $customFields;

    /**
     * UnitEditScreen constructor.
     *
     * @param CustomFieldsRepository $customFields
     */
    public function __construct(CustomFieldsRepository $customFields)
    {
        $this->customTypeSlug = Unit::getSlug();
        $this->customFields = $customFields;
    }

    public function addMetaBoxes()
    {
        add_meta_box(
            'einsatzverwaltung_unit_links',
            __('Linking', 'einsatzverwaltung'),
            array($this, 'displayMetaBoxLinking'),
            $this->customTypeSlug,
            'side',
            'default',
            array(
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => false
            )
        );
    }

    /**
     * @param WP_Post $post
     */
    public function displayMetaBoxLinking(WP_Post $post)
    {
        wp_nonce_field('save_evw_unit_details', 'einsatzverwaltung_nonce');

        $fields = $this->customFields->getFieldsForType($this->customTypeSlug);

        foreach (array('unit_pid', 'unit_exturl') as $key) {
            if (!array_key_exists($key, $fields)) {
                continue;
            }

            $customField = $fields[$key];

            echo '<div>';
            printf('<label for="%s">%s</label>', esc_attr($customField->key), esc_html($customField->label));
            echo $customField->getEditPostInput($post);
            echo '</div>';
            printf('<p class="description">%s</p>', esc_html($customField->description));
        }
    }
}
