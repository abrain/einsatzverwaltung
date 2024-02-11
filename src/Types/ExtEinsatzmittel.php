<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\StringList;
use abrain\Einsatzverwaltung\CustomFields\TextInput;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use WP_Screen;
use function add_filter;

/**
 * Description of the custom taxonomy 'Externes Einsatzmittel'
 * @package abrain\Einsatzverwaltung\Types
 */
class ExtEinsatzmittel extends CustomTaxonomy
{
    /**
     * @return string
     */
    public static function getSlug(): string
    {
        return 'exteinsatzmittel';
    }

    /**
     * @return array
     */
    public function getRegistrationArgs(): array
    {
        return array(
            'label' => 'Externe Einsatzmittel',
            'labels' => array(
                'name' => 'Externe Einsatzmittel',
                'singular_name' => 'Externes Einsatzmittel',
                'menu_name' => 'Externe Einsatzmittel',
                'search_items' => 'Externe Einsatzmittel suchen',
                'popular_items' => 'H&auml;ufig eingesetzte externe Einsatzmittel',
                'all_items' => 'Alle externen Einsatzmittel',
                'edit_item' => 'Externes Einsatzmittel bearbeiten',
                'view_item' => 'Externes Einsatzmittel ansehen',
                'update_item' => 'Externes Einsatzmittel aktualisieren',
                'add_new_item' => 'Neues externes Einsatzmittel',
                'new_item_name' => 'Externes Einsatzmittel hinzuf&uuml;gen',
                'separate_items_with_commas' => 'Externe Einsatzmittel mit Kommas trennen',
                'add_or_remove_items' => 'Externe Einsatzmittel hinzuf&uuml;gen oder entfernen',
                'choose_from_most_used' => 'Aus h&auml;ufig eingesetzten externen Einsatzmitteln w&auml;hlen',
                'not_found' => 'Keine externen Einsatzmittel gefunden.',
                'no_terms' => 'Keine externen Einsatzmittel',
                'items_list_navigation' => 'Navigation der Liste der externen Einsatzmittel',
                'items_list' => 'Liste der externen Einsatzmittel',
                'back_to_items' => '&larr; Zur&uuml;ck zu den externen Einsatzmitteln',
            ),
            'public' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => true,
            'capabilities' => array(
                'manage_terms' => 'edit_einsatzberichte',
                'edit_terms' => 'edit_einsatzberichte',
                'delete_terms' => 'edit_einsatzberichte',
                'assign_terms' => 'edit_einsatzberichte'
            ),
            'rewrite' => array(
                'slug' => 'externe-einsatzmittel'
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
        $customFields->add($this, new TextInput(
            'url',
            'URL',
            'URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.'
        ));
        $customFields->add($this, new StringList(
            'altname',
            __('Alternative identifiers', 'einsatzverwaltung'),
            __('A list of identifiers that are synonymous with this resource. They will be used to find exisiting resources when reports are created via the API. One identifier per line.', 'einsatzverwaltung')
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        add_filter('default_hidden_columns', function (array $hiddenColumns, WP_Screen $screen) {
            if ($screen->taxonomy === self::getSlug()) {
                $hiddenColumns[] = 'altname';
            }
            return $hiddenColumns;
        }, 10, 2);
    }
}
