<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\CustomFields\Checkbox;
use abrain\Einsatzverwaltung\CustomFields\ColorPicker;
use abrain\Einsatzverwaltung\CustomFields\MediaSelector;
use abrain\Einsatzverwaltung\CustomFields\StringList;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use WP_REST_Response;
use WP_Screen;
use function __;
use function add_filter;

/**
 * Description of the custom taxonomy 'Type of incident'
 * @package abrain\Einsatzverwaltung\Types
 */
class IncidentType extends CustomTaxonomy
{
    /**
     * @return string
     */
    public static function getSlug(): string
    {
        return 'einsatzart';
    }

    /**
     * @return array
     */
    public function getRegistrationArgs(): array
    {
        return array(
            'labels' => array(
                'name' => _x('Incident Categories', 'taxonomy general name', 'einsatzverwaltung'),
                'singular_name' => _x('Incident Category', 'taxonomy singular name', 'einsatzverwaltung'),
                'menu_name' => _x('Incident Categories', 'menu name', 'einsatzverwaltung'),
                'search_items' => __('Search Incident Categories', 'einsatzverwaltung'),
                'all_items' => __('All Incident Categories', 'einsatzverwaltung'),
                'parent_item' => __('Parent Incident Category', 'einsatzverwaltung'),
                'parent_item_colon' => __('Parent Incident Category:', 'einsatzverwaltung'),
                'parent_field_description' => __('Assign a parent Incident Category to create a hierarchy.', 'einsatzverwaltung'),
                'edit_item' => __('Edit Incident Category', 'einsatzverwaltung'),
                'view_item' => __('View Incident Category', 'einsatzverwaltung'),
                'update_item' => __('Update Incident Category', 'einsatzverwaltung'),
                'add_new_item' => __('Add New Incident Category', 'einsatzverwaltung'),
                'new_item_name' => __('New Incident Category Name', 'einsatzverwaltung'),
                'not_found' => __('No Incident Categories found.', 'einsatzverwaltung'),
                'no_terms' => __('No Incident Categories', 'einsatzverwaltung'),
                'items_list_navigation' => __('Incident Categories list navigation', 'einsatzverwaltung'),
                'items_list' => __('Incident Categories list', 'einsatzverwaltung'),
                /* translators: Tab heading when selecting from the most used terms. */
                'most_used' => _x('Most Used', 'incident categories', 'einsatzverwaltung'),
                'back_to_items' => __('&larr; Go to Incident Categories', 'einsatzverwaltung'),
            ),
            'public' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => true,
            'meta_box_cb' => false,
            'capabilities' => array(
                'manage_terms' => 'edit_einsatzberichte',
                'edit_terms' => 'edit_einsatzberichte',
                'delete_terms' => 'edit_einsatzberichte',
                'assign_terms' => 'edit_einsatzberichte'
            ),
            'hierarchical' => true
        );
    }

    /**
     * @inheritDoc
     */
    public function getRewriteSlug(): string
    {
        return self::getSlug();
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $customFields)
    {
        $customFields->add($this, new ColorPicker(
            'typecolor',
            __('Color', 'einsatzverwaltung'),
            'Ordne dieser Einsatzart eine Farbe zu. Einsatzarten ohne Farbe erben diese gegebenenfalls von übergeordneten Einsatzarten.'
        ));
        $customFields->add($this, new MediaSelector(
            'default_featured_image',
            __('Default featured image', 'einsatzverwaltung'),
            __('If a report in this Incident Category has no featured image, this image is shown instead.', 'einsatzverwaltung')
        ));
        $customFields->add($this, new Checkbox(
            'outdated',
            __('Outdated', 'einsatzverwaltung'),
            __('This Incident Category is no longer used', 'einsatzverwaltung'),
            __('Outdated categories can still be assigned to reports, they just get moved to the end of the list. Existing reports will not be changed.', 'einsatzverwaltung'),
            '0'
        ));
        $customFields->add($this, new StringList(
            'altname',
            __('Alternative identifiers', 'einsatzverwaltung'),
            __('A list of identifiers that are synonymous with this Incident Category. They will be used to find exisiting incident categories when reports are created via the API. One identifier per line.', 'einsatzverwaltung')
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        /**
         * Prevent the Gutenberg Editor from creating a UI for this taxonomy, so we can use our own
         * https://github.com/WordPress/gutenberg/issues/6912#issuecomment-428403380
         */
        add_filter('rest_prepare_taxonomy', function (WP_REST_Response $response, $taxonomy) {
            if ('einsatzart' === $taxonomy->name) {
                $response->data['visibility']['show_ui'] = false;
            }
            return $response;
        }, 10, 2);

        add_action('admin_enqueue_scripts', function () {
            $screen = get_current_screen();
            if ($screen === false) {
                return;
            }

            // Enqueue the scripts to handle media upload and selection
            if ($screen->taxonomy === self::getSlug() && in_array($screen->base, array('edit-tags', 'term'))) {
                wp_enqueue_media();
                wp_enqueue_script('einsatzverwaltung-media-selector', Core::$scriptUrl . 'media-selector.js');
            }
        });

        add_filter('default_hidden_columns', function (array $hiddenColumns, WP_Screen $screen) {
            if ($screen->taxonomy === self::getSlug()) {
                $hiddenColumns[] = 'altname';
                $hiddenColumns[] = 'default_featured_image';
            }
            return $hiddenColumns;
        }, 10, 2);
    }
}
