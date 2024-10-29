<?php
namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\CustomFields\Checkbox;
use abrain\Einsatzverwaltung\CustomFields\NumberInput;
use abrain\Einsatzverwaltung\CustomFields\PostSelector;
use abrain\Einsatzverwaltung\CustomFields\StringList;
use abrain\Einsatzverwaltung\CustomFields\UnitSelector;
use abrain\Einsatzverwaltung\CustomFields\UrlInput;
use WP_REST_Response;
use WP_Screen;
use WP_Term;
use abrain\Einsatzverwaltung\CustomFieldsRepository;
use function add_filter;
use function array_key_exists;
use function esc_html;
use function esc_url;
use function get_term_meta;
use function get_terms;
use function get_the_title;
use function is_numeric;
use function strcasecmp;
use function url_to_postid;

/**
 * Description of the custom taxonomy 'Vehicle'
 * @package abrain\Einsatzverwaltung\Types
 */
class Vehicle extends CustomTaxonomy
{
    /**
     * Comparison function for vehicles
     *
     * @param WP_Term $vehicle1
     * @param WP_Term $vehicle2
     *
     * @return int
     */
    public static function compareVehicles(WP_Term $vehicle1, WP_Term $vehicle2): int
    {
        $order1 = get_term_meta($vehicle1->term_id, 'vehicleorder', true);
        $order2 = get_term_meta($vehicle2->term_id, 'vehicleorder', true);

        if (empty($order1) && !empty($order2)) {
            return 1;
        }

        if (!empty($order1) && empty($order2)) {
            return -1;
        }

        // If no order is set on both or if they are equal, sort by name
        if ((empty($order1) && empty($order2)) || ($order1 == $order2)) {
            return strcasecmp($vehicle1->name, $vehicle2->name);
        }

        return ($order1 < $order2) ? -1 : 1;
    }

    /**
     * @return string
     */
    public static function getSlug(): string
    {
        return 'fahrzeug';
    }

    /**
     * @return array
     */
    public function getRegistrationArgs(): array
    {
        return array(
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
                'back_to_items' => '&larr; Zur&uuml;ck zu den Fahrzeugen',
            ),
            'public' => true,
            'show_in_rest' => true,
            'meta_box_cb' => false,
            'hierarchical' => true,
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

    public static function isActivelyUsed(): bool
    {
        $vehicleCount = get_terms(['taxonomy' => self::getSlug(), 'fields' => 'count']);
        return is_numeric($vehicleCount) && $vehicleCount > 0;
    }

    /**
     * @inheritdoc
     */
    public function registerCustomFields(CustomFieldsRepository $customFields)
    {
        $customFields->add($this, new UnitSelector(
            'vehicle_unit',
            _x('Unit', 'taxonomy singular name', 'einsatzverwaltung'),
            'Falls du auf dieser Seite mehrere Einheiten (Abteilungen o.ä.) unterscheidest, kannst du hier das Fahrzeug einer davon zuordnen.'
        ));
        $customFields->add($this, new PostSelector(
            'fahrzeugpid',
            __('Page with further information', 'einsatzverwaltung'),
            'Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.',
            array('page')
        ));
        $customFields->add($this, new UrlInput(
            'vehicle_exturl',
            __('External URL', 'einsatzverwaltung'),
            __('You can specify a URL that points to more information about this vehicle. If set, this takes precedence over the page selected above.', 'einsatzverwaltung')
        ));
        $customFields->add($this, new NumberInput(
            'vehicleorder',
            'Reihenfolge',
            'Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen mit dem Wert 0. Bei gleichem Wert werden Fahrzeuge in alphabetischer Reihenfolge ausgegeben.'
        ));
        $customFields->add($this, new Checkbox(
            'out_of_service',
            __('Out of service', 'einsatzverwaltung'),
            __('This vehicle is no longer in service', 'einsatzverwaltung'),
            'Beim Bearbeiten von Einsatzberichten werden Fahrzeuge, die nicht außer Dienst sind, zuerst aufgelistet.',
            '0'
        ));
        $customFields->add($this, new StringList(
            'altname',
            __('Alternative identifiers', 'einsatzverwaltung'),
            __('A list of identifiers that are synonymous with this vehicle. They will be used to find exisiting vehicles when reports are created via the API. One identifier per line.', 'einsatzverwaltung')
        ));
    }

    /**
     * @inheritdoc
     */
    public function registerHooks()
    {
        $taxonomySlug = self::getSlug();
        add_action("{$taxonomySlug}_pre_add_form", array($this, 'deprectatedHierarchyNotice'));
        add_action('admin_menu', array($this, 'addBadgeToMenu'));

        /**
         * Prevent the Gutenberg Editor from creating a UI for this taxonomy, so we can use our own
         * https://github.com/WordPress/gutenberg/issues/6912#issuecomment-428403380
         */
        add_filter('rest_prepare_taxonomy', function (WP_REST_Response $response, $taxonomy) {
            if (self::getSlug() === $taxonomy->name) {
                $response->data['visibility']['show_ui'] = false;
            }
            return $response;
        }, 10, 2);

        // Manipulate the columns of the term list after the automatically generated ones have been added
        add_action("manage_edit-{$taxonomySlug}_columns", array($this, 'onCustomColumns'), 20);
        add_filter("manage_{$taxonomySlug}_custom_column", array($this, 'onTaxonomyColumnContent'), 20, 3);

        add_filter('default_hidden_columns', function (array $hiddenColumns, WP_Screen $screen) {
            if ($screen->taxonomy === self::getSlug()) {
                $hiddenColumns[] = 'altname';
            }
            return $hiddenColumns;
        }, 10, 2);
    }

    /**
     * Check if there are vehicles with parents, as that is now deprecated
     */
    public function deprectatedHierarchyNotice()
    {
        $termsWithParentCount = $this->getTermsWithParentCount();
        if ($termsWithParentCount > 0) {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html__('The vehicles will soon be reworked and only vehicles without children will remain. Please use the recently introduced Units instead.', 'einsatzverwaltung')
            );
        }
    }

    public function addBadgeToMenu()
    {
        global $submenu;
        $termsWithParentCount = $this->getTermsWithParentCount();
        if ($termsWithParentCount > 0) {
            $submenuKey = 'edit.php?post_type=' . Report::getSlug();
            if (array_key_exists($submenuKey, $submenu)) {
                $vehicleEntry = array_filter($submenu[$submenuKey], function ($entry) {
                    return $entry[2] === 'edit-tags.php?taxonomy=fahrzeug&amp;post_type=einsatz';
                });

                foreach ($vehicleEntry as $id => $entry) {
                    $entry[0] .= sprintf(
                        ' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
                        esc_html($termsWithParentCount)
                    );
                    $submenu[$submenuKey][$id] = $entry;
                }
            }
        }
    }

    /**
     * @return int Returns the number of terms in this taxonomy that have a parent term.
     */
    private function getTermsWithParentCount(): int
    {
        $terms = get_terms(array('taxonomy' => self::getSlug(), 'hide_empty' => false));
        $childTerms = array_filter($terms, function (WP_Term $term) {
            return $term->parent !== 0;
        });
        return count($childTerms);
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
        // Remove the column for the external URL. We'll combine it with the vehicle page column.
        unset($columns['vehicle_exturl']);
        // Rename the vehicle page column
        $columns['fahrzeugpid'] = __('Linking', 'einsatzverwaltung');
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
        // We only want to change the column of the vehicle page
        if ($columnName !== 'fahrzeugpid') {
            return $content;
        }

        $externalUrl = get_term_meta($termId, 'vehicle_exturl', true);
        // If no external URL is set, there's nothing to change
        if (empty($externalUrl)) {
            return $content;
        }

        // The external URL takes precedence over the internal vehicle page, so we will return that

        // Check if it is a local link after all so we can display the post title
        $linkTitle = __('External URL', 'einsatzverwaltung');
        $postId = url_to_postid($externalUrl);
        if ($postId !== 0) {
            $title = get_the_title($postId);
            $linkTitle = empty($title) ? __('Internal URL', 'einsatzverwaltung') : $title;
        }

        return sprintf('<a href="%1$s">%2$s</a>', esc_url($externalUrl), esc_html($linkTitle));
    }
}
