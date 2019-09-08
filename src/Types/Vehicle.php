<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFieldsRepository;

/**
 * Description of the custom taxonomy 'Vehicle'
 * @package abrain\Einsatzverwaltung\Types
 */
class Vehicle implements CustomPostType
{
    /**
     * @return string
     */
    public static function getSlug()
    {
        return 'evw_vehicle';
    }

    /**
     * @return array
     */
    private function getLabels()
    {
        return array(
            'name' => _x('Vehicles', 'post type general name', 'einsatzverwaltung'),
            'singular_name' => _x('Vehicle', 'post type singular name', 'einsatzverwaltung'),
            'menu_name' => __('Vehicles', 'einsatzverwaltung'),
            'add_new' => _x('Add New', 'Vehicle', 'einsatzverwaltung'),
            'add_new_item' => __('Add New Vehicle', 'einsatzverwaltung'),
            'edit_item' => __('Edit Vehicle', 'einsatzverwaltung'),
            'new_item' => __('New Vehicle', 'einsatzverwaltung'),
            'view_item' => __('View Vehicle', 'einsatzverwaltung'),
            'view_items' => __('View Vehicles', 'einsatzverwaltung'),
            'search_items' => __('Search Vehicles', 'einsatzverwaltung'),
            'not_found' => __('No vehicles found.', 'einsatzverwaltung'),
            'not_found_in_trash' => __('No vehicles found in Trash.', 'einsatzverwaltung'),
            'archives' => __('Vehicle Archives', 'einsatzverwaltung'),
            'attributes' => __('Vehicle Attributes', 'einsatzverwaltung'),
            'insert_into_item' => __('Insert into vehicle', 'einsatzverwaltung'),
            'uploaded_to_this_item' => __('Uploaded to this vehicle', 'einsatzverwaltung'),
            'featured_image' => _x('Featured Image', 'vehicle', 'einsatzverwaltung'),
            'set_featured_image' => _x('Set featured image', 'vehicle', 'einsatzverwaltung'),
            'remove_featured_image' => _x('Remove featured image', 'vehicle', 'einsatzverwaltung'),
            'use_featured_image' => _x('Use as featured image', 'vehicle', 'einsatzverwaltung'),
            'filter_items_list' => __('Filter vehicles list', 'einsatzverwaltung'),
            'items_list_navigation' => __('Vehicles list navigation', 'einsatzverwaltung'),
            'items_list' => __('Vehicles list', 'einsatzverwaltung'),
            'item_published' => __('Vehicle published.', 'einsatzverwaltung'),
            'item_published_privately' => __('Vehicle published privately.', 'einsatzverwaltung'),
            'item_reverted_to_draft' => __('Vehicle reverted to draft.', 'einsatzverwaltung'),
            'item_scheduled' => __('Vehicle scheduled.', 'einsatzverwaltung'),
            'item_updated' => __('Vehicle updated.', 'einsatzverwaltung'),
        );
    }

    /**
     * @return array
     */
    public function getRegistrationArgs()
    {
        return array(
            'labels' => $this->getLabels(),
            'public' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'author', 'page-attributes'),
            'menu_position' => 20,
            'show_in_admin_bar' => false,
            'show_in_rest' => true,
            'capability_type' => self::getSlug(),
            'map_meta_cap' => true,
            'menu_icon' => 'none',
            'delete_with_user' => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $taxonomyCustomFields)
    {
        $taxonomyCustomFields->add($this, new PostSelector(
            '_page_id',
            'Fahrzeugseite',
            'Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.',
            array('einsatz', 'attachment', 'ai1ec_event', 'tribe_events', 'pec-events')
        ));
        $taxonomyCustomFields->add($this, new NumberInput(
            'menu_order',
            'Reihenfolge',
            'Optionale Angabe, mit der die Anzeigereihenfolge der Fahrzeuge beeinflusst werden kann. Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0 in alphabetischer Reihenfolge.'
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
    }
}
