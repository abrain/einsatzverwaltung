<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\Model\ReportAnnotation;
use abrain\Einsatzverwaltung\ReportAnnotationRepository;
use abrain\Einsatzverwaltung\TaxonomyCustomFields;

/**
 * Description of the custom post type for the reports
 * @package abrain\Einsatzverwaltung\Types
 */
class Report implements CustomType
{
    const DEFAULT_REWRITE_SLUG = 'einsatzberichte';
    const SLUG = 'einsatz';

    /**
     * @return string
     */
    public static function getRewriteSlug()
    {
        return sanitize_title(
            get_option('einsatzvw_rewrite_slug', self::DEFAULT_REWRITE_SLUG),
            self::DEFAULT_REWRITE_SLUG
        );
    }

    /**
     * @return array
     */
    private function getLabels()
    {
        return array(
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
        );
    }

    /**
     * @return array
     */
    private function getCapabilities()
    {
        return array(
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
        );
    }

    /**
     * @inheritdoc
     */
    public function getRegistrationArgs()
    {
        return array(
            'labels' => $this->getLabels(),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array(
                'feeds' => true,
                'slug' => self::getRewriteSlug()
            ),
            'supports' => array('title', 'editor', 'thumbnail', 'publicize', 'author', 'revisions'),
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'capability_type' => array('einsatzbericht', 'einsatzberichte'),
            'map_meta_cap' => true,
            'capabilities' => $this->getCapabilities(),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-media-document',
            'taxonomies' => array('post_tag', 'category'),
            'delete_with_user' => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function getSlug()
    {
        return self::SLUG;
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(TaxonomyCustomFields $taxonomyCustomFields)
    {
        $this->registerPostMeta();
        $this->registerAnnotations();
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

    private function registerPostMeta()
    {
        register_meta('post', 'einsatz_einsatzende', array(
            'type' => 'string',
            'description' => 'Datum und Uhrzeit, zu der der Einsatz endete.',
            'single' => true,
            'sanitize_callback' => array($this, 'sanitizeTimeOfEnding'),
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

    /**
     * @param string $value
     * @return string
     */
    public function sanitizeTimeOfEnding($value)
    {
        $sanitizedValue = sanitize_text_field($value);
        if (!empty($sanitizedValue)) {
            $dateTime = date_create($sanitizedValue);
        }

        if (empty($dateTime)) {
            return "";
        }

        $formattedDateTime = date_format($dateTime, 'Y-m-d H:i');

        return ($formattedDateTime === false ? '' : $formattedDateTime);
    }
}
