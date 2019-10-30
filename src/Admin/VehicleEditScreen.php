<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\CustomFieldsRepository;
use abrain\Einsatzverwaltung\Types\Vehicle;
use WP_Post;
use function add_meta_box;
use function array_key_exists;
use function esc_attr;
use function esc_html;
use function printf;
use function wp_nonce_field;

/**
 * Customizations for the edit screen for the Vehicle custom post type.
 *
 * @package abrain\Einsatzverwaltung\Admin
 */
class VehicleEditScreen extends EditScreen
{
    /**
     * @var CustomFieldsRepository
     */
    private $customFieldsRepo;

    /**
     * VehicleEditScreen constructor.
     *
     * @param CustomFieldsRepository $customFieldsRepo
     */
    public function __construct(CustomFieldsRepository $customFieldsRepo)
    {
        $this->customFieldsRepo = $customFieldsRepo;
        $this->customTypeSlug = Vehicle::getSlug();
    }

    /**
     * Register meta boxes
     */
    public function addMetaBoxes()
    {
        add_meta_box(
            'meta_box_vehicle_altpage',
            __('Alternative page', 'einsatzverwaltung'),
            array($this, 'displayMetaBox'),
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
     * @param WP_Post $post The post object currently being edited
     */
    public function displayMetaBox(WP_Post $post)
    {
        wp_nonce_field('save_vehicle_meta', 'evw_vehicle_nonce');

        $customFields = $this->customFieldsRepo->getFieldsForType($this->customTypeSlug);

        if (array_key_exists('_page_id', $customFields)) {
            echo '<div class="components-panel__row">';
            $customField = $customFields['_page_id'];
            printf('<label for="%s" class="screen-reader-text">%s</label>', esc_attr($customField->key), esc_html($customField->label));
            echo $customField->getEditPostInput($post);
            echo '</div>';
            printf('<p class="description">%s</p>', esc_html($customField->description));
        }
    }
}
