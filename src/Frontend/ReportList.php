<?php
namespace abrain\Einsatzverwaltung\Frontend;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Utilities;
use DateTime;

/**
 * Tabellarische Übersicht für Einsatzberichte
 *
 * @author Andreas Brain
 * @package abrain\Einsatzverwaltung\Frontend
 */
class ReportList
{
    private $columns;

    /**
     * @var Core
     */
    private $core;

    private $numberOfColumns;

    /**
     * Gibt an, ob nach jedem Monat eine Trennung eingefügt werden soll
     *
     * @var bool
     */
    private $splitMonths;

    /**
     * In diesem String wird der HTML-Code für die Liste aufgebaut
     *
     * @var string
     */
    private $string;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * ReportList constructor.
     *
     * @param Utilities $utilities
     * @param Core $core
     */
    public function __construct($utilities, $core)
    {
        $this->utilities = $utilities;
        $this->core = $core;
    }

    /**
     * Generiert den HTML-Code für die Liste
     *
     * @param array $reports Eine Liste von IncidentReport-Objekten
     * @param array $args
     */
    private function constructList($reports, $args)
    {
        if (empty($reports)) {
            $this->string = '<span>F&uuml;r den gew&auml;hlten Zeitraum stehen keine Einsatzberichte zur Verf&uuml;gung</span>';
            return;
        }

        $defaults = array(
            'splitMonths' => false,
            'columns' => array()
        );
        $parsedArgs = wp_parse_args($args, $defaults);

        // TODO Argumente validieren
        $this->splitMonths = boolval($parsedArgs['splitMonths']);
        $this->columns = $this->utilities->sanitizeColumnsArray($parsedArgs['columns']);
        $this->numberOfColumns = count($this->columns);

        $veryFirstReport = true;
        $currentYear = null;
        $currentMonth = null;
        $previousYear = null;
        $previousMonth = null;
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $timeOfAlerting = $report->getTimeOfAlerting();
            $currentYear = intval($timeOfAlerting->format('Y'));
            $currentMonth = intval($timeOfAlerting->format('m'));

            if ($veryFirstReport) {
                $this->beginTable($currentYear);
                $veryFirstReport = false;
            }

            if ($previousYear != null && $currentYear != $previousYear) {
                $previousMonth = null;
                $this->endTable();
                $this->beginTable($currentYear);
                if (!$this->splitMonths) {
                    $this->insertTableHeader();
                }
            }

            if ($this->splitMonths && $currentMonth != $previousMonth) {
                $this->insertMonthSeparator($timeOfAlerting);
                $this->insertTableHeader();
            }

            $this->insertRow($report);

            $previousYear = $currentYear;
            $previousMonth = $currentMonth;
        }
        $this->endTable();
    }

    /**
     * Gibt den HTML-Code für die Liste zurück
     *
     * @param array $reports Eine Liste von IncidentReport-Objekten
     * @param array $args
     *
     * @return string HTML-Code der Liste
     */
    public function getList($reports, $args)
    {
        if (empty($this->string)) {
            $this->constructList($reports, $args);
        }

        return $this->string;
    }

    /**
     * @param array $reports Eine Liste von IncidentReport-Objekten
     * @param array $args
     */
    public function printList($reports, $args)
    {
        echo $this->getList($reports, $args);
    }

    /**
     * Beginnt eine neue Tabelle für ein bestimmtes Jahr
     *
     * @param int $year Das Kalenderjahr für die Überschrift
     */
    private function beginTable($year)
    {
        $this->string .= '<h2>Eins&auml;tze '.$year.'</h2>';
        $this->string .= '<table class="responsive-stacked-table with-mobile-labels"><tbody>';
    }

    private function endTable()
    {
        $this->string .= '</tbody></table>';
    }

    private function insertTableHeader()
    {
        $allColumns = $this->core->getListColumns();

        $this->string .= '<tr class="einsatz-header">';
        foreach ($this->columns as $colId) {
            if (!array_key_exists($colId, $allColumns)) {
                $this->string .= '<th>&nbsp;</th>';
                continue;
            }

            $colInfo = $allColumns[$colId];
            $style = $this->utilities->getArrayValueIfKey($colInfo, 'nowrap', false) ? 'white-space: nowrap;' : '';
            $this->string .= '<th' . (empty($style) ? '' : ' style="' . $style . '"') . '>' . $colInfo['name'] . '</th>';
        }
        $this->string .= '</tr>';
    }

    /**
     * @param DateTime $date
     */
    private function insertMonthSeparator($date)
    {
        $this->string .= '<tr><td class="einsatz-title-month" colspan="' . $this->numberOfColumns . '">';
        $this->string .=  date_i18n('F', $date->getTimestamp()) . '</td></tr>';
    }

    /**
     * @param IncidentReport $report Der Einsatzbericht
     */
    private function insertRow($report)
    {
        $this->string .= '<tr>';
        foreach ($this->columns as $colId) {
            $this->string .= '<td class="einsatz-column-' . $colId . '">';
            // TODO Inhalt der Zelle
            $this->string .= '&nbsp;</td>';
        }
        $this->string .= '</tr>';
    }
}
