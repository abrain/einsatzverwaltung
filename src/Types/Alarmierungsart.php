<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFieldsRepository;

/**
 * Description of the custom taxonomy 'Alarmierungsart'
 * @package abrain\Einsatzverwaltung\Types
 */
class Alarmierungsart implements CustomTaxonomy
{
    /**
     * @return string
     */
    public static function getSlug(): string
    {
        return 'alarmierungsart';
    }

    /**
     * @return array
     */
    public function getRegistrationArgs(): array
    {
        return array(
            'label' => 'Alarmierungsart',
            'labels' => array(
                'name' => 'Alarmierungsarten',
                'singular_name' => 'Alarmierungsart',
                'menu_name' => 'Alarmierungsarten',
                'search_items' => 'Alarmierungsart suchen',
                'popular_items' => 'H&auml;ufige Alarmierungsarten',
                'all_items' => 'Alle Alarmierungsarten',
                'edit_item' => 'Alarmierungsart bearbeiten',
                'view_item' => 'Alarmierungsart ansehen',
                'update_item' => 'Alarmierungsart aktualisieren',
                'add_new_item' => 'Neue Alarmierungsart',
                'new_item_name' => 'Alarmierungsart hinzuf&uuml;gen',
                'separate_items_with_commas' => 'Alarmierungsarten mit Kommas trennen',
                'add_or_remove_items' => 'Alarmierungsarten hinzuf&uuml;gen oder entfernen',
                'choose_from_most_used' => 'Aus h&auml;ufigen Alarmierungsarten w&auml;hlen',
                'not_found' => 'Keine Alarmierungsarten gefunden.',
                'no_terms' => 'Keine Alarmierungsarten',
                'items_list_navigation' => 'Navigation der Liste der Alarmierungsarten',
                'items_list' => 'Liste der Alarmierungsarten',
                'back_to_items' => '&larr; Zur&uuml;ck zu den Alarmierungsarten',
            ),
            'public' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => true,
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
    public function getRewriteSlug(): string
    {
        return self::getSlug();
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $customFields)
    {
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
    }
}
