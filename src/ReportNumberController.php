<?php

namespace abrain\Einsatzverwaltung;

use function date_create;
use function date_format;
use function get_option;
use function get_post_field;
use function get_post_type;
use function in_array;
use function intval;
use function is_numeric;
use function sprintf;
use function str_pad;
use function update_post_meta;

/**
 * Takes care of keeping report numbers up-to-date
 * @package abrain\Einsatzverwaltung
 */
class ReportNumberController
{
    const DEFAULT_SEQNUM_DIGITS = 3;

    /**
     * @var Data
     */
    private $data;

    /**
     * ReportNumberController constructor.
     *
     * @param Data $data
     */
    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    /**
     * Sobald die laufende Nummer aktualisiert wird, muss die Einsatznummer neu generiert werden.
     *
     * @param int $metaId ID des postmeta-Eintrags
     * @param int $objectId Post-ID
     * @param string $metaKey Der Key des postmeta-Eintrags
     * @param string $metaValue Der Wert des postmeta-Eintrags
     */
    public function onPostMetaChanged(int $metaId, int $objectId, string $metaKey, string $metaValue)
    {
        // Bail, if this is not about reports
        if (get_post_type($objectId) !== 'einsatz') {
            return;
        }

        if ($metaKey === 'einsatz_seqNum' && self::isAutoIncidentNumbers()) {
            $this->adjustIncidentNumber($objectId, (int)$metaValue);
        }
    }

    /**
     * @param int $postId
     * @param int $sequenceNumber
     */
    private function adjustIncidentNumber(int $postId, int $sequenceNumber)
    {
        $date = date_create(get_post_field('post_date', $postId));
        $newIncidentNumber = $this->formatEinsatznummer(date_format($date, 'Y'), $sequenceNumber);
        update_post_meta($postId, 'einsatz_incidentNumber', $newIncidentNumber);
    }

    /**
     * Formatiert die Einsatznummer
     *
     * @param string $jahr Jahreszahl
     * @param int $nummer Laufende Nummer des Einsatzes im angegebenen Jahr
     *
     * @return string Formatierte Einsatznummer
     */
    private function formatEinsatznummer(string $jahr, int $nummer): string
    {
        $stellen = self::sanitizeEinsatznummerStellen(get_option('einsatzvw_einsatznummer_stellen'));
        $lfdvorne = (get_option('einsatzvw_einsatznummer_lfdvorne', false) == '1');
        $format = $lfdvorne ? '%2$s%1$s' : '%1$s%2$s';
        return sprintf($format, $jahr, str_pad($nummer, $stellen, "0", STR_PAD_LEFT));
    }

    /**
     * @return bool
     */
    public static function isAutoIncidentNumbers(): bool
    {
        return (get_option('einsatzverwaltung_incidentnumbers_auto', '0') === '1');
    }

    /**
     * @param string $option Name of the added option
     * @param mixed $value Value of the added option
     */
    public function onOptionAdded(string $option, $value)
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
    public static function sanitizeEinsatznummerStellen($input): int
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
    private function updateAllIncidentNumbers()
    {
        $years = $this->data->getYearsWithReports();
        foreach ($years as $year) {
            $reportQuery = new ReportQuery();
            $reportQuery->setOrderAsc(true);
            $reportQuery->setIncludePrivateReports(true);
            $reportQuery->setYear($year);
            $reports = $reportQuery->getReports();

            foreach ($reports as $report) {
                $newIncidentNumber = $this->formatEinsatznummer($year, (int)$report->getSequentialNumber());
                update_post_meta($report->getPostId(), 'einsatz_incidentNumber', $newIncidentNumber);
            }
        }
    }

    /**
     * Prüft, ob die automatische Verwaltung der Einsatznummern aktiviert wurde, und deshalb alle Einsatznummern
     * aktualisiert werden müssen
     *
     * @param string $option Name der Option
     * @param mixed $oldValue Der alte Wert
     * @param mixed $newValue Der neue Wert
     */
    public function maybeAutoIncidentNumbersChanged(string $option, $oldValue, $newValue)
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
     * @param mixed $oldValue Der alte Wert
     * @param mixed $newValue Der neue Wert
     */
    public function maybeIncidentNumberFormatChanged(string $option, $oldValue, $newValue)
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
