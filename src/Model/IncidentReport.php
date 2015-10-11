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
            'einsatz_einsatzort' => 'Einsatzort',
            'einsatz_einsatzleiter' => 'Einsatzleiter',
            'einsatz_einsatzende' => 'Einsatzende',
            'einsatz_fehlalarm' => 'Fehlalarm',
            'einsatz_mannschaft' => 'Mannschaftsstärke'
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
            'alarmierungsart' => 'Alarmierungsart',
            'einsatzart' => 'Einsatzart',
            'fahrzeug' => 'Fahrzeuge',
            'exteinsatzmittel' => 'Externe Einsatzmittel'
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
            'post_date' => 'Alarmzeit',
            'post_name' => 'Einsatznummer',
            'post_content' => 'Berichtstext',
            'post_title' => 'Berichtstitel'
        );
    }
}
