<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use WP_Post;
use function add_filter;
use function strcasecmp;

/**
 * Description of the custom taxonomy 'Vehicle'
 * @package abrain\Einsatzverwaltung\Types
 */
class Vehicle implements CustomPostType
{
    /**
     * Comparison function for vehicles
     *
     * @param object $vehicle1
     * @param object $vehicle2
     *
     * @return int
     */
    public static function compareVehicles($vehicle1, $vehicle2)
    {
        $order1 = $vehicle1->vehicle_order;
        $order2 = $vehicle2->vehicle_order;

        if (empty($order1) && !empty($order2)) {
            return 1;
        }

        if (!empty($order1) && empty($order2)) {
            return -1;
        }

        // If no order is set on both or if they are equal, sort by name
        if (empty($order1) && empty($order2) || $order1 == $order2) {
            return strcasecmp($vehicle1->name, $vehicle2->name);
        }

        return ($order1 < $order2) ? -1 : 1;
    }

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
            'supports' => array('title', 'editor', 'thumbnail', 'revisions', 'custom-fields', 'author'),
            'menu_position' => 20,
            'show_in_admin_bar' => false,
            'show_in_rest' => true,
            'capability_type' => self::getSlug(),
            'map_meta_cap' => true,
            'menu_icon' => 'none',
            'rewrite' => array(
                'slug' => $this->getRewriteSlug()
            ),
            'delete_with_user' => false,
        );
    }

    /**
     * @inheritDoc
     */
    public function getRewriteSlug()
    {
        $default = _x('vehicles', 'default permalink base', 'einsatzverwaltung');
        $rewriteSlug = get_option('evw_vehicle_rewrite_slug', $default);
        return sanitize_title($rewriteSlug, $default);
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $taxonomyCustomFields)
    {
        $taxonomyCustomFields->add($this, new PostSelector(
            '_page_id',
            __('Alternative page', 'einsatzverwaltung'),
            __('Should you already have a content page about this vehicle, select it here. Visitors will be directed to that page instead.', 'einsatzverwaltung'),
            array('einsatz', Vehicle::getSlug(), 'attachment', 'ai1ec_event', 'tribe_events', 'pec-events')
        ));
        $taxonomyCustomFields->add($this, new NumberInput(
            'vehicle_order',
            'Reihenfolge',
            'Optionale Angabe, mit der die Anzeigereihenfolge der Fahrzeuge beeinflusst werden kann. Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0 in alphabetischer Reihenfolge.'
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        // Do not prepend 'Private: ' for privately published vehicles
        add_filter('private_title_format', function ($prepend, WP_Post $post) {
            if ($post->post_type === self::getSlug()) {
                $prepend = '%s';
            }

            return $prepend;
        }, 10, 2);
    }
}
