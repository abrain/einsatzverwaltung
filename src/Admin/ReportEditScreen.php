<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\IncidentType;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Types\Vehicle;
use abrain\Einsatzverwaltung\Utilities;
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
use function get_term;
use function get_term_meta;
use function get_terms;
use function in_array;
use function is_wp_error;
use function join;
use function preg_grep;
use function preg_match;
use function preg_match_all;
use function printf;
use function sprintf;
use function usort;
use function wp_dropdown_categories;
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
            array($this, 'displayMetaBoxEinsatzart'),
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
            __('Units and Vehicles', 'einsatzverwaltung'),
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
            'YYYY-MM-DD hh:mm'
        );

        $this->echoInputText(
            __('End time', 'einsatzverwaltung'),
            'einsatz_einsatzende',
            esc_attr($einsatzende),
            'YYYY-MM-DD hh:mm'
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
    public function displayMetaBoxEinsatzart(WP_Post $post)
    {
        $report = new IncidentReport($post);
        $typeOfIncident = $report->getTypeOfIncident();
        wp_dropdown_categories(array(
            'show_option_all'    => '',
            'show_option_none'   => _x('- none -', 'incident category dropdown', 'einsatzverwaltung'),
            'orderby'            => 'NAME',
            'order'              => 'ASC',
            'show_count'         => false,
            'hide_empty'         => false,
            'echo'               => true,
            'selected'           => $typeOfIncident ? $typeOfIncident->term_id : 0,
            'hierarchical'       => true,
            'name'               => 'tax_input[einsatzart]',
            'taxonomy'           => 'einsatzart',
            'hide_if_empty'      => false
        ));
    }

    /**
     * A custom meta box for selecting the vehicles
     *
     * @param WP_Post $post
     */
    public function displayMetaBoxVehicles(WP_Post $post)
    {
        $vehicleTaxonomy = get_taxonomy(Vehicle::getSlug());
        if (empty($vehicleTaxonomy)) {
            return;
        }

        // Determine if units should be shown
        $showUnits = Unit::hasTerms();

        $allVehicles = get_terms(array(
            'taxonomy' => Vehicle::getSlug(),
            'hide_empty' => false
        ));
        if (empty($allVehicles) && !$showUnits) {
            printf("<div>%s</div>", esc_html__('No units and no vehicles', 'einsatzverwaltung'));
            return;
        }

        // Distinguish by 'out of service' flag
        $outOfServiceVehicles = array_filter($allVehicles, function (WP_Term $vehicle) {
            return get_term_meta($vehicle->term_id, 'out_of_service', true) === '1';
        });
        $inServiceVehicles = array_filter($allVehicles, function (WP_Term $vehicle) {
            return get_term_meta($vehicle->term_id, 'out_of_service', true) !== '1';
        });

        // Determine vehicles assigned to the current report
        $assignedVehicleIds = get_terms([
            'taxonomy' => Vehicle::getSlug(),
            'object_ids' => $post->ID,
            'fields' => 'ids'
        ]);
        if (is_wp_error($assignedVehicleIds)) {
            $assignedVehicleIds = [];
        }

        echo '<div>';
        if ($showUnits) {
            $this->echoVehiclesByUnit($post, $vehicleTaxonomy, $inServiceVehicles, $assignedVehicleIds);
        } else {
            // Sort the vehicles according to the custom order numbers
            usort($inServiceVehicles, array(Vehicle::class, 'compareVehicles'));

            echo '<ul>';
            $this->echoTermCheckboxes($inServiceVehicles, $vehicleTaxonomy, $assignedVehicleIds);
            echo '</ul>';
        }

        if (!empty($outOfServiceVehicles)) {
            echo '<hr>';
            usort($outOfServiceVehicles, array(Vehicle::class, 'compareVehicles'));

            // Automatically expand the details tag, if vehicles in there are assigned to the current report
            $outOfServiceIds = array_map(function (WP_Term $vehicle) {
                return $vehicle->term_id;
            }, $outOfServiceVehicles);
            echo empty(array_intersect($assignedVehicleIds, $outOfServiceIds)) ? '<details>' : '<details open="open">';

            echo sprintf("<summary>%s</summary>", esc_html__('Out of service', 'einsatzverwaltung'));
            echo '<ul>';
            $this->echoTermCheckboxes($outOfServiceVehicles, $vehicleTaxonomy, $assignedVehicleIds);
            echo '</ul>';
            echo '</details>';
        }
        echo '</div>';
    }

    /**
     * Echoes the checkboxes for vehicles and units, grouped by unit.
     *
     * @param WP_Post $post
     * @param WP_Taxonomy $vehicleTaxonomy
     * @param array $vehicles
     * @param array $assignedVehicleIds
     */
    private function echoVehiclesByUnit(WP_Post $post, WP_Taxonomy $vehicleTaxonomy, array $vehicles, array $assignedVehicleIds)
    {
        $unitTaxObj = get_taxonomy(Unit::getSlug());
        if (empty($unitTaxObj)) {
            return;
        }

        $assignedUnitIds = get_terms([
            'taxonomy' => Unit::getSlug(),
            'object_ids' => $post->ID,
            'fields' => 'ids'
        ]);
        if (is_wp_error($assignedVehicleIds)) {
            $assignedVehicleIds = [];
        }

        foreach (Utilities::groupVehiclesByUnit($vehicles) as $unitId => $unitVehicles) {
            if ($unitId === -1) {
                printf('<p style="margin-top: 13px;"><b>%s</b></p>', esc_html__('Without Unit', 'einsatzverwaltung'));
            } else {
                $unit = get_term($unitId, Unit::getSlug());
                printf(
                    '<label><input type="checkbox" name="tax_input[%1$s][]" value="%2$s" %3$s><b>%4$s</b></label>',
                    $unitTaxObj->name,
                    esc_attr($unit->name),
                    checked(in_array($unit->term_id, $assignedUnitIds), true, false),
                    esc_html($unit->name)
                );
            }

            if (empty($unitVehicles)) {
                echo '<br>';
                continue;
            }

            echo '<ul style="margin-left: 1.5em;">';
            $this->echoTermCheckboxes($unitVehicles, $vehicleTaxonomy, $assignedVehicleIds);
            echo '</ul>';
        }
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
