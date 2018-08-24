<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\CustomFields\ColorPicker;
use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFields\TextInput;
use abrain\Einsatzverwaltung\Exceptions\TypeRegistrationException;
use abrain\Einsatzverwaltung\Model\ReportAnnotation;
use abrain\Einsatzverwaltung\Types\CustomType;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;

/**
 * Central place to register custom post types and taxonomies with WordPress
 *
 * @package abrain\Einsatzverwaltung
 */
class TypeRegistry
{
    private $postTypes = array();
    private $taxonomies = array();

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
    private $data;

    /**
     * TypeRegistry constructor.
     * @param $data
     */
    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    /**
     * Erzeugt den neuen Beitragstyp Einsatzbericht und die zugehörigen Taxonomien
     * @throws TypeRegistrationException
     */
    public function registerTypes()
    {
        $report = new Report();
        $this->registerPostType($report);
        $this->registerTaxonomy(new IncidentType(), $report->getSlug());

        register_taxonomy('fahrzeug', 'einsatz', $this->argsFahrzeug);
        register_taxonomy('exteinsatzmittel', 'einsatz', $this->argsExteinsatzmittel);
        register_taxonomy('alarmierungsart', 'einsatz', $this->argsAlarmierungsart);

        $this->registerPostMeta();
        $this->registerAnnotations();
        $this->registerTaxonomyCustomFields();
    }

    /**
     * Registers a custom post type
     *
     * @param CustomType $customType Object that describes the custom post type
     * @throws TypeRegistrationException
     */
    private function registerPostType(CustomType $customType)
    {
        $slug = $customType->getSlug();
        if (array_key_exists($slug, $this->postTypes)) {
            throw new TypeRegistrationException(
                sprintf(__('Post type with slug "%s" already exists', 'einsatzverwaltung'), $slug)
            );
        }

        $postType = register_post_type($slug, $customType->getRegistrationArgs());
        if (is_wp_error($postType)) {
            throw new TypeRegistrationException(sprintf(
                __('Failed to register post type with slug "%s": %s', 'einsatzverwaltung'),
                $slug,
                $postType->get_error_message()
            ));
        }

        $this->postTypes[$slug] = $postType;
    }

    /**
     * Registers a custom taxonomy for a certain post type
     *
     * @param CustomType $customTaxonomy Object that describes the custom taxonomy
     * @param string $postType
     * @throws TypeRegistrationException
     */
    private function registerTaxonomy(CustomType $customTaxonomy, $postType)
    {
        $slug = $customTaxonomy->getSlug();
        if (get_taxonomy($slug) !== false) {
            throw new TypeRegistrationException(
                sprintf(__('Taxonomy with slug "%s" already exists', 'einsatzverwaltung'), $slug)
            );
        }

        $postType = register_taxonomy($slug, $postType, $customTaxonomy->getRegistrationArgs());
        if (is_wp_error($postType)) {
            throw new TypeRegistrationException(sprintf(
                __('Failed to register post type with slug "%s": %s', 'einsatzverwaltung'),
                $slug,
                $postType->get_error_message()
            ));
        }
        array_push($this->taxonomies, $slug);
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
