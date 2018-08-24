<?php
namespace abrain\Einsatzverwaltung\Types;

/**
 * Description of the custom post type for the reports
 * @package abrain\Einsatzverwaltung\Types
 */
class Report implements CustomType
{
    const DEFAULT_REWRITE_SLUG = 'einsatzberichte';

    /**
     * @return string
     */
    private function getRewriteSlug()
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
                'slug' => $this->getRewriteSlug()
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
        return 'einsatz';
    }
}
