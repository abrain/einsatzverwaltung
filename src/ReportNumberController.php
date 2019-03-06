<?php

namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;

/**
 * Takes care of keeping report numbers up-to-date
 * @package abrain\Einsatzverwaltung
 */
class ReportNumberController
{
    const DEFAULT_SEQNUM_DIGITS = 3;

    /**
     * ReportNumberController constructor.
     */
    public function __construct()
    {
        if (self::isAutoIncidentNumbers()) {
            add_action('updated_postmeta', array($this, 'adjustIncidentNumber'), 10, 4);
            add_action('added_post_meta', array($this, 'adjustIncidentNumber'), 10, 4);
        }
        add_action('updated_option', array($this, 'maybeAutoIncidentNumbersChanged'), 10, 3);
        add_action('updated_option', array($this, 'maybeIncidentNumberFormatChanged'), 10, 3);
        add_action('add_option_einsatzverwaltung_incidentnumbers_auto', array($this, 'onOptionAdded'), 10, 2);
    }

    /**
     * Sobald die laufende Nummer aktualisiert wird, muss die Einsatznummer neu generiert werden.
     *
     * @param int $metaId ID des postmeta-Eintrags
     * @param int $objectId Post-ID
     * @param string $metaKey Der Key des postmeta-Eintrags
     * @param string $metaValue Der Wert des postmeta-Eintrags
     */
    public function adjustIncidentNumber($metaId, $objectId, $metaKey, $metaValue)
    {
        // Nur Änderungen an der laufenden Nummer sind interessant
        if ('einsatz_seqNum' != $metaKey) {
            return;
        }

        // Für den unwahrscheinlichen Fall, dass der Metakey bei anderen Beitragstypen verwendet wird, ist hier Schluss
        $postType = get_post_type($objectId);
        if ('einsatz' != $postType) {
            return;
        }

        $date = date_create(get_post_field('post_date', $objectId));
        $newIncidentNumber = $this->formatEinsatznummer(date_format($date, 'Y'), $metaValue);
        update_post_meta($objectId, 'einsatz_incidentNumber', $newIncidentNumber);
    }

    /**
     * Formatiert die Einsatznummer
     *
     * @param string $jahr Jahreszahl
     * @param int $nummer Laufende Nummer des Einsatzes im angegebenen Jahr
     *
     * @return string Formatierte Einsatznummer
     */
    public function formatEinsatznummer($jahr, $nummer)
    {
        $stellen = self::sanitizeEinsatznummerStellen(get_option('einsatzvw_einsatznummer_stellen'));
        $lfdvorne = (get_option('einsatzvw_einsatznummer_lfdvorne', false) === '1');
        $format = $lfdvorne ? '%2$s%1$s' : '%1$s%2$s';
        return sprintf($format, $jahr, str_pad($nummer, $stellen, "0", STR_PAD_LEFT));
    }

    /**
     * @return bool
     */
    public static function isAutoIncidentNumbers()
    {
        return (get_option('einsatzverwaltung_incidentnumbers_auto', '0') === '1');
    }

    /**
     * @param string $option Name of the added option
     * @param mixed $value Value of the added option
     */
    public function onOptionAdded($option, $value)
    {
        if ($option === 'einsatzverwaltung_incidentnumbers_auto') {
            $this->maybeAutoIncidentNumbersChanged($option, '', $value);
        }
    }

    /**
     * Stellt einen sinnvollen Wert für die Anzahl Stellen der laufenden Einsatznummer sicher
     *
     * @param mixed $input
     *
     * @return int
     */
    public static function sanitizeEinsatznummerStellen($input)
    {
        if (!is_numeric($input)) {
            return self::DEFAULT_SEQNUM_DIGITS;
        }

        $val = intval($input);
        if ($val <= 0) {
            return self::DEFAULT_SEQNUM_DIGITS;
        }

        return $val;
    }

    /**
     * Generiert für alle Einsatzberichte eine Einsatznummer gemäß dem aktuell konfigurierten Format.
     */
    public function updateAllIncidentNumbers()
    {
        $years = Data::getJahreMitEinsatz();
        foreach ($years as $year) {
            $posts = Data::getEinsatzberichte($year);
            foreach ($posts as $post) {
                $incidentReport = new IncidentReport($post);
                $seqNum = $incidentReport->getSequentialNumber();
                $newIncidentNumber = $this->formatEinsatznummer($year, $seqNum);
                update_post_meta($post->ID, 'einsatz_incidentNumber', $newIncidentNumber);
            }
        }
    }

    /**
     * Prüft, ob die automatische Verwaltung der Einsatznummern aktiviert wurde, und deshalb alle Einsatznummern
     * aktualisiert werden müssen
     *
     * @param string $option Name der Option
     * @param string $oldValue Der alte Wert
     * @param string $newValue Der neue Wert
     */
    public function maybeAutoIncidentNumbersChanged($option, $oldValue, $newValue)
    {
        // Wir sind nur an einer bestimmten Option interessiert
        if ('einsatzverwaltung_incidentnumbers_auto' != $option) {
            return;
        }

        // Nur Änderungen sind interessant
        if ($newValue == $oldValue) {
            return;
        }

        // Die automatische Verwaltung wurde aktiviert
        if ($newValue == 1) {
            $this->updateAllIncidentNumbers();
        }
    }

    /**
     * Prüft, ob sich das Format der Einsatznummern geändert hat, und deshalb alle Einsatznummern aktualisiert werden
     * müssen
     *
     * @param string $option Name der Option
     * @param string $oldValue Der alte Wert
     * @param string $newValue Der neue Wert
     */
    public function maybeIncidentNumberFormatChanged($option, $oldValue, $newValue)
    {
        // Wir sind nur an bestimmten Optionen interessiert
        if (!in_array($option, array('einsatzvw_einsatznummer_stellen', 'einsatzvw_einsatznummer_lfdvorne'))) {
            return;
        }

        // Nur Änderungen sind interessant
        if ($newValue == $oldValue) {
            return;
        }

        // Nur neu formatieren, wenn die Einsatznummern automatisch verwaltet werden
        if (get_option('einsatzverwaltung_incidentnumbers_auto') !== '1') {
            return;
        }

        $this->updateAllIncidentNumbers();
    }
}
