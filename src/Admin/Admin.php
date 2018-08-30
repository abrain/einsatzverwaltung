<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Report;
use WP_Post;

/**
 * Regelt die Darstellung im Administrationsbereich
 */
class Admin
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
            'high'
        );
        add_meta_box(
            'einsatzverwaltung_meta_annotations',
            'Vermerke',
            array($this, 'displayMetaBoxAnnotations'),
            'einsatz',
            'side'
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
            'meta_input[einsatz_fehlalarm]',
            $report->isFalseAlarm()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            'Besonderer Einsatz',
            'meta_input[einsatz_special]',
            $report->isSpecial()
        );
        echo '<br>';

        $this->echoInputCheckbox(
            'Bilder im Bericht',
            'meta_input[einsatz_hasimages]',
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

        $names = Data::getEinsatzleiterNamen();
        echo '<input type="hidden" id="einsatzleiter_used_values" value="' . implode(',', $names) . '" />';
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
            'meta_input[einsatz_einsatzende]',
            esc_attr($einsatzende),
            'JJJJ-MM-TT hh:mm'
        );

        echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

        $this->echoInputText(
            'Einsatzort',
            'meta_input[einsatz_einsatzort]',
            esc_attr($einsatzort)
        );

        $this->echoInputText(
            'Einsatzleiter',
            'meta_input[einsatz_einsatzleiter]',
            esc_attr($einsatzleiter)
        );

        $this->echoInputText(
            'Mannschaftsst&auml;rke',
            'meta_input[einsatz_mannschaft]',
            esc_attr($mannschaftsstaerke)
        );

        echo '</tbody></table>';
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
     * Legt fest, welche Spalten bei der Übersicht der Einsatzberichte im
     * Adminbereich angezeigt werden
     *
     * @param array $columns
     *
     * @return array
     */
    public function filterColumnsEinsatz($columns)
    {
        unset($columns['author']);
        unset($columns['date']);
        unset($columns['categories']);
        $columns['title'] = 'Einsatzbericht';
        $columns['e_nummer'] = 'Nummer';
        $columns['einsatzverwaltung_annotations'] = 'Vermerke';
        $columns['e_alarmzeit'] = 'Alarmzeit';
        $columns['e_einsatzende'] = 'Einsatzende';
        $columns['e_art'] = 'Art';
        $columns['e_fzg'] = 'Fahrzeuge';

        return $columns;
    }

    /**
     * Liefert den Inhalt für die jeweiligen Spalten bei der Übersicht der
     * Einsatzberichte im Adminbereich
     *
     * @param string $column
     * @param int $postId
     */
    public function filterColumnContentEinsatz($column, $postId)
    {
        $report = new IncidentReport($postId);

        switch ($column) {
            case 'e_nummer':
                $einsatznummer = $report->getNumber();
                echo (empty($einsatznummer) ? '-' : $einsatznummer);
                break;
            case 'e_einsatzende':
                $timeOfEnding = $report->getTimeOfEnding();
                if (empty($timeOfEnding)) {
                    echo '-';
                } else {
                    $timestamp = strtotime($timeOfEnding);
                    echo date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
                }
                break;
            case 'e_alarmzeit':
                $timeOfAlerting = $report->getTimeOfAlerting();

                if (empty($timeOfAlerting)) {
                    echo '-';
                } else {
                    echo $timeOfAlerting->format('d.m.Y') . '<br>' . $timeOfAlerting->format('H:i');
                }
                break;
            case 'e_art':
                $term = $report->getTypeOfIncident();
                if ($term === false) {
                    echo '-';
                    break;
                }

                $url = add_query_arg(array('post_type' => Report::SLUG, 'einsatzart' => $term->slug), 'edit.php');
                $text = sanitize_term_field('name', $term->name, $term->term_id, 'einsatzart', 'display');
                printf('<a href="%s">%s</a>', esc_url($url), esc_html($text));
                break;
            case 'e_fzg':
                $fahrzeuge = $report->getVehicles();
                if (empty($fahrzeuge)) {
                    echo '-';
                    break;
                }

                $out = array();
                foreach ($fahrzeuge as $term) {
                    $url = add_query_arg(array('post_type' => Report::SLUG, 'fahrzeug' => $term->slug), 'edit.php');
                    $text = sanitize_term_field('name', $term->name, $term->term_id, 'fahrzeug', 'display');
                    $out[] = sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($text));
                }
                echo join(', ', $out);
                break;
            case 'einsatzverwaltung_annotations':
                echo AnnotationIconBar::getInstance()->render($report);
                break;
            default:
                break;
        }
    }
}
