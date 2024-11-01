<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFields\UrlInput;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use WP_REST_Response;
use WP_Term;
use function add_action;
use function add_filter;
use function get_term;
use function get_term_meta;
use function get_terms;
use function is_numeric;
use function strcasecmp;

/**
 * Description of the custom taxonomy for units
 * @package abrain\Einsatzverwaltung\Types
 */
class Unit extends CustomTaxonomy
{
    public static function getInfoUrl(WP_Term $term): string
    {
        return parent::getInfoUrlForTerm($term, 'unit_exturl', 'unit_pid');
    }

    /**
     * Comparison function for unis
     *
     * @param WP_Term $unit1
     * @param WP_Term $unit2
     *
     * @return int
     */
    public static function compare(WP_Term $unit1, WP_Term $unit2): int
    {
        $order1 = get_term_meta($unit1->term_id, 'unit_order', true);
        $order2 = get_term_meta($unit2->term_id, 'unit_order', true);

        if (empty($order1) && !empty($order2)) {
            return 1;
        }

        if (!empty($order1) && empty($order2)) {
            return -1;
        }

        // If no order is set on both or if they are equal, sort by name
        if ((empty($order1) && empty($order2)) || ($order1 == $order2)) {
            return strcasecmp($unit1->name, $unit2->name);
        }

        return ($order1 < $order2) ? -1 : 1;
    }

    /**
     * @return array
     */
    private function getLabels(): array
    {
        return array(
            'name' => _x('Units', 'taxonomy general name', 'einsatzverwaltung'),
            'singular_name' => _x('Unit', 'taxonomy singular name', 'einsatzverwaltung'),
            'search_items' => __('Search Units', 'einsatzverwaltung'),
            'popular_items' => __('Popular Units', 'einsatzverwaltung'),
            'all_items' => __('All Units', 'einsatzverwaltung'),
            'edit_item' => __('Edit Unit', 'einsatzverwaltung'),
            'view_item' => __('View Unit', 'einsatzverwaltung'),
            'update_item' => __('Update Unit', 'einsatzverwaltung'),
            'add_new_item' => __('Add New Unit', 'einsatzverwaltung'),
            'new_item_name' => __('New Unit Name', 'einsatzverwaltung'),
            'separate_items_with_commas' => __('Separate units with commas', 'einsatzverwaltung'),
            'add_or_remove_items' => __('Add or remove units', 'einsatzverwaltung'),
            'choose_from_most_used' => __('Choose from the most used units', 'einsatzverwaltung'),
            'not_found' => __('No units found.', 'einsatzverwaltung'),
            'no_terms' => __('No units', 'einsatzverwaltung'),
            'items_list_navigation' => __('Units list navigation', 'einsatzverwaltung'),
            'items_list' => __('Units list', 'einsatzverwaltung'),
            'most_used' => _x('Most Used', 'units', 'einsatzverwaltung'),
            'back_to_items' =>  __('&larr; Go to Units', 'einsatzverwaltung'),
        );
    }

    /**
     * @inheritDoc
     */
    public function getRegistrationArgs(): array
    {
        return array(
            'labels' => $this->getLabels(),
            'description' => '',
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'meta_box_cb' => false,
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
     * @inheritDoc
     */
    public static function getSlug(): string
    {
        return 'evw_unit';
    }

    public static function hasTerms(): bool
    {
        $unitCount = get_terms(['taxonomy' => self::getSlug(), 'fields' => 'count', 'hide_empty' => false]);
        return is_numeric($unitCount) && $unitCount > 0;
    }

    public static function isActivelyUsed(): bool
    {
        $unitCount = get_terms(['taxonomy' => self::getSlug(), 'fields' => 'count']);
        return is_numeric($unitCount) && $unitCount > 0;
    }

    /**
     * @inheritDoc
     */
    public function registerCustomFields(CustomFieldsRepository $customFields)
    {
        $customFields->add($this, new PostSelector(
            'unit_pid',
            __('Page with further information', 'einsatzverwaltung'),
            'Seite mit mehr Informationen &uuml;ber die Einheit. Wird in Einsatzberichten mit dieser Einheit verlinkt.',
            array('page')
        ));
        $customFields->add($this, new UrlInput(
            'unit_exturl',
            __('External URL', 'einsatzverwaltung'),
            __('You can specify a URL that points to more information about this unit. If set, this takes precedence over the page selected above.', 'einsatzverwaltung')
        ));
        $customFields->add($this, new NumberInput(
            'unit_order',
            'Reihenfolge',
            'Einheiten mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen mit dem Wert 0. Bei gleichem Wert werden Einheiten in alphabetischer Reihenfolge ausgegeben.'
        ));
    }

    /**
     * @inheritDoc
     */
    public function registerHooks()
    {
        $taxonomySlug = self::getSlug();

        // Manipulate the columns of the term list after the automatically generated ones have been added
        add_action("manage_edit-{$taxonomySlug}_columns", array($this, 'onCustomColumns'), 20);
        add_filter("manage_{$taxonomySlug}_custom_column", array($this, 'onTaxonomyColumnContent'), 20, 3);

        /**
         * Prevent the Gutenberg Editor from creating a UI for this taxonomy, so we can use our own
         * https://github.com/WordPress/gutenberg/issues/6912#issuecomment-428403380
         */
        add_filter('rest_prepare_taxonomy', function (WP_REST_Response $response, $taxonomy) use ($taxonomySlug) {
            if ($taxonomySlug === $taxonomy->name) {
                $response->data['visibility']['show_ui'] = false;
            }
            return $response;
        }, 10, 2);
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
        // Remove the column for the external URL, we'll combine it with the unit page column.
        unset($columns['unit_exturl']);

        // Rename the unit page column
        $columns['unit_pid'] = __('Linking', 'einsatzverwaltung');

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
        if ($columnName === 'unit_pid') {
            $url = self::getInfoUrl(get_term($termId));
            return empty($url) ? '' : self::getUrlColumnContent($url);
        }

        return $content;
    }
}
