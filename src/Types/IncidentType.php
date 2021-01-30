<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\Checkbox;
use abrain\Einsatzverwaltung\CustomFields\ColorPicker;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use WP_REST_Response;
use function __;

/**
 * Description of the custom taxonomy 'Type of incident'
 * @package abrain\Einsatzverwaltung\Types
 */
class IncidentType implements CustomTaxonomy
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
            'label' => 'Einsatzarten',
            'labels' => array(
                'name' => 'Einsatzarten',
                'singular_name' => 'Einsatzart',
                'menu_name' => 'Einsatzarten',
                'search_items' => 'Einsatzarten suchen',
                'popular_items' => 'H&auml;ufige Einsatzarten',
                'all_items' => 'Alle Einsatzarten',
                'parent_item' => '&Uuml;bergeordnete Einsatzart',
                'parent_item_colon' => '&Uuml;bergeordnete Einsatzart:',
                'edit_item' => 'Einsatzart bearbeiten',
                'view_item' => 'Einsatzart ansehen',
                'update_item' => 'Einsatzart aktualisieren',
                'add_new_item' => 'Neue Einsatzart',
                'new_item_name' => 'Einsatzart hinzuf&uuml;gen',
                'separate_items_with_commas' => 'Einsatzarten mit Kommas trennen',
                'add_or_remove_items' => 'Einsatzarten hinzuf&uuml;gen oder entfernen',
                'choose_from_most_used' => 'Aus h&auml;ufigen Einsatzarten w&auml;hlen',
                'not_found' => 'Keine Einsatzarten gefunden.',
                'no_terms' => 'Keine Einsatzarten',
                'items_list_navigation' => 'Navigation der Liste der Einsatzarten',
                'items_list' => 'Liste der Einsatzarten',
                'back_to_items' => '&larr; Zur&uuml;ck zu den Einsatzarten',
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
            'Farbe',
            'Ordne dieser Einsatzart eine Farbe zu. Einsatzarten ohne Farbe erben diese gegebenenfalls von übergeordneten Einsatzarten.'
        ));
        $customFields->add($this, new Checkbox(
            'outdated',
            __('Outdated', 'einsatzverwaltung'),
            __('This Incident Category is no longer used', 'einsatzverwaltung'),
            __('Outdated categories can still be assigned to reports, they just get moved to the end of the list. Existing reports will not be changed.', 'einsatzverwaltung'),
            '0'
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
    }
}
