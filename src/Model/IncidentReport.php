<?php

namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\Taxonomies;
use DateTime;
use WP_Post;

/**
 * Datenmodellklasse für Einsatzberichte
 *
 * TODO Rückgabetypen festlegen und sicherstellen
 *
 * @author Andreas Brain
 */
class IncidentReport
{
    /**
     * Wenn es sich um einen bestehenden Beitrag handelt, ist hier das WordPress-Beitragsobjekt gespeichert.
     *
     * @var WP_Post
     */
    private $post;

    /**
     * IncidentReport constructor.
     *
     * @param int|WP_Post $post
     */
    public function __construct($post = null)
    {
        if (!empty($post) && !is_int($post) && !is_a($post, 'WP_Post')) {
            _doing_it_wrong(__FUNCTION__, 'Parameter post muss null oder vom Typ Integer oder WP_Post sein', null);
            return;
        }

        if (!empty($post)) {
            if (get_post_type($post) != 'einsatz') {
                _doing_it_wrong(__FUNCTION__, 'WP_Post-Objekt ist kein Einsatzbericht', null);
                return;
            }

            $this->post = get_post($post);
        }
    }

    /**
     * Gibt die Beschriftung für ein Feld zurück
     *
     * @param string $field Slug des Feldes
     *
     * @return string Die Beschriftung oder $field, wenn es das Feld nicht gibt
     */
    public static function getFieldLabel($field)
    {
        $fields = self::getFields();
        return (array_key_exists($field, $fields) ? $fields[$field]['label'] : $field);
    }

    /**
     * Gibt ein Array aller Felder und deren Namen zurück,
     * Hauptverwendungszweck ist das Mapping beim Import
     */
    public static function getFields()
    {
        return array_merge(self::getMetaFields(), self::getTerms(), self::getPostFields());
    }

    /**
     * Gibt die slugs und Namen der Metafelder zurück
     *
     * @return array
     */
    public static function getMetaFields()
    {
        return array(
            'einsatz_einsatzort' => array(
                'label' => 'Einsatzort'
            ),
            'einsatz_einsatzleiter' => array(
                'label' => 'Einsatzleiter'
            ),
            'einsatz_einsatzende' => array(
                'label' => 'Einsatzende'
            ),
            'einsatz_fehlalarm' => array(
                'label' => 'Fehlalarm'
            ),
            'einsatz_mannschaft' => array(
                'label' => 'Mannschaftsstärke'
            ),
            'einsatz_special' => array(
                'label' => 'Besonderer Einsatz'
            ),
        );
    }

    /**
     * Komparator für Fahrzeuge
     *
     * @param object $vehicle1
     * @param object $vehicle2
     *
     * @return int
     */
    private function compareVehicles($vehicle1, $vehicle2)
    {
        if (empty($vehicle1->vehicle_order) && !empty($vehicle2->vehicle_order)) {
            return 1;
        }

        if (!empty($vehicle1->vehicle_order) && empty($vehicle2->vehicle_order)) {
            return -1;
        }

        if (empty($vehicle1->vehicle_order) && empty($vehicle2->vehicle_order) ||
            $vehicle1->vehicle_order == $vehicle2->vehicle_order
        ) {
            return strcasecmp($vehicle1->name, $vehicle2->name);
        }

        return ($vehicle1->vehicle_order < $vehicle2->vehicle_order) ? -1 : 1;
    }

    /**
     * Gibt die slugs und Namen der Taxonomien zurück
     *
     * @return array
     */
    public static function getTerms()
    {
        return array(
            'alarmierungsart' => array(
                'label' => 'Alarmierungsart'
            ),
            'einsatzart' => array(
                'label' => 'Einsatzart'
            ),
            'fahrzeug' => array(
                'label' => 'Fahrzeuge'
            ),
            'exteinsatzmittel' => array(
                'label' => 'Externe Einsatzmittel'
            )
        );
    }

    /**
     * Gibt slugs und Namen der Direkt dem Post zugeordneten Felder zurück
     *
     * @return array
     */
    public static function getPostFields()
    {
        return array(
            'post_date' => array(
                'label' => 'Alarmzeit'
            ),
            'post_name' => array(
                'label' => 'Einsatznummer'
            ),
            'post_content' => array(
                'label' => 'Berichtstext'
            ),
            'post_title' => array(
                'label' => 'Berichtstitel'
            )
        );
    }

    /**
     * TODO Methodenname überdenken
     *
     * @return array|bool|\WP_Error
     */
    public function getAdditionalForces()
    {
        return get_the_terms($this->post->ID, 'exteinsatzmittel');
    }

    /**
     * Gibt den eingetragenen Einsatzleiter zurück
     *
     * @return mixed
     */
    public function getIncidentCommander()
    {
        return get_post_meta($this->post->ID, 'einsatz_einsatzleiter', true);
    }

    /**
     * Gibt den eingetragenen Einsatzort zurück
     *
     * @return mixed
     */
    public function getLocation()
    {
        return get_post_meta($this->post->ID, 'einsatz_einsatzort', true);
    }

    /**
     * Gibt die Einsatznummer zurück
     *
     * @return string
     */
    public function getNumber()
    {
        return get_post_field('post_name', $this->post->ID);
    }

    /**
     * @return bool|int
     */
    public function getPostId()
    {
        return $this->post ? $this->post->ID : false;
    }

    /**
     * Gibt die laufende Nummer des Einsatzberichts bezogen auf das Kalenderjahr zurück
     *
     * @return mixed
     */
    public function getSequentialNumber()
    {
        return get_post_meta($this->post->ID, 'einsatz_seqNum', true);
    }

    /**
     * Gibt Alarmdatum und -zeit zurück
     *
     * @return DateTime|false
     */
    public function getTimeOfAlerting()
    {
        if (empty($this->post)) {
            return false;
        }

        $time = $this->post->post_date;
        return DateTime::createFromFormat('Y-m-d H:i:s', $time);
    }

    /**
     * Gibt Datum und Zeit des Einsatzendes zurück
     *
     * @return mixed
     */
    public function getTimeOfEnding()
    {
        return get_post_meta($this->post->ID, 'einsatz_einsatzende', true);
    }

    /**
     * Gibt das Term-Objekt der Alarmierungsart zurück
     *
     * @return array|bool|\WP_Error
     */
    public function getTypesOfAlerting()
    {
        return get_the_terms($this->post->ID, 'alarmierungsart');
    }

    /**
     * Bestimmt die Einsatzart eines bestimmten Einsatzes. Ist nötig, weil die Taxonomie
     * 'einsatzart' mehrere Werte speichern kann, aber nur einer genutzt werden soll
     *
     * @return object|bool
     */
    public function getTypeOfIncident()
    {
        $einsatzarten = get_the_terms($this->post->ID, 'einsatzart');
        if ($einsatzarten && !is_wp_error($einsatzarten) && !empty($einsatzarten)) {
            $keys = array_keys($einsatzarten);
            return $einsatzarten[$keys[0]];
        }

        return false;
    }

    /**
     * Gibt die Fahrzeuge eines Einsatzberichts aus
     *
     * @return array|bool|\WP_Error
     */
    public function getVehicles()
    {
        $vehicles = get_the_terms($this->post->ID, 'fahrzeug');

        if (empty($vehicles)) {
            return array();
        }

        // Reihenfolge abfragen
        foreach ($vehicles as $vehicle) {
            if (!isset($vehicle->term_id)) {
                continue;
            }

            $vehicleOrder = Taxonomies::getTermField($vehicle->term_id, 'fahrzeug', 'vehicleorder');
            if (!empty($vehicleOrder)) {
                $vehicle->vehicle_order = $vehicleOrder;
            }
        }

        // Fahrzeuge vor Rückgabe sortieren
        usort($vehicles, array($this, 'compareVehicles'));

        return $vehicles;
    }

    /**
     * Gibt die eingetragene Mannschaftsstärke zurück
     *
     * @return mixed
     */
    public function getWorkforce()
    {
        return get_post_meta($this->post->ID, 'einsatz_mannschaft', true);
    }

    /**
     * @return mixed
     */
    public function isFalseAlarm()
    {
        return get_post_meta($this->post->ID, 'einsatz_fehlalarm', true);
    }

    /**
     * Gibt zurück, ob ein Einsatzbericht als besonders markiert wurde oder nicht
     *
     * @return bool
     */
    public function isSpecial()
    {
        return get_post_meta($this->post->ID, 'einsatz_special', true);
    }
}
