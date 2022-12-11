<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFieldsRepository;

/**
 * Description of the custom taxonomy 'Alerting Methods'
 * @package abrain\Einsatzverwaltung\Types
 */
class AlertingMethod extends CustomTaxonomy
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
            'labels' => array(
                'name' => _x('Alerting Methods', 'taxonomy general name', 'einsatzverwaltung'),
                'singular_name' => _x('Alerting Method', 'taxonomy singular name', 'einsatzverwaltung'),
                'menu_name' => __('Alerting Methods', 'einsatzverwaltung'),
                'search_items' => __('Search Alerting Method', 'einsatzverwaltung'),
                'popular_items' => __('Popular Alerting Methods', 'einsatzverwaltung'),
                'all_items' => __('All Alerting Methods', 'einsatzverwaltung'),
                'edit_item' => __('Edit Alerting Method', 'einsatzverwaltung'),
                'view_item' => __('View Alerting Method', 'einsatzverwaltung'),
                'update_item' => __('Update Alerting Method', 'einsatzverwaltung'),
                'add_new_item' => __('Add New Alerting Method', 'einsatzverwaltung'),
                'new_item_name' => __('New Alerting Method Name', 'einsatzverwaltung'),
                'separate_items_with_commas' => __('Separate alerting methods with commas', 'einsatzverwaltung'),
                'add_or_remove_items' => __('Add or remove alerting methods', 'einsatzverwaltung'),
                'choose_from_most_used' => __('Choose from the most used alerting methods', 'einsatzverwaltung'),
                'not_found' => __('No alerting methods found.', 'einsatzverwaltung'),
                'no_terms' => __('No alerting methods', 'einsatzverwaltung'),
                'items_list_navigation' => __('Alerting Methods list navigation', 'einsatzverwaltung'),
                'items_list' => __('Alerting Methods list', 'einsatzverwaltung'),
                'back_to_items' => __('&larr; Go to Alerting Methods', 'einsatzverwaltung'),
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
