<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFields\UrlInput;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use WP_Term;
use function __;
use function add_action;
use function add_filter;
use function get_term;

/**
 * Description of the custom taxonomy 'Alerting Methods'
 * @package abrain\Einsatzverwaltung\Types
 */
class AlertingMethod extends CustomTaxonomy
{
    public static function getInfoUrl(WP_Term $term): string
    {
        return parent::getInfoUrlForTerm($term, 'alertingmethod_exturl', 'alertingmethod_pid');
    }

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
        $customFields->add($this, new PostSelector(
            'alertingmethod_pid',
            __('Page with further information', 'einsatzverwaltung'),
            'Seite mit mehr Informationen &uuml;ber die Alarmierungsart. Wird in Einsatzberichten mit dieser Alarmierungsart verlinkt.',
            array('page')
        ));
        $customFields->add($this, new UrlInput(
            'alertingmethod_exturl',
            __('External URL', 'einsatzverwaltung'),
            __('You can specify a URL that points to more information about this alerting method. If set, this takes precedence over the page selected above.', 'einsatzverwaltung')
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        $taxonomySlug = self::getSlug();

        // Manipulate the columns of the term list after the automatically generated ones have been added
        add_action("manage_edit-{$taxonomySlug}_columns", array($this, 'onCustomColumns'), 20);
        add_filter("manage_{$taxonomySlug}_custom_column", array($this, 'onTaxonomyColumnContent'), 20, 3);
    }

    /**
     * Filters the columns shown in the WP_List_Table for this taxonomy.
     *
     * @param array $columns
     *
     * @return array
     */
    public function onCustomColumns(array $columns): array
    {
        // Remove the column for the external URL, we'll combine it with the page ID column.
        unset($columns['alertingmethod_exturl']);

        // Rename the page ID column
        $columns['alertingmethod_pid'] = __('Linking', 'einsatzverwaltung');

        return $columns;
    }

    /**
     * Filters the content of the columns of the WP_List_Table for this taxonomy.
     *
     * @param string $content Content of the column that has been defined by the previous filters
     * @param string $columnName Name of the column
     * @param int $termId Term ID
     *
     * @return string
     */
    public function onTaxonomyColumnContent(string $content, string $columnName, int $termId): string
    {
        // We only want to change a specific column
        if ($columnName === 'alertingmethod_pid') {
            $url = self::getInfoUrl(get_term($termId));
            return empty($url) ? '' : self::getUrlColumnContent($url);
        }

        return $content;
    }
}
