<?php

namespace abrain\Einsatzverwaltung;

use function add_action;
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
use function update_option;
use function update_post_meta;

/**
 * Takes care of keeping report numbers up-to-date
 * @package abrain\Einsatzverwaltung
 */
class ReportNumberController
{
    const DEFAULT_SEQNUM_DIGITS = 3;
    const DEFAULT_SEPARATOR = 'none';

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
     * Register the actions and filters, that this class expects.
     */
    public function addHooks()
    {
        add_action('updated_postmeta', array($this, 'onPostMetaChanged'), 10, 4);
        add_action('added_post_meta', array($this, 'onPostMetaChanged'), 10, 4);
        add_action('updated_option', array($this, 'maybeAutoIncidentNumbersChanged'), 10, 3);
        add_action('updated_option', array($this, 'maybeIncidentNumberFormatChanged'), 10, 3);
        add_action('added_option', array($this, 'onOptionAdded'), 10, 2);
    }

    /**
     * Sobald die laufende Nummer aktualisiert wird, muss die Einsatznummer neu generiert werden.
     *
     * @param int $metaId ID des postmeta-Eintrags
     * @param int $objectId Post-ID
     * @param string $metaKey Der Key des postmeta-Eintrags
     * @param mixed $metaValue Der Wert des postmeta-Eintrags
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) A WordPress hook with fixed signature
     * @noinspection PhpUnusedParameterInspection
     */
    public function onPostMetaChanged(int $metaId, int $objectId, string $metaKey, $metaValue)
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
     * @return string
     */
    private function determineSeparator(): string
    {
        switch (self::sanitizeSeparator(get_option('einsatzvw_numbers_separator', self::DEFAULT_SEPARATOR))) {
            case 'slash':
                return '/';
            case 'hyphen':
                return '-';
            default:
                return '';
        }
    }

    /**
     * Formatiert die Einsatznummer
     *
     * @param string $jahr Jahreszahl
     * @param int $nummer Laufende Nummer des Einsatzes im angegebenen Jahr
     *
     * @return string Formatierte Einsatznummer
     */
    public function formatEinsatznummer(string $jahr, int $nummer): string
    {
        $stellen = self::sanitizeNumberOfDigits(get_option('einsatzvw_einsatznummer_stellen'));
        $sequentialFirst = (get_option('einsatzvw_einsatznummer_lfdvorne', false) == '1');
        $separator = $this->determineSeparator();

        return sprintf(
            $sequentialFirst ? '%2$s%3$s%1$s' : '%1$s%3$s%2$s',
            $jahr,
            str_pad($nummer, $stellen, "0", STR_PAD_LEFT),
            $separator
        );
    }

    public function formatNumberRange(int $year, int $start, int $count): string
    {
        $minimumNumberOfDigits = self::sanitizeNumberOfDigits(get_option('einsatzvw_einsatznummer_stellen'));
        $sequentialFirst = (get_option('einsatzvw_einsatznummer_lfdvorne', false) == '1');
        $separator = $this->determineSeparator();

        return sprintf(
            $sequentialFirst ? '%2$s – %3$s%4$s%1$d' : '%1$d%4$s%2$s – %3$s',
            $year,
            zeroise($start, $minimumNumberOfDigits),
            zeroise($start + $count - 1, $minimumNumberOfDigits),
            $separator
        );
    }

    /**
     * @return bool
     */
    public static function isAutoIncidentNumbers(): bool
    {
        return (get_option('einsatzverwaltung_incidentnumbers_auto', '0') === '1');
    }

    /**
     * If one of the format-defining options is added for the first time, behave as if the option got changed. The
     * default value is passed as the previous value.
     *
     * @param string $option Name of the added option
     * @param mixed $value Value of the added option
     */
    public function onOptionAdded(string $option, $value)
    {
        switch ($option) {
            case 'einsatzverwaltung_incidentnumbers_auto':
                $this->maybeAutoIncidentNumbersChanged($option, '0', $value);
                break;
            case 'einsatzvw_einsatznummer_stellen':
                $this->maybeIncidentNumberFormatChanged($option, self::DEFAULT_SEQNUM_DIGITS, $value);
                break;
            case 'einsatzvw_einsatznummer_lfdvorne':
                $this->maybeIncidentNumberFormatChanged($option, '0', $value);
                break;
            case 'einsatzvw_numbers_separator':
                $this->maybeIncidentNumberFormatChanged($option, self::DEFAULT_SEPARATOR, $value);
                break;
        }
    }

    /**
     * Stellt einen sinnvollen Wert für die Anzahl Stellen der laufenden Einsatznummer sicher
     *
     * @param mixed $input
     *
     * @return int
     */
    public static function sanitizeNumberOfDigits($input): int
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
     * Sanitizes the option value for the separator between year and sequential number.
     *
     * @param string $input
     *
     * @return string
     */
    public static function sanitizeSeparator(string $input): string
    {
        if (in_array($input, ['none', 'slash', 'hyphen'])) {
            return $input;
        }

        return self::DEFAULT_SEPARATOR;
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
            update_option('einsatzverwaltung_reformat_numbers', '1');
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
        // Make sure this is about one of the format options
        $formatOptions = array(
            'einsatzvw_einsatznummer_stellen',
            'einsatzvw_einsatznummer_lfdvorne',
            'einsatzvw_numbers_separator'
        );
        if (!in_array($option, $formatOptions)) {
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

        update_option('einsatzverwaltung_reformat_numbers', '1');
    }

    public function maybeReformatIncidentNumbers()
    {
        if (get_option('einsatzverwaltung_reformat_numbers', '0') === '1') {
            $this->updateAllIncidentNumbers();
            update_option('einsatzverwaltung_reformat_numbers', '0');
        }
    }
}
