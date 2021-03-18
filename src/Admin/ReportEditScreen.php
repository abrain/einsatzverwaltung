<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\IncidentType;
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
use function array_key_exists;
use function array_map;
use function checked;
use function esc_attr;
use function esc_attr__;
use function esc_html;
use function esc_html__;
use function get_taxonomy;
use function get_term_meta;
use function get_terms;
use function get_the_terms;
use function in_array;
use function is_wp_error;
use function join;
use function preg_grep;
use function preg_match;
use function preg_match_all;
use function printf;
use function sprintf;
use function str_replace;
use function usort;
use const PREG_GREP_INVERT;

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
            __('Incident details', 'einsatzverwaltung'),
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
            __('Annotations', 'einsatzverwaltung'),
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
            __('Incident Category', 'einsatzverwaltung'),
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
    public function displayMetaBoxAnnotations(WP_Post $post)
    {
        $report = new IncidentReport($post);

        $this->echoInputCheckbox(
            __('False alarm', 'einsatzverwaltung'),
            'einsatz_fehlalarm',
            $report->isFalseAlarm()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            __('Featured report', 'einsatzverwaltung'),
            'einsatz_special',
            $report->isSpecial()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            __('Report contains pictures', 'einsatzverwaltung'),
            'einsatz_hasimages',
            $report->hasImages()
        );
    }

    /**
     * Inhalt der Metabox zum Bearbeiten der Einsatzdetails
     *
     * @param WP_Post $post Das Post-Objekt des aktuell bearbeiteten Einsatzberichts
     */
    public function displayMetaBoxEinsatzdetails(WP_Post $post)
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
        $weight = $report->getWeight();

        $names = $this->getEinsatzleiterNamen();
        printf('<input type="hidden" id="einsatzleiter_used_values" value="%s" />', esc_attr(implode(',', $names)));
        echo '<div style="display: flex; flex-wrap: wrap; justify-content: space-between"><table><tbody>';

        if (get_option('einsatzverwaltung_incidentnumbers_auto', '0') === '1') {
            $numberText = $report->isDraft() ? __('Will be generated upon publication', 'einsatzverwaltung') : $nummer;
            printf(
                '<tr><td>%s</td><td class="incidentnumber">%s</td></tr>',
                esc_html__('Incident number', 'einsatzverwaltung'),
                esc_html($numberText)
            );
        } else {
            $this->echoInputText(
                __('Incident number', 'einsatzverwaltung'),
                'einsatzverwaltung_nummer',
                esc_attr($nummer),
                '',
                10
            );
        }

        $this->echoInputText(
            __('Alarm time', 'einsatzverwaltung'),
            'einsatzverwaltung_alarmzeit',
            esc_attr($alarmzeit->format('Y-m-d H:i')),
            'YYYY-MM-TT hh:mm'
        );

        $this->echoInputText(
            __('End time', 'einsatzverwaltung'),
            'einsatz_einsatzende',
            esc_attr($einsatzende),
            'YYYY-MM-TT hh:mm'
        );

        echo '</tbody></table><table><tbody>';

        $this->echoInputText(
            __('Location', 'einsatzverwaltung'),
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

        echo '<div>';
        printf(
            '<label for="einsatz_weight">%1$s</label>&nbsp;',
            esc_html__('Number of incidents covered in this report:', 'einsatzverwaltung')
        );
        printf(
            '<input type="number" id="einsatz_weight" name="einsatz_weight" value="%1$s" min="1" size="3"/>',
            esc_attr($weight)
        );
        printf(
            '<p class="description">%1$s</p>',
            esc_html__('Influences numbering and report counting.', 'einsatzverwaltung')
        );
        echo '</div></div>';
    }

    /**
     * Zeigt die Metabox für die Einsatzart
     *
     * @param WP_Post $post Post-Object
     */
    public static function displayMetaBoxEinsatzart(WP_Post $post)
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
        $units = get_terms(array(
            'taxonomy' => Unit::getSlug(),
            'hide_empty' => false
        ));

        if (is_wp_error($units)) {
            printf("<div>%s</div>", $units->get_error_message());
            return;
        }

        $taxonomyObject = get_taxonomy(Unit::getSlug());
        if (empty($units)) {
            printf("<div>%s</div>", esc_html($taxonomyObject->labels->no_terms));
            return;
        }

        // Sort the units according to the custom order numbers
        usort($units, array(Unit::class, 'compare'));

        $report = new IncidentReport($post);
        $assignedUnits = array_map(function (WP_Term $unit) {
            return $unit->term_id;
        }, $report->getUnits());

        echo '<div><ul>';
        $this->echoTermCheckboxes($units, $taxonomyObject, $assignedUnits);
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

        // Distinguish by 'out of service' flag
        $outOfServiceVehicles = array_filter($allVehicles, function (WP_Term $vehicle) {
            return get_term_meta($vehicle->term_id, 'out_of_service', true) === '1';
        });
        $inServiceVehicles = array_filter($allVehicles, function (WP_Term $vehicle) {
            return get_term_meta($vehicle->term_id, 'out_of_service', true) !== '1';
        });

        // Sort the vehicles according to the custom order numbers
        usort($inServiceVehicles, array(Vehicle::class, 'compareVehicles'));
        usort($outOfServiceVehicles, array(Vehicle::class, 'compareVehicles'));

        // Determine vehicles assigned to the current report
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

            // Automatically expand the details tag, if vehicles in there are assigned to the current report
            $outOfServiceIds = array_map(function (WP_Term $vehicle) {
                return $vehicle->term_id;
            }, $outOfServiceVehicles);
            echo empty(array_intersect($assignedVehicleIds, $outOfServiceIds)) ? '<details>' : '<details open="open">';

            echo sprintf("<summary>%s</summary>", esc_html__('Out of service', 'einsatzverwaltung'));
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
    public static function dropdownEinsatzart(string $selected)
    {
        wp_dropdown_categories(array(
            'show_option_all'    => '',
            'show_option_none'   => _x('- none -', 'incident category dropdown', 'einsatzverwaltung'),
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
     * Modifies the output of wp_dropdown_categories() for the taxonomy einsatzart to put entries marked as outdated at
     * the end of the list.
     *
     * @param string $output
     * @param array $parsedArgs
     *
     * @return string
     */
    public function filterIncidentCategoryDropdown(string $output, array $parsedArgs): string
    {
        if (!array_key_exists('taxonomy', $parsedArgs) || $parsedArgs['taxonomy'] !== IncidentType::getSlug()) {
            return $output;
        }

        $outdatedTermsIds = get_terms([
            'taxonomy' => IncidentType::getSlug(),
            'hide_empty' => false,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => 'outdated', 'value' => '1']
            ]
        ]);
        if (empty($outdatedTermsIds)) {
            // If no categories are marked as outdated, don't alter the output
            return $output;
        }

        // Separeate the select opening tag and the options
        if (preg_match('/^(<select [^>]+>)(.*?)<\/select>$/ms', $output, $matches) !== 1) {
            return $output;
        }

        // Extract the option tags
        if (empty(preg_match_all('/<option [^>]+>[^<]+<\/option>/', $matches[2], $options))) {
            return $output;
        }

        // Separate the current from the outdated options
        $pattern = sprintf('/value="(%s)"/', join('|', $outdatedTermsIds));
        $currentOptions = preg_grep($pattern, $options[0], PREG_GREP_INVERT);
        $outdatedOptions = preg_grep($pattern, $options[0]);

        // Begin the new output with opening the select tag
        $newOutput = $matches[1];
        $newOutput .= join("\n", $currentOptions);
        $newOutput .= sprintf('<optgroup label="%s">', esc_attr__('Outdated', 'einsatzverwaltung'));
        $newOutput .= join("\n", $outdatedOptions);
        $newOutput .= '</optgroup></select>';

        return $newOutput;
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
    private function echoInputText(string $label, string $name, string $value, $placeholder = '', $size = 20)
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
    private function echoInputCheckbox(string $label, string $name, bool $state)
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
    private function echoTermCheckboxes(array $terms, WP_Taxonomy $taxonomy, array $assignedIds)
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
    public function getEinsatzleiterNamen(): array
    {
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
