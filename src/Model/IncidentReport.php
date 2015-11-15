<?php

namespace abrain\Einsatzverwaltung\Model;

/**
 * Datenmodellklasse für Einsatzberichte
 *
 * @author Andreas Brain
 */
class IncidentReport
{
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
            )
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
}
