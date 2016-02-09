<?php

namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\Data;
use WP_Post;

/**
 * Datenmodellklasse für Einsatzberichte
 *
 * TODO Methoden aus Klasse Data übernehmen
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
        return Data::getWeitereKraefte($this->post->ID);
    }

    /**
     * Gibt den eingetragenen Einsatzleiter zurück
     *
     * @return mixed
     */
    public function getIncidentCommander()
    {
        return Data::getEinsatzleiter($this->post->ID);
    }

    /**
     * Gibt den eingetragenen Einsatzort zurück
     *
     * @return mixed
     */
    public function getLocation()
    {
        return Data::getEinsatzort($this->post->ID);
    }

    /**
     * Gibt die Einsatznummer zurück
     *
     * @return string
     */
    public function getNumber()
    {
        return Data::getEinsatznummer($this->post->ID);
    }

    /**
     * Gibt die laufende Nummer des Einsatzberichts bezogen auf das Kalenderjahr zurück
     *
     * @return mixed
     */
    public function getSequentialNumber()
    {
        return Data::getLaufendeNummer($this->post->ID);
    }

    /**
     * Gibt Alarmdatum und -zeit zurück
     *
     * @return mixed
     */
    public function getTimeOfAlerting()
    {
        return Data::getAlarmzeit($this->post->ID);
    }

    /**
     * Gibt Datum und Zeit des Einsatzendes zurück
     *
     * @return mixed
     */
    public function getTimeOfEnding()
    {
        return Data::getEinsatzende($this->post->ID);
    }

    /**
     * Gibt das Term-Objekt der Alarmierungsart zurück
     *
     * @return array|bool|\WP_Error
     */
    public function getTypesOfAlerting()
    {
        return Data::getAlarmierungsart($this->post->ID);
    }

    /**
     * Bestimmt die Einsatzart eines bestimmten Einsatzes. Ist nötig, weil die Taxonomie
     * 'einsatzart' mehrere Werte speichern kann, aber nur einer genutzt werden soll
     *
     * @return object|bool
     */
    public function getTypeOfIncident()
    {
        return Data::getEinsatzart($this->post->ID);
    }

    /**
     * Gibt die Fahrzeuge eines Einsatzberichts aus
     *
     * @return array|bool|\WP_Error
     */
    public function getVehicles()
    {
        return Data::getFahrzeuge($this->post->ID);
    }

    /**
     * Gibt die eingetragene Mannschaftsstärke zurück
     *
     * @return mixed
     */
    public function getWorkforce()
    {
        return Data::getMannschaftsstaerke($this->post->ID);
    }

    /**
     * @return mixed
     */
    public function isFalseAlarm()
    {
        return Data::getFehlalarm($this->post->ID);
    }

    /**
     * Gibt zurück, ob ein Einsatzbericht als besonders markiert wurde oder nicht
     *
     * @return bool
     */
    public function isSpecial()
    {
        return Data::isSpecial($this->post->ID);
    }
}
