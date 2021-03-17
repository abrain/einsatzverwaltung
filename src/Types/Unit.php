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
use function esc_html;
use function esc_url;
use function get_permalink;
use function get_term;
use function get_term_meta;
use function get_the_title;
use function sprintf;
use function strcasecmp;
use function url_to_postid;

/**
 * Description of the custom taxonomy for units
 * @package abrain\Einsatzverwaltung\Types
 */
class Unit implements CustomTaxonomy
{
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
        if (empty($order1) && empty($order2) || $order1 == $order2) {
            return strcasecmp($unit1->name, $unit2->name);
        }

        return ($order1 < $order2) ? -1 : 1;
    }

    /**
     * Retrieve the URL to more info about a given Unit. This can be a permalink to an internal page or an external URL.
     *
     * @param WP_Term $unit
     *
     * @return string A URL or an empty string
     */
    public static function getInfoUrl(WP_Term $unit): string
    {
        // The external URL takes precedence over an internal page
        $extUrl = get_term_meta($unit->term_id, 'unit_exturl', true);
        if (!empty($extUrl)) {
            return $extUrl;
        }

        // Figure out if an internal page has been assigned
        $pageid = get_term_meta($unit->term_id, 'unit_pid', true);
        if (empty($pageid)) {
            return '';
        }

        // Try to get the permalink of this page
        $pageUrl = get_permalink($pageid);
        if ($pageUrl === false) {
            return '';
        }

        return $pageUrl;
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
            'popular_items' => 'H&auml;ufig eingesetzte Einheiten',
            'all_items' => 'Alle Einheiten',
            'edit_item' => __('Edit Unit', 'einsatzverwaltung'),
            'view_item' => __('View Unit', 'einsatzverwaltung'),
            'update_item' => 'Einheit aktualisieren',
            'add_new_item' => __('Add New Unit', 'einsatzverwaltung'),
            'new_item_name' => 'Einheit hinzuf&uuml;gen',
            'separate_items_with_commas' => 'Separate tags with commas',
            'add_or_remove_items' => 'Add or remove tags',
            'choose_from_most_used' => 'Choose from the most used tags',
            'not_found' => __('No units found.', 'einsatzverwaltung'),
            'no_terms' => 'Keine Einheiten',
            'items_list_navigation' => __('Units list navigation', 'einsatzverwaltung'),
            'items_list' => __('Units list', 'einsatzverwaltung'),
            'most_used' => 'Most Used',
            'back_to_items' => '&larr; Zur&uuml;ck zu den Einheiten',
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
            'Optionale Angabe, mit der die Anzeigereihenfolge der Einheiten beeinflusst werden kann. Einheiten mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0. Haben mehrere Einheiten den gleichen Wert, werden sie in alphabetischer Reihenfolge ausgegeben.'
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
        if ($columnName !== 'unit_pid') {
            return $content;
        }

        $unit = get_term($termId);
        $url = Unit::getInfoUrl($unit);
        // If no info URL is set, there's nothing to do
        if (empty($url)) {
            return $content;
        }

        // Check if it is a local link after all so we can display the post title
        $linkTitle = __('External URL', 'einsatzverwaltung');
        $postId = url_to_postid($url);
        if ($postId !== 0) {
            $title = get_the_title($postId);
            $linkTitle = empty($title) ? __('Internal URL', 'einsatzverwaltung') : $title;
        }

        return sprintf('<a href="%1$s">%2$s</a>', esc_url($url), esc_html($linkTitle));
    }
}
