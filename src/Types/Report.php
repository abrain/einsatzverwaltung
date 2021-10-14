<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFieldsRepository;
use abrain\Einsatzverwaltung\DataAccess\ReportActions;
use abrain\Einsatzverwaltung\Model\ReportAnnotation;
use abrain\Einsatzverwaltung\ReportAnnotationRepository;
use abrain\Einsatzverwaltung\Utilities;
use function register_meta;

/**
 * Description of the custom post type for the reports
 * @package abrain\Einsatzverwaltung\Types
 */
class Report implements CustomPostType
{
    const DEFAULT_REWRITE_SLUG = 'einsatzberichte';

    /**
     * @return array
     */
    private function getLabels(): array
    {
        return array(
            'name' => _x('Incident Reports', 'post type general name', 'einsatzverwaltung'),
            'singular_name' => _x('Incident Report', 'post type singular name', 'einsatzverwaltung'),
            'add_new' => _x('Add New', 'incident report', 'einsatzverwaltung'),
            'add_new_item' => __('Add New Incident Report', 'einsatzverwaltung'),
            'edit_item' => __('Edit Incident Report', 'einsatzverwaltung'),
            'new_item' => __('New Incident Report', 'einsatzverwaltung'),
            'view_item' => __('View Incident Report', 'einsatzverwaltung'),
            'view_items' => __('View Incident Reports', 'einsatzverwaltung'),
            'search_items' => __('Search Incident Reports', 'einsatzverwaltung'),
            'not_found' => __('No Incident Reports found.', 'einsatzverwaltung'),
            'not_found_in_trash' => __('No Incident Reports found in Trash.', 'einsatzverwaltung'),
            'all_items' => __('All Incident Reports', 'einsatzverwaltung'),
            'archives' => __('Incident Report Archives', 'einsatzverwaltung'),
            'attributes' => __('Incident Report Attributes', 'einsatzverwaltung'),
            'insert_into_item' => __('Insert into Incident Report', 'einsatzverwaltung'),
            'uploaded_to_this_item' => __('Uploaded to this Incident Report', 'einsatzverwaltung'),
            'featured_image' => _x('Featured image', 'Incident Report', 'einsatzverwaltung'),
            'set_featured_image' => _x('Set featured image', 'incident report', 'einsatzverwaltung'),
            'remove_featured_image' => _x('Remove featured image', 'incident report', 'einsatzverwaltung'),
            'use_featured_image' => _x('Use as featured image', 'incident report', 'einsatzverwaltung'),
            'menu_name' => _x('Incident Reports', 'menu name', 'einsatzverwaltung'),
            'filter_items_list' => __('Filter Incident Reports list', 'einsatzverwaltung'),
            'items_list_navigation' => __('Incident Reports list navigation', 'einsatzverwaltung'),
            'items_list' => __('Incident Reports list', 'einsatzverwaltung'),
            'item_published' => __('Incident Report published.', 'einsatzverwaltung'),
            'item_published_privately' => __('Incident Report published privately.', 'einsatzverwaltung'),
            'item_reverted_to_draft' => __('Incident Report reverted to draft.', 'einsatzverwaltung'),
            'item_scheduled' => __('Incident Report scheduled.', 'einsatzverwaltung'),
            'item_updated' => __('Incident Report updated.', 'einsatzverwaltung'),
        );
    }

    /**
     * @return array
     */
    private function getCapabilities(): array
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
    public function getRegistrationArgs(): array
    {
        return array(
            'labels' => $this->getLabels(),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array(
                'feeds' => true,
                'slug' => $this->getRewriteSlug()
            ),
            'supports' => $this->getSupportedFeatures(),
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'capability_type' => array('einsatzbericht', 'einsatzberichte'),
            'map_meta_cap' => true,
            'capabilities' => $this->getCapabilities(),
            'menu_position' => 5,
            'menu_icon' => 'dashicons-media-document',
            'taxonomies' => $this->getTaxonomies(),
            'delete_with_user' => false,
        );
    }

    /**
     * @inheritDoc
     */
    public function getRewriteSlug(): string
    {
        $rewriteSlug = get_option('einsatzvw_rewrite_slug', self::DEFAULT_REWRITE_SLUG);
        return sanitize_title($rewriteSlug, self::DEFAULT_REWRITE_SLUG);
    }

    /**
     * @inheritdoc
     */
    public static function getSlug(): string
    {
        return 'einsatz';
    }

    /**
     * @return array The core features that this post type supports
     */
    private function getSupportedFeatures(): array
    {
        $features = array('title', 'editor', 'thumbnail', 'publicize', 'author', 'revisions', 'custom-fields');

        if (get_option('einsatz_support_excerpt', '0') === '1') {
            $features[] = 'excerpt';
        }

        return $features;
    }

    /**
     * @return array The taxonomies that are linked to this post type
     */
    private function getTaxonomies(): array
    {
        $taxonomies = array('category');

        if (get_option('einsatz_support_posttag', '0') === '1') {
            $taxonomies[] = 'post_tag';
        }

        return $taxonomies;
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $customFields)
    {
        $this->registerPostMeta();
        $this->registerAnnotations();
    }

    private function registerAnnotations()
    {
        $annotationRepository = ReportAnnotationRepository::getInstance();
        $annotationRepository->addAnnotation(new ReportAnnotation(
            'images',
            __('Report contains pictures', 'einsatzverwaltung'),
            'einsatz_hasimages',
            'camera',
            __('Report contains pictures', 'einsatzverwaltung'),
            __('Report does not contain pictures', 'einsatzverwaltung')
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
            __('False alarm', 'einsatzverwaltung'),
            'einsatz_fehlalarm',
            '',
            __('False alarm', 'einsatzverwaltung'),
            'Kein Fehlalarm'
        ));
    }

    private function registerPostMeta()
    {
        register_meta('post', 'einsatz_einsatzende', array(
            'object_subtype' => self::getSlug(),
            'type' => 'string',
            'description' => 'Datum und Uhrzeit, zu der der Einsatz endete.',
            'single' => true,
            'sanitize_callback' => array($this, 'sanitizeTimeOfEnding'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_einsatzleiter', array(
            'object_subtype' => self::getSlug(),
            'type' => 'string',
            'description' => 'Name der Person, die die Einsatzleitung innehatte.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_einsatzort', array(
            'object_subtype' => self::getSlug(),
            'type' => 'string',
            'description' => 'Die Örtlichkeit, an der der Einsatz stattgefunden hat.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_fehlalarm', array(
            'object_subtype' => self::getSlug(),
            'type' => 'boolean',
            'description' => 'Vermerk, ob es sich um einen Fehlalarm handelte.',
            'single' => true,
            'sanitize_callback' => array(Utilities::class, 'sanitizeCheckbox'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_hasimages', array(
            'object_subtype' => self::getSlug(),
            'type' => 'boolean',
            'description' => 'Vermerk, ob der Einsatzbericht Bilder enthält.',
            'single' => true,
            'sanitize_callback' => array(Utilities::class, 'sanitizeCheckbox'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_incidentNumber', array(
            'object_subtype' => self::getSlug(),
            'type' => 'string',
            'description' => 'Einsatznummer.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_mannschaft', array(
            'object_subtype' => self::getSlug(),
            'type' => 'string',
            'description' => 'Angaben über die Personalstärke für diesen Einsatz.',
            'single' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_special', array(
            'object_subtype' => self::getSlug(),
            'type' => 'boolean',
            'description' => 'Vermerk, ob es sich um einen besonderen Einsatzbericht handelt.',
            'single' => true,
            'sanitize_callback' => array(Utilities::class, 'sanitizeCheckbox'),
            'show_in_rest' => false
        ));

        register_meta('post', 'einsatz_weight', array(
            'object_subtype' => self::getSlug(),
            'type' => 'integer',
            'description' => 'Anzahl von Einsätzen, die durch diesen Bericht repräsentiert werden',
            'single' => true,
            'default' => '1',
            'sanitize_callback' => 'absint',
            'show_in_rest' => true
        ));
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function sanitizeTimeOfEnding(string $value): string
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

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        (new ReportActions())->addHooks();
    }
}
