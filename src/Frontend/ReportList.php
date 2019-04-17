<?php
namespace abrain\Einsatzverwaltung\Frontend;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Util\Formatter;
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
    const TABLECLASS = 'einsatzverwaltung-reportlist';

    /**
     * Array mit Spalten-IDs, die nicht mit einem Link zum Einsatzbericht versehen werden dürfen
     *
     * @var array
     */
    private $columnsLinkBlacklist = array('incidentCommander', 'location', 'vehicles', 'alarmType', 'additionalForces',
        'incidentType', 'units');

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var ReportListSettings
     */
    private static $settings;

    /**
     * In diesem String wird der HTML-Code für die Liste aufgebaut
     *
     * @var string
     */
    private $string;

    /**
     * ReportList constructor.
     *
     * @param Formatter $formatter
     */
    public function __construct($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Generiert den HTML-Code für die Liste
     *
     * @param array $reports Eine Liste von IncidentReport-Objekten
     * @param ReportListParameters $parameters
     */
    private function constructList($reports, ReportListParameters $parameters)
    {
        if (empty($reports)) {
            $this->string = sprintf(
                '<span>%s</span>',
                esc_html('F&uuml;r den gew&auml;hlten Zeitraum stehen keine Einsatzberichte zur Verf&uuml;gung')
            );
            return;
        }

        // Berichte abarbeiten
        $currentYear = null;
        $currentMonth = null;
        $previousYear = null;
        $previousMonth = null;
        $monthlyCounter = 0;
        $numberOfColumns = count($parameters->getColumns());
        if ($parameters->compact) {
            $this->beginTable(false, $parameters);
            $this->insertTableHeader($parameters);
        }
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $timeOfAlerting = $report->getTimeOfAlerting();
            $currentYear = intval($timeOfAlerting->format('Y'));
            $currentMonth = intval($timeOfAlerting->format('m'));

            // Ein neues Jahr beginnt
            if (!$parameters->compact && $currentYear != $previousYear) {
                // Wenn mindestens schon ein Jahr ausgegeben wurde
                if ($previousYear != null) {
                    $previousMonth = null;
                    $this->endTable();
                }

                $this->beginTable($currentYear, $parameters);
                if (!$parameters->isSplitMonths()) {
                    $this->insertTableHeader($parameters);
                    $this->insertZebraCorrection($numberOfColumns);
                }

                $monthlyCounter = 0;
            }

            // Monatswechsel bei aktivierter Monatstrennung
            if ($parameters->isSplitMonths() && $currentMonth != $previousMonth) {
                if ($monthlyCounter > 0 && $monthlyCounter % 2 != 0) {
                    $this->insertZebraCorrection($numberOfColumns);
                }
                $this->insertMonthSeparator($timeOfAlerting, $numberOfColumns);
                $this->insertTableHeader($parameters);
                $monthlyCounter = 0;
            }

            // Zeile für den aktuellen Bericht ausgeben
            $this->insertRow($report, $parameters);
            $monthlyCounter++;

            // Variablen für den nächsten Durchgang setzen
            $previousYear = $currentYear;
            $previousMonth = $currentMonth;
        }
        $this->endTable();
    }

    /**
     * Gibt den HTML-Code für die Liste zurück
     *
     * @param array $reports Eine Liste von IncidentReport-Objekten
     * @param ReportListParameters $parameters
     *
     * @return string HTML-Code der Liste
     */
    public function getList($reports, ReportListParameters $parameters)
    {
        $this->string = '';
        $this->constructList($reports, $parameters);
        return $this->string;
    }

    /**
     * @param array $reports Eine Liste von IncidentReport-Objekten
     * @param ReportListParameters $parameters
     */
    public function printList($reports, ReportListParameters $parameters)
    {
        echo $this->getList($reports, $parameters);
    }

    /**
     * Beginnt eine neue Tabelle für ein bestimmtes Jahr
     *
     * @param bool|int $year Das Kalenderjahr für die Überschrift oder false um keine Überschrift auszugeben
     * @param ReportListParameters $parameters
     */
    private function beginTable($year, ReportListParameters $parameters)
    {
        if ($parameters->showHeading && $year !== false) {
            $this->string .= '<h2>Eins&auml;tze '.$year.'</h2>';
        }
        $this->string .= '<table class="' . self::TABLECLASS . '"><tbody>';
    }

    private function endTable()
    {
        $this->string .= '</tbody></table>';
    }

    /**
     * @param ReportListParameters $parameters
     */
    private function insertTableHeader(ReportListParameters $parameters)
    {
        $allColumns = self::getListColumns();

        $this->string .= '<tr class="einsatz-header">';
        foreach ($parameters->getColumns() as $colId) {
            if (!array_key_exists($colId, $allColumns)) {
                $this->string .= '<th>&nbsp;</th>';
                continue;
            }

            $colInfo = $allColumns[$colId];
            $style = Utilities::getArrayValueIfKey($colInfo, 'nowrap', false) ? 'white-space: nowrap;' : '';
            $this->string .= sprintf('<th style="%s">%s</th>', esc_attr($style), esc_html($colInfo['name']));
        }
        $this->string .= '</tr>';
    }

    /**
     * @param DateTime $date
     * @param int $numberOfColumns
     */
    private function insertMonthSeparator($date, $numberOfColumns)
    {
        $this->string .= '<tr class="einsatz-title-month"><td colspan="' . $numberOfColumns . '">';
        $this->string .=  date_i18n('F', $date->getTimestamp()) . '</td></tr>';
    }

    /**
     * @param IncidentReport $report Der Einsatzbericht
     * @param ReportListParameters $parameters
     */
    private function insertRow($report, ReportListParameters $parameters)
    {
        $this->string .= '<tr class="report">';
        foreach ($parameters->getColumns() as $colId) {
            $this->string .= $this->getCellMarkup($report, $parameters, $colId);
        }
        $this->string .= '</tr>';
    }

    /**
     * @param IncidentReport $report
     * @param ReportListParameters $parameters
     * @param string $columnId
     *
     * @return string
     */
    private function getCellMarkup(IncidentReport $report, ReportListParameters $parameters, $columnId)
    {
        $cellContent = $this->getCellContent($report, $columnId, $parameters);

        if ($this->isCellLinkToReport($report, $columnId, $parameters)) {
            $cellContent = sprintf(
                '<a href="%s" rel="bookmark">%s</a>',
                esc_url(get_permalink($report->getPostId())),
                $cellContent
            );
        }

        return sprintf('<td class="einsatz-column-%s">%s</td>', $columnId, $cellContent);
    }

    /**
     * Determines if a certain table cell should contain a link to the report
     *
     * @param IncidentReport $report
     * @param string $columnId
     * @param ReportListParameters $parameters
     *
     * @return bool
     */
    private function isCellLinkToReport(IncidentReport $report, $columnId, ReportListParameters $parameters)
    {
        $linkToReport = $parameters->linkEmptyReports || $report->hasContent();
        $columnsLinkingReport = $parameters->getColumnsLinkingReport();
        $userWantsLink = !empty($columnsLinkingReport) && in_array($columnId, $columnsLinkingReport);

        return $linkToReport && $userWantsLink && !in_array($columnId, $this->columnsLinkBlacklist);
    }

    /**
     * Fügt eine unsichtbare Zeile ein, um das Zebramuster in bestimmten Fällen zu erhalten
     *
     * @param int $numberOfColumns
     */
    private function insertZebraCorrection($numberOfColumns)
    {
        $this->string .= '<tr class="zebracorrection"><td colspan="'.$numberOfColumns.'">&nbsp;</td></tr>';
    }

    /**
     * Gibt den Inhalt der Tabellenzelle einer bestimmten Spalte für einen bestimmten Einsatzbericht zurück
     *
     * @param IncidentReport $report
     * @param string $colId Eindeutige Kennung der Spalte
     * @param ReportListParameters $parameters
     *
     * @return string
     */
    private function getCellContent($report, $colId, ReportListParameters $parameters)
    {
        if (empty($report)) {
            return '&nbsp;';
        }

        $timeOfAlerting = $report->getTimeOfAlerting();

        switch ($colId) {
            case 'number':
                $cellContent = $report->getNumber();
                break;
            case 'date':
                $cellContent = $timeOfAlerting->format('d.m.Y');
                break;
            case 'time':
                $cellContent = $timeOfAlerting->format('H:i');
                break;
            case 'datetime':
                $cellContent = $timeOfAlerting->format('d.m.Y H:i');
                break;
            case 'title':
                $postTitle = get_the_title($report->getPostId());
                $cellContent =  empty($postTitle) ? '(kein Titel)' : $postTitle;
                break;
            case 'incidentCommander':
                $cellContent = $report->getIncidentCommander();
                break;
            case 'location':
                $cellContent = $report->getLocation();
                break;
            case 'workforce':
                $cellContent = $report->getWorkforce();
                break;
            case 'duration':
                $minutes = $report->getDuration();
                $cellContent = $this->formatter->getDurationString($minutes, true);
                break;
            case 'vehicles':
                $cellContent = $this->formatter->getVehicles($report, $parameters->linkVehicles, false);
                break;
            case 'alarmType':
                $cellContent = $this->formatter->getTypesOfAlerting($report);
                break;
            case 'additionalForces':
                $cellContent = $this->formatter->getAdditionalForces($report, $parameters->linkAdditionalForces, false);
                break;
            case 'units':
                $cellContent = $this->formatter->getUnits($report);
                break;
            case 'incidentType':
                $showHierarchy = (get_option('einsatzvw_list_art_hierarchy', '0') === '1');
                $cellContent = $this->formatter->getTypeOfIncident($report, false, false, $showHierarchy);
                break;
            case 'seqNum':
                $cellContent = $report->getSequentialNumber();
                break;
            case 'annotationImages':
                $cellContent = AnnotationIconBar::getInstance()->render($report, array('images'));
                break;
            case 'annotationSpecial':
                $cellContent = AnnotationIconBar::getInstance()->render($report, array('special'));
                break;
            default:
                $cellContent = '';
        }

        // Damit Zellen einer Tabelle nicht komplett leer sind
        if (empty($cellContent)) {
            $cellContent = '&nbsp;';
        }

        return $cellContent;
    }

    /**
     * Gibt die möglichen Spalten für die Tabelle zurück
     *
     * @return array
     */
    public static function getListColumns()
    {
        return array(
            'number' => array(
                'name' => 'Nummer',
                'nowrap' => true
            ),
            'date' => array(
                'name' => 'Datum',
                'nowrap' => true
            ),
            'time' => array(
                'name' => 'Zeit',
                'nowrap' => true
            ),
            'datetime' => array(
                'name' => 'Datum',
                'longName' => 'Datum + Zeit',
                'nowrap' => true
            ),
            'title' => array(
                'name' => 'Einsatzmeldung'
            ),
            'incidentCommander' => array(
                'name' => 'Einsatzleiter'
            ),
            'location' => array(
                'name' => 'Einsatzort'
            ),
            'workforce' => array(
                'name' => 'Mannschaftsst&auml;rke',
                'cssname' => 'Mannschaftsst\0000E4rke',
            ),
            'duration' => array(
                'name' => 'Dauer',
                'nowrap' => true
            ),
            'vehicles' => array(
                'name' => 'Fahrzeuge'
            ),
            'alarmType' => array(
                'name' => 'Alarmierungsart'
            ),
            'additionalForces' => array(
                'name' => 'Weitere Kr&auml;fte',
                'cssname' => 'Weitere Kr\0000E4fte',
            ),
            'units' => array(
                'name' => __('Units', 'einsatzverwaltung')
            ),
            'incidentType' => array(
                'name' => 'Einsatzart'
            ),
            'seqNum' => array(
                'name' => 'Lfd.',
                'longName' => 'Laufende Nummer'
            ),
            'annotationImages' => array(
                'name' => '',
                'longName' => 'Vermerk &quot;Bilder im Bericht&quot;'
            ),
            'annotationSpecial' => array(
                'name' => '',
                'longName' => 'Vermerk &quot;Besonderer Einsatz&quot;'
            )
        );
    }

    /**
     * Generiert CSS-Code, der von Einstellungen abhängt oder nicht gut von Hand zu pflegen ist
     *
     * @return string
     */
    public static function getDynamicCss()
    {
        $reportListSettings = self::$settings; // FIXME Verrenkung, um PHP 5.3.0 als Minimum zu ermöglichen, solange das
                                               // Ende der Untersützung nicht im Blog angekündigt wurde.
        if (empty($reportListSettings)) {      // NEEDS_PHP5.5 direkt empty(self::$settings) prüfen
            self::$settings = new ReportListSettings();
        }

        $string = '';

        // Sollen Zebrastreifen angezeigt werden?
        if (self::$settings->isZebraTable()) {
            $string .= sprintf(
                '.%s tr.report:nth-child(%s) { background-color: %s; }',
                self::TABLECLASS,
                self::$settings->getZebraNthChildArg(),
                self::$settings->getZebraColor()
            );
        }

        // Bei der responsiven Ansicht die selben Begriffe voranstellen wie im Tabellenkopf
        $string .= '@media (max-width: 767px) {';
        foreach (self::getListColumns() as $colId => $colInfo) {
            $string .= sprintf(
                '.%s td.einsatz-column-%s:before {content: "%s:";}',
                self::TABLECLASS,
                $colId,
                (array_key_exists('cssname', $colInfo) ? $colInfo['cssname'] : $colInfo['name'])
            ).PHP_EOL;
        }
        $string .= '}';

        return $string;
    }
}
