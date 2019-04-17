<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Unit;
use WP_Post;
use wpdb;

/**
 * Regelt das Erscheinungsbild des Editors für Einsatzberichte
 * @package abrain\Einsatzverwaltung\Admin
 */
class ReportEditScreen
{
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
            'post_type' => Unit::POST_TYPE,
            'numberposts' => -1,
            'order' => 'ASC',
            'orderby' => 'name'
        ));
        if (empty($units)) {
            $postTypeObject = get_post_type_object(Unit::POST_TYPE);
            printf("<div>%s</div>", esc_html($postTypeObject->labels->not_found));
            return;
        }

        $assignedUnits = get_post_meta($post->ID, '_evw_unit');
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
