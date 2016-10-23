<?php

namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\Taxonomies;
use DateTime;
use WP_Post;

/**
 * Datenmodellklasse für Einsatzberichte
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
            'einsatz_location' => array(
                'label' => 'Goolemaps Position'
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
     * @return array
     */
    public function getAdditionalForces()
    {
        return $this->getTheTerms('exteinsatzmittel');
    }

    /**
     * Gibt den eingetragenen Einsatzleiter zurück
     *
     * @return string
     */
    public function getIncidentCommander()
    {
        return $this->getPostMeta('einsatz_einsatzleiter');
    }

    /**
     * Gibt den eingetragenen Einsatzort zurück
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->getPostMeta('einsatz_einsatzort');
    }

    /**
     * Gibt den eingetragenen Einsatzort als googlemaps koordinate zurück
     *
     * @return string
     */
    public function getGmapsLocation()
    {
        return $this->getPostMeta('einsatz_location');
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
     * @param $key
     *
     * @return string
     */
    private function getPostMeta($key)
    {
        if (empty($this->post)) {
            return '';
        }

        $meta = get_post_meta($this->post->ID, $key, true);

        if (empty($meta)) {
            return '';
        }

        return $meta;
    }

    /**
     * Gibt die laufende Nummer des Einsatzberichts bezogen auf das Kalenderjahr zurück
     *
     * @return mixed
     */
    public function getSequentialNumber()
    {
        return $this->getPostMeta('einsatz_seqNum');
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

        // Solange der Einsatzbericht ein Entwurf ist, wird die Alarmzeit in Postmeta vorgehalten
        if ($this->isDraft()) {
            $time = $this->getPostMeta('_einsatz_timeofalerting');
        }

        if (empty($time)) {
            $time = $this->post->post_date;
        }

        return DateTime::createFromFormat('Y-m-d H:i:s', $time);
    }

    /**
     * Gibt Datum und Zeit des Einsatzendes zurück
     *
     * @return string
     */
    public function getTimeOfEnding()
    {
        return $this->getPostMeta('einsatz_einsatzende');
    }

    /**
     * Gibt das Term-Objekt der Alarmierungsart zurück
     *
     * @return array
     */
    public function getTypesOfAlerting()
    {
        return $this->getTheTerms('alarmierungsart');
    }

    /**
     * Holt die Terms einer bestimmten Taxonomie für den aktuellen Einsatzbericht aus der Datenbank und fängt dabei
     * alle Fehlerfälle ab
     *
     * @param string $taxonomy Der eindeutige Bezeichner der Taxonomie
     *
     * @return array Die Terms oder ein leeres Array
     */
    private function getTheTerms($taxonomy)
    {
        if (empty($this->post)) {
            return array();
        }

        $terms = get_the_terms($this->post->ID, $taxonomy);

        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        return $terms;
    }

    /**
     * Gibt die Einsatzart eines bestimmten Einsatzes zurück. Auch wenn die Taxonomie 'einsatzart' mehrere Werte
     * speichern kann, wird nur der erste zurückgegeben.
     *
     * @return object|false
     */
    public function getTypeOfIncident()
    {
        $terms = $this->getTheTerms('einsatzart');

        if (empty($terms)) {
            return false;
        }

        $keys = array_keys($terms);
        return $terms[$keys[0]];
    }

    /**
     * Gibt die Fahrzeuge eines Einsatzberichts aus
     *
     * @return array
     */
    public function getVehicles()
    {
        $vehicles = $this->getTheTerms('fahrzeug');

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
     * @return string
     */
    public function getWorkforce()
    {
        return $this->getPostMeta('einsatz_mannschaft');
    }

    /**
     * Gibt zurück, ob der Einsatzbericht über einen Beitragstext verfügt
     *
     * @return bool
     */
    public function hasContent()
    {
        return !empty($this->post->post_content);
    }

    /**
     * Gibt zurück, ob der Einsatzbericht noch im Entwurfsstadium ist
     *
     * @return bool
     */
    private function isDraft()
    {
        return in_array($this->post->post_status, array('draft', 'pending', 'auto-draft'));
    }

    /**
     * Gibt zurück, ob es sich um einen Fehlalarm handelte
     *
     * @return bool
     */
    public function isFalseAlarm()
    {
        return ($this->getPostMeta('einsatz_fehlalarm') == 1);
    }

    /**
     * Gibt zurück, ob ein Einsatzbericht als besonders markiert wurde oder nicht
     *
     * @return bool
     */
    public function isSpecial()
    {
        return ($this->getPostMeta('einsatz_special') == 1);
    }
}
