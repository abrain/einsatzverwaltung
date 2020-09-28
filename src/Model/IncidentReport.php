<?php

namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\Types\Vehicle;
use DateTime;
use WP_Post;
use WP_Term;
use function array_filter;
use function get_post;
use function get_post_type;
use function error_log;
use function intval;
use function is_numeric;

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
        if (empty($post)) {
            return;
        }

        if (get_post_type($post) !== 'einsatz') {
            error_log('The given post object is not an incident report'); // TODO throw exception
            return;
        }

        $this->post = get_post($post);
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
            'einsatz_incidentNumber' => array(
                'label' => 'Einsatznummer'
            ),
        );
    }

    /**
     * Gibt die Einsatzdauer in Minuten zurück
     *
     * @return bool|int Dauer in Minuten oder false, wenn Alarmzeit und/oder Einsatzende nicht verfügbar sind
     */
    public function getDuration()
    {
        $timeOfAlerting = $this->getPostDate();
        $timeOfEnding = $this->getTimeOfEnding();

        if (empty($timeOfAlerting) || empty($timeOfEnding)) {
            return false;
        }

        // Create DateTime objects with the proper time zone information
        $dateTimeZone = wp_timezone();
        $startDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $timeOfAlerting, $dateTimeZone);
        $endDateTime = DateTime::createFromFormat('Y-m-d H:i', $timeOfEnding, $dateTimeZone);
        if ($startDateTime === false || $endDateTime === false) {
            return false;
        }

        $timestamp1 = $startDateTime->getTimestamp();
        $timestamp2 = $endDateTime->getTimestamp();
        $differenz = $timestamp2 - $timestamp1;

        return intval($differenz / 60);
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
     * Gibt die Einsatznummer zurück
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->getPostMeta('einsatz_incidentNumber');
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
        $postDate = $this->getPostDate();

        if ($postDate === false) {
            return false;
        }

        return DateTime::createFromFormat('Y-m-d H:i:s', $postDate);
    }

    /**
     * @return bool|string
     */
    private function getPostDate()
    {
        if (empty($this->post)) {
            return false;
        }

        // Solange der Einsatzbericht ein Entwurf ist, wird die Alarmzeit in Postmeta vorgehalten
        if ($this->isDraft() || $this->isFuture()) {
            $postDate = $this->getPostMeta('_einsatz_timeofalerting');
        }

        if (empty($postDate)) {
            $postDate = $this->post->post_date;
        }

        return $postDate;
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
     * @return WP_Term[] Die Terms oder ein leeres Array
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
     * @return WP_Term
     */
    public function getTypeOfIncident()
    {
        $terms = $this->getTheTerms('einsatzart');

        if (empty($terms)) {
            return null;
        }

        $keys = array_keys($terms);
        return $terms[$keys[0]];
    }

    /**
     * @return WP_Post[]
     */
    public function getUnits()
    {
        $unitIds = get_post_meta($this->getPostId(), '_evw_unit');
        $units = array_map('get_post', $unitIds);
        return array_filter($units, function ($unit) {
            return !empty($unit);
        });
    }

    /**
     * Gibt die Fahrzeuge eines Einsatzberichts aus
     *
     * @return WP_Term[]
     */
    public function getVehicles()
    {
        $vehicles = $this->getTheTerms('fahrzeug');

        if (empty($vehicles)) {
            return array();
        }

        usort($vehicles, array(Vehicle::class, 'compareVehicles'));

        return $vehicles;
    }

    /**
     * @return int The weight of the report (i. e. how many reports it represents)
     */
    public function getWeight()
    {
        $weight = $this->getPostMeta('einsatz_weight');
        if (empty($weight) || !is_numeric($weight)) {
            return 1;
        }

        return intval($weight);
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
     * Gibt zurück, ob der Einsatzbericht Bilder beinhaltet.
     *
     * @return bool
     */
    public function hasImages()
    {
        return ($this->getPostMeta('einsatz_hasimages') == 1);
    }

    /**
     * Gibt zurück, ob der Einsatzbericht noch im Entwurfsstadium ist
     *
     * @return bool
     */
    public function isDraft()
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

    /**
     * Gibt zurück, ob der Einsatzbericht geplant ist
     *
     * @return bool
     */
    private function isFuture()
    {
        return $this->post->post_status === 'future';
    }

    /**
     * Veranlasst die Zuordnung des Einsatzberichts zu einer Kategorie
     *
     * @param int $category Die ID der Kategorie
     */
    public function addToCategory($category)
    {
        wp_set_post_categories($this->getPostId(), $category, true);
    }
}
