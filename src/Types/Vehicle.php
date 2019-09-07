<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFieldsRepository;

/**
 * Description of the custom taxonomy 'Vehicle'
 * @package abrain\Einsatzverwaltung\Types
 */
class Vehicle implements CustomTaxonomy
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
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $taxonomyCustomFields)
    {
        $taxonomyCustomFields->addPostSelector($this, new PostSelector(
            'fahrzeugpid',
            'Fahrzeugseite',
            'Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.',
            array('einsatz', 'attachment', 'ai1ec_event', 'tribe_events', 'pec-events')
        ));
        $taxonomyCustomFields->addNumberInput($this, new NumberInput(
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
    }
}
