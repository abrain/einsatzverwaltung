<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use WP_REST_Response;
use WP_Term;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use function strcasecmp;

/**
 * Description of the custom taxonomy 'Vehicle'
 * @package abrain\Einsatzverwaltung\Types
 */
class Vehicle implements CustomTaxonomy
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
        return 'fahrzeug';
    }

    /**
     * @return array
     */
    public function getRegistrationArgs()
    {
        return array(
            'label' => 'Fahrzeuge',
            'labels' => array(
                'name' => 'Fahrzeuge',
                'singular_name' => 'Fahrzeug',
                'menu_name' => 'Fahrzeuge',
                'search_items' => 'Fahrzeuge suchen',
                'popular_items' => 'H&auml;ufig eingesetzte Fahrzeuge',
                'all_items' => 'Alle Fahrzeuge',
                'parent_item' => '&Uuml;bergeordnete Einheit',
                'parent_item_colon' => '&Uuml;bergeordnete Einheit:',
                'edit_item' => 'Fahrzeug bearbeiten',
                'view_item' => 'Fahrzeug ansehen',
                'update_item' => 'Fahrzeug aktualisieren',
                'add_new_item' => 'Neues Fahrzeug',
                'new_item_name' => 'Fahrzeug hinzuf&uuml;gen',
                'not_found' => 'Keine Fahrzeuge gefunden.',
                'no_terms' => 'Keine Fahrzeuge',
                'items_list_navigation' => 'Navigation der Fahrzeugliste',
                'items_list' => 'Fahrzeugliste',
                'back_to_items' => '&larr; Zur&uuml;ck zu den Fahrzeugen',
            ),
            'public' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => true,
            'meta_box_cb' => false,
            'hierarchical' => true,
            'capabilities' => array(
                'manage_terms' => 'edit_einsatzberichte',
                'edit_terms' => 'edit_einsatzberichte',
                'delete_terms' => 'edit_einsatzberichte',
                'assign_terms' => 'edit_einsatzberichte'
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getRewriteSlug()
    {
        return self::getSlug();
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $customFields)
    {
        $customFields->add($this, new PostSelector(
            'fahrzeugpid',
            'Fahrzeugseite',
            'Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.',
            array('einsatz', 'attachment', 'ai1ec_event', 'tribe_events', 'pec-events')
        ));
        $customFields->add($this, new NumberInput(
            'vehicleorder',
            'Reihenfolge',
            'Optionale Angabe, mit der die Anzeigereihenfolge der Fahrzeuge beeinflusst werden kann. Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0 in alphabetischer Reihenfolge.'
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        add_action("{$this->getSlug()}_pre_add_form", array($this, 'deprectatedHierarchyNotice'));
        add_action('admin_menu', array($this, 'addBadgeToMenu'));

        /**
         * Prevent the Gutenberg Editor from creating a UI for this taxonomy, so we can use our own
         * https://github.com/WordPress/gutenberg/issues/6912#issuecomment-428403380
         */
        add_filter('rest_prepare_taxonomy', function (WP_REST_Response $response, $taxonomy) {
            if (self::getSlug() === $taxonomy->name) {
                $response->data['visibility']['show_ui'] = false;
            }
            return $response;
        }, 10, 2);
    }

    /**
     * Check if there are vehicles with parents, as that is now deprecated
     */
    public function deprectatedHierarchyNotice()
    {
        $termsWithParentCount = $this->getTermsWithParentCount();
        if ($termsWithParentCount > 0) {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html__('The vehicles will soon be reworked and only vehicles without children will remain. Please use the recently introduced Units instead.', 'einsatzverwaltung')
            );
        }
    }

    public function addBadgeToMenu()
    {
        global $submenu;
        $termsWithParentCount = $this->getTermsWithParentCount();
        if ($termsWithParentCount > 0) {
            $submenuKey = 'edit.php?post_type=' . Report::getSlug();
            if (array_key_exists($submenuKey, $submenu)) {
                $vehicleEntry = array_filter($submenu[$submenuKey], function ($entry) {
                    return $entry[2] === 'edit-tags.php?taxonomy=fahrzeug&amp;post_type=einsatz';
                });

                foreach ($vehicleEntry as $id => $entry) {
                    $entry[0] .= sprintf(
                        ' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
                        esc_html($termsWithParentCount)
                    );
                    $submenu[$submenuKey][$id] = $entry;
                }
            }
        }
    }

    /**
     * @return int Returns the number of terms in this taxonomy that have a parent term.
     */
    private function getTermsWithParentCount()
    {
        $terms = get_terms(array('taxonomy' => self::getSlug(), 'hide_empty' => false));
        $childTerms = array_filter($terms, function (WP_Term $term) {
            return $term->parent !== 0;
        });
        return count($childTerms);
    }
}
