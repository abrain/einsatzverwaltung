<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Types\Vehicle;
use WP_Post;
use WP_Taxonomy;
use WP_Term;
use wpdb;
use function add_meta_box;
use function array_filter;
use function array_intersect;
use function array_map;
use function checked;
use function esc_attr;
use function esc_html;
use function get_post_type_object;
use function get_posts;
use function get_taxonomy;
use function get_term_meta;
use function get_terms;
use function get_the_terms;
use function in_array;
use function printf;
use function str_replace;

/**
 * Customizations for the edit screen for the IncidentReport custom post type.
 *
 * @package abrain\Einsatzverwaltung\Admin
 */
class ReportEditScreen extends EditScreen
{
    /**
     * ReportEditScreen constructor.
     */
    public function __construct()
    {
        $this->customTypeSlug = Report::getSlug();
    }

    /**
     * Fügt die Metabox zum Bearbeiten der Einsatzdetails ein
     */
    public function addMetaBoxes()
    {
        add_meta_box(
            'einsatzverwaltung_meta_box',
            'Einsatzdetails',
            array($this, 'displayMetaBoxEinsatzdetails'),
            'einsatz',
            'normal',
            'high',
            array(
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => false
            )
        );
        add_meta_box(
            'einsatzverwaltung_meta_annotations',
            'Vermerke',
            array($this, 'displayMetaBoxAnnotations'),
            'einsatz',
            'side',
            'default',
            array(
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => false
            )
        );
        add_meta_box(
            'einsatzartdiv',
            'Einsatzart',
            array('abrain\Einsatzverwaltung\Admin\ReportEditScreen', 'displayMetaBoxEinsatzart'),
            'einsatz',
            'side',
            'default',
            array(
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => false
            )
        );
        add_meta_box(
            'einsatzverwaltung_units',
            __('Units', 'einsatzverwaltung'),
            array($this, 'displayMetaBoxUnits'),
            'einsatz',
            'side',
            'default',
            array(
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => false
            )
        );
        add_meta_box(
            'fahrzeugdiv',
            __('Vehicles', 'einsatzverwaltung'),
            array($this, 'displayMetaBoxVehicles'),
            $this->customTypeSlug,
            'side',
            'default',
            array(
                '__block_editor_compatible_meta_box' => true,
                '__back_compat_meta_box' => false
            )
        );
    }

    /**
     * Inhalt der Metabox für Vermerke zum Einsatzbericht
     *
     * @param WP_Post $post Das Post-Objekt des aktuell bearbeiteten Einsatzberichts
     */
    public function displayMetaBoxAnnotations($post)
    {
        $report = new IncidentReport($post);

        $this->echoInputCheckbox(
            'Fehlalarm',
            'einsatz_fehlalarm',
            $report->isFalseAlarm()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            'Besonderer Einsatz',
            'einsatz_special',
            $report->isSpecial()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            'Bilder im Bericht',
            'einsatz_hasimages',
            $report->hasImages()
        );
    }

    /**
     * Inhalt der Metabox zum Bearbeiten der Einsatzdetails
     *
     * @param WP_Post $post Das Post-Objekt des aktuell bearbeiteten Einsatzberichts
     */
    public function displayMetaBoxEinsatzdetails($post)
    {
        // Use nonce for verification
        wp_nonce_field('save_einsatz_details', 'einsatzverwaltung_nonce');

        $report = new IncidentReport($post);

        $nummer = $report->getNumber();
        $alarmzeit = $report->getTimeOfAlerting();
        $einsatzende = $report->getTimeOfEnding();
        $einsatzort = $report->getLocation();
        $einsatzleiter = $report->getIncidentCommander();
        $mannschaftsstaerke = $report->getWorkforce();

        $names = $this->getEinsatzleiterNamen();
        printf('<input type="hidden" id="einsatzleiter_used_values" value="%s" />', esc_attr(implode(',', $names)));
        echo '<table><tbody>';

        if (get_option('einsatzverwaltung_incidentnumbers_auto', '0') === '1') {
            echo '<tr><td>Einsatznummer</td><td>' . esc_html($nummer) . '</td></tr>';
        } else {
            $this->echoInputText(
                'Einsatznummer',
                'einsatzverwaltung_nummer',
                esc_attr($nummer),
                '',
                10
            );
        }

        $this->echoInputText(
            'Alarmzeit',
            'einsatzverwaltung_alarmzeit',
            esc_attr($alarmzeit->format('Y-m-d H:i')),
            'JJJJ-MM-TT hh:mm'
        );

        $this->echoInputText(
            'Einsatzende',
            'einsatz_einsatzende',
            esc_attr($einsatzende),
            'JJJJ-MM-TT hh:mm'
        );

        echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

        $this->echoInputText(
            'Einsatzort',
            'einsatz_einsatzort',
            esc_attr($einsatzort)
        );

        $this->echoInputText(
            'Einsatzleiter',
            'einsatz_einsatzleiter',
            esc_attr($einsatzleiter)
        );

        $this->echoInputText(
            'Mannschaftsst&auml;rke',
            'einsatz_mannschaft',
            esc_attr($mannschaftsstaerke)
        );

        echo '</tbody></table>';
    }

    /**
     * Zeigt die Metabox für die Einsatzart
     *
     * @param WP_Post $post Post-Object
     */
    public static function displayMetaBoxEinsatzart($post)
    {
        $report = new IncidentReport($post);
        $typeOfIncident = $report->getTypeOfIncident();
        self::dropdownEinsatzart($typeOfIncident ? $typeOfIncident->term_id : 0);
    }

    /**
     * @param WP_Post $post
     */
    public function displayMetaBoxUnits(WP_Post $post)
    {
        $units = get_posts(array(
            'post_type' => Unit::getSlug(),
            'numberposts' => -1,
            'order' => 'ASC',
            'orderby' => 'name'
        ));
        if (empty($units)) {
            $postTypeObject = get_post_type_object(Unit::getSlug());
            printf("<div>%s</div>", esc_html($postTypeObject->labels->not_found));
            return;
        }

        $report = new IncidentReport($post);
        $assignedUnits = array_map(function (WP_Post $unit) {
            return $unit->ID;
        }, $report->getUnits());
        echo '<div><ul>';
        foreach ($units as $unit) {
            $assigned = in_array($unit->ID, $assignedUnits);
            printf(
                '<li><label><input type="checkbox" name="evw_units[]" value="%d"%s>%s</label></li>',
                esc_attr($unit->ID),
                checked($assigned, true, false),
                esc_html($unit->post_title)
            );
        }
        echo '</ul></div>';
    }

    /**
     * A custom meta box for selecting the vehicles
     *
     * @param WP_Post $post
     */
    public function displayMetaBoxVehicles(WP_Post $post)
    {
        $taxonomyObject = get_taxonomy(Vehicle::getSlug());
        if (empty($taxonomyObject)) {
            return;
        }

        $allVehicles = get_terms(array(
            'taxonomy' => Vehicle::getSlug(),
            'hide_empty' => false
        ));
        if (empty($allVehicles)) {
            printf("<div>%s</div>", esc_html($taxonomyObject->labels->no_terms));
            return;
        }
        $outOfServiceVehicles = array_filter($allVehicles, function (WP_Term $vehicle) {
            return get_term_meta($vehicle->term_id, 'out_of_service', true) === '1';
        });
        $inServiceVehicles = array_filter($allVehicles, function (WP_Term $vehicle) {
            return get_term_meta($vehicle->term_id, 'out_of_service', true) !== '1';
        });

        $terms = get_the_terms($post, Vehicle::getSlug());
        if (is_wp_error($terms) || $terms === false) {
            $terms = array();
        }
        $assignedVehicleIds = array_map(function (WP_Term $vehicle) {
            return $vehicle->term_id;
        }, $terms);

        // Output the checkboxes
        echo '<div><ul>';
        $this->echoTermCheckboxes($inServiceVehicles, $taxonomyObject, $assignedVehicleIds);

        if (!empty($outOfServiceVehicles)) {
            echo '<hr>';

            // Automatically expand the details tag, if vehicles in there are assigned to the current post
            $outOfServiceIds = array_map(function (WP_Term $vehicle) {
                return $vehicle->term_id;
            }, $outOfServiceVehicles);
            echo empty(array_intersect($assignedVehicleIds, $outOfServiceIds)) ? '<details>' : '<details open="open">';

            echo '<summary>Fahrzeuge au&szlig;er Dienst</summary>';
            $this->echoTermCheckboxes($outOfServiceVehicles, $taxonomyObject, $assignedVehicleIds);
            echo '</details>';
        }
        echo '</ul></div>';
    }

    /**
     * Zeigt Dropdown mit Hierarchie für die Einsatzart
     *
     * @param string $selected Slug der ausgewählten Einsatzart
     */
    public static function dropdownEinsatzart($selected)
    {
        wp_dropdown_categories(array(
            'show_option_all'    => '',
            'show_option_none'   => '- keine -',
            'orderby'            => 'NAME',
            'order'              => 'ASC',
            'show_count'         => false,
            'hide_empty'         => false,
            'echo'               => true,
            'selected'           => $selected,
            'hierarchical'       => true,
            'name'               => 'tax_input[einsatzart]',
            'taxonomy'           => 'einsatzart',
            'hide_if_empty'      => false
        ));
    }

    /**
     * Gibt ein Eingabefeld für die Metabox aus
     *
     * @param string $label Beschriftung
     * @param string $name Feld-ID
     * @param string $value Feldwert
     * @param string $placeholder Platzhalter
     * @param int $size Größe des Eingabefelds
     */
    private function echoInputText($label, $name, $value, $placeholder = '', $size = 20)
    {
        printf('<tr><td><label for="%1$s">%2$s</label></td>', esc_attr($name), esc_html($label));
        printf(
            '<td><input type="text" id="%1$s" name="%1$s" value="%2$s" size="%3$s" placeholder="%4$s" /></td></tr>',
            esc_attr($name),
            esc_attr($value),
            esc_attr($size),
            esc_attr($placeholder)
        );
    }

    /**
     * Gibt eine Checkbox für die Metabox aus
     *
     * @param string $label Beschriftung
     * @param string $name Feld-ID
     * @param bool $state Zustandswert
     */
    private function echoInputCheckbox($label, $name, $state)
    {
        printf(
            '<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s/><label for="%1$s">%3$s</label>',
            esc_attr($name),
            checked($state, '1', false),
            $label
        );
    }

    /**
     * @param WP_Term[] $terms
     * @param WP_Taxonomy $taxonomy
     * @param int[] $assignedIds
     */
    private function echoTermCheckboxes($terms, $taxonomy, $assignedIds)
    {
        $format = '<li><label><input type="checkbox" name="tax_input[%1$s][]" value="%2$s" %3$s>%4$s</label></li>';
        if ($taxonomy->hierarchical) {
            $format = str_replace('%2$s', '%2$d', $format);
        }
        foreach ($terms as $term) {
            $assigned = in_array($term->term_id, $assignedIds);
            printf(
                $format,
                $taxonomy->name,
                ($taxonomy->hierarchical ? esc_attr($term->term_id) : esc_attr($term->name)),
                checked($assigned, true, false),
                esc_html($term->name)
            );
        }
    }

    /**
     * Gibt die Namen aller bisher verwendeten Einsatzleiter zurück
     *
     * @return array
     */
    public function getEinsatzleiterNamen()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $names = array();
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value <> %s",
            array('einsatz_einsatzleiter', '')
        );
        $results = $wpdb->get_results($query, OBJECT);

        foreach ($results as $result) {
            $names[] = $result->meta_value;
        }
        return $names;
    }
}
