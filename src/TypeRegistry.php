<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\CustomFields\ColorPicker;
use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFields\TextInput;
use abrain\Einsatzverwaltung\Model\ReportAnnotation;

/**
 * Central place to register custom post types and taxonomies with WordPress
 *
 * @package abrain\Einsatzverwaltung
 */
class TypeRegistry
{
    private $argsEinsatz = array(
        'labels' => array(
            'name' => 'Einsatzberichte',
            'singular_name' => 'Einsatzbericht',
            'menu_name' => 'Einsatzberichte',
            'add_new' => 'Neu',
            'add_new_item' => 'Neuer Einsatzbericht',
            'edit' => 'Bearbeiten',
            'edit_item' => 'Einsatzbericht bearbeiten',
            'new_item' => 'Neuer Einsatzbericht',
            'view' => 'Ansehen',
            'view_item' => 'Einsatzbericht ansehen',
            'search_items' => 'Einsatzberichte suchen',
            'not_found' => 'Keine Einsatzberichte gefunden',
            'not_found_in_trash' => 'Keine Einsatzberichte im Papierkorb gefunden',
            'filter_items_list' => 'Liste der Einsatzberichte filtern',
            'items_list_navigation' => 'Navigation der Liste der Einsatzberichte',
            'items_list' => 'Liste der Einsatzberichte',
            'insert_into_item' => 'In den Einsatzbericht einf&uuml;gen',
            'uploaded_to_this_item' => 'Zu diesem Einsatzbericht hochgeladen',
            'view_items' => 'Einsatzberichte ansehen',
            //'attributes' => 'Attribute', // In WP 4.7 eingeführtes Label, für Einsatzberichte derzeit nicht relevant
        ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => array(
            'feeds' => true
        ),
        'supports' => array('title', 'editor', 'thumbnail', 'publicize', 'author', 'revisions'),
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => true,
        'capability_type' => array('einsatzbericht', 'einsatzberichte'),
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'edit_einsatzbericht',
            'read_post' => 'read_einsatzbericht',
            'delete_post' => 'delete_einsatzbericht',
            'edit_posts' => 'edit_einsatzberichte',
            'edit_others_posts' => 'edit_others_einsatzberichte',
            'publish_posts' => 'publish_einsatzberichte',
            'read_private_posts' => 'read_private_einsatzberichte',
            'read' => 'read',
            'delete_posts' => 'delete_einsatzberichte',
            'delete_private_posts' => 'delete_private_einsatzberichte',
            'delete_published_posts' => 'delete_published_einsatzberichte',
            'delete_others_posts' => 'delete_others_einsatzberichte',
            'edit_private_posts' => 'edit_private_einsatzberichte',
            'edit_published_posts' => 'edit_published_einsatzberichte'
        ),
        'menu_position' => 5,
        'menu_icon' => 'dashicons-media-document',
        'taxonomies' => array('post_tag', 'category'),
        'delete_with_user' => false,
    );

    private $argsEinsatzart = array(
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
        ),
        'public' => true,
        'show_in_nav_menus' => false,
        'meta_box_cb' => 'abrain\Einsatzverwaltung\Admin::displayMetaBoxEinsatzart',
        'capabilities' => array(
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        ),
        'hierarchical' => true
    );

    private $argsFahrzeug = array(
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
        ),
        'public' => true,
        'show_in_nav_menus' => false,
        'hierarchical' => true,
        'capabilities' => array(
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );

    private $argsExteinsatzmittel = array(
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
        ),
        'public' => true,
        'show_in_nav_menus' => false,
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

    private $argsAlarmierungsart = array(
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
        ),
        'public' => true,
        'show_in_nav_menus' => false,
        'capabilities' => array(
            'manage_terms' => 'edit_einsatzberichte',
            'edit_terms' => 'edit_einsatzberichte',
            'delete_terms' => 'edit_einsatzberichte',
            'assign_terms' => 'edit_einsatzberichte'
        )
    );
    private $options;
    private $data;

    /**
     * TypeRegistry constructor.
     * @param $options
     * @param $data
     */
    public function __construct(Options $options, Data $data)
    {
        $this->options = $options;
        $this->data = $data;
    }

    /**
     * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
     */
    public function registerTypes()
    {
        // Anpassungen der Parameter
        $this->argsEinsatz['rewrite']['slug'] = $this->options->getRewriteSlug();

        register_post_type('einsatz', $this->argsEinsatz);
        register_taxonomy('einsatzart', 'einsatz', $this->argsEinsatzart);
        register_taxonomy('fahrzeug', 'einsatz', $this->argsFahrzeug);
        register_taxonomy('exteinsatzmittel', 'einsatz', $this->argsExteinsatzmittel);
        register_taxonomy('alarmierungsart', 'einsatz', $this->argsAlarmierungsart);

        $this->registerPostMeta();
        $this->registerAnnotations();
        $this->registerTaxonomyCustomFields();
    }

    private function registerPostMeta()
    {
        register_meta('post', 'einsatz_einsatzende', array(
            'type' => 'string',
            'description' => 'Datum und Uhrzeit, zu der der Einsatz endete.',
            'single' => true,
            'sanitize_callback' => array($this->data, 'sanitizeTimeOfEnding'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_einsatzleiter', array(
            'type' => 'string',
            'description' => 'Name der Person, die die Einsatzleitung innehatte.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_einsatzort', array(
            'type' => 'string',
            'description' => 'Die Örtlichkeit, an der der Einsatz stattgefunden hat.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_fehlalarm', array(
            'type' => 'boolean',
            'description' => 'Vermerk, ob es sich um einen Fehlalarm handelte.',
            'single' => true,
            'sanitize_callback' => array('Utilities', 'sanitizeCheckbox'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_hasimages', array(
            'type' => 'boolean',
            'description' => 'Vermerk, ob der Einsatzbericht Bilder enthält.',
            'single' => true,
            'sanitize_callback' => array('Utilities', 'sanitizeCheckbox'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_incidentNumber', array(
            'type' => 'string',
            'description' => 'Einsatznummer.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_mannschaft', array(
            'type' => 'string',
            'description' => 'Angaben über die Personalstärke für diesen Einsatz.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_special', array(
            'type' => 'boolean',
            'description' => 'Vermerk, ob es sich um einen besonderen Einsatzbericht handelt.',
            'single' => true,
            'sanitize_callback' => array('Utilities', 'sanitizeCheckbox'),
            'show_in_rest' => false
        ));
    }

    private function registerAnnotations()
    {
        $annotationRepository = ReportAnnotationRepository::getInstance();
        $annotationRepository->addAnnotation(new ReportAnnotation(
            'images',
            'Bilder im Bericht',
            'einsatz_hasimages',
            'camera',
            'Einsatzbericht enthält Bilder',
            'Einsatzbericht enthält keine Bilder'
        ));
        $annotationRepository->addAnnotation(new ReportAnnotation(
            'special',
            'Besonderer Einsatz',
            'einsatz_special',
            'star',
            'Besonderer Einsatz',
            'Kein besonderer Einsatz'
        ));
        $annotationRepository->addAnnotation(new ReportAnnotation(
            'falseAlarm',
            'Fehlalarm',
            'einsatz_fehlalarm',
            '',
            'Fehlalarm',
            'Kein Fehlalarm'
        ));
    }

    private function registerTaxonomyCustomFields()
    {
        $taxonomyCustomFields = new TaxonomyCustomFields();
        $taxonomyCustomFields->addTextInput('exteinsatzmittel', new TextInput(
            'url',
            'URL',
            'URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.'
        ));
        $taxonomyCustomFields->addColorpicker('einsatzart', new ColorPicker(
            'typecolor',
            'Farbe',
            'Ordne dieser Einsatzart eine Farbe zu. Einsatzarten ohne Farbe erben diese gegebenenfalls von übergeordneten Einsatzarten.'
        ));
        $taxonomyCustomFields->addPostSelector('fahrzeug', new PostSelector(
            'fahrzeugpid',
            'Fahrzeugseite',
            'Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.',
            array('einsatz', 'attachment', 'ai1ec_event', 'tribe_events')
        ));
        $taxonomyCustomFields->addNumberInput('fahrzeug', new NumberInput(
            'vehicleorder',
            'Reihenfolge',
            'Optionale Angabe, mit der die Anzeigereihenfolge der Fahrzeuge beeinflusst werden kann. Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0 in alphabetischer Reihenfolge.'
        ));
    }
}