<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

use abrain\Einsatzverwaltung\Frontend\AnnotationIconBar;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\Util\Formatter;
use DateTime;
use function esc_html__;
use function intval;
use function sprintf;

/**
 * Tabellarische Übersicht für Einsatzberichte
 *
 * @author Andreas Brain
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class Renderer
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
     * @var int
     */
    private $currentYear = 0;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var int
     */
    private $previousMonth = 0;

    /**
     * @var int
     */
    private $previousYear = 0;

    /**
     * Counts how many rows have been inserted since the last table header, necessary for zebra stripe correction
     *
     * @var int
     */
    private $rowsSinceLastHeader = 0;

    /**
     * @var Settings
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
     * @param IncidentReport[] $reports Eine Liste von IncidentReport-Objekten
     * @param Parameters $parameters
     */
    private function constructList($reports, Parameters $parameters)
    {
        if (empty($reports)) {
            $this->string = sprintf(
                '<span>%s</span>',
                esc_html__('There are no incident reports for the chosen period.', 'einsatzverwaltung')
            );
            return;
        }

        // Berichte abarbeiten
        $this->currentYear = 0;
        $currentMonth = null;
        $currentQuarter = null;
        $previousQuarter = null;
        $this->previousYear = 0;
        $this->previousMonth = 0;
        $this->rowsSinceLastHeader = 0;
        if ($parameters->compact) {
            $this->beginTable(false, $parameters);
            $this->insertTableHeader($parameters);
            $this->insertZebraCorrection($parameters->getColumnCount());
        }
        foreach ($reports as $report) {
            $timeOfAlerting = $report->getTimeOfAlerting();
            $this->currentYear = intval($timeOfAlerting->format('Y'));
            $currentMonth = intval($timeOfAlerting->format('m'));

            // Ein neues Jahr beginnt
            if ($this->currentYear != $this->previousYear) {
                $this->onYearChange($parameters);
            }

            // Ein neuer Monat beginnt
            if ($currentMonth != $this->previousMonth) {
                $currentQuarter = floor(($currentMonth - 1) / 3) + 1;
                if ($currentQuarter !== $previousQuarter) {
                    $this->onQuarterChange($parameters, $currentQuarter);
                }
                $this->onMonthChange($parameters, $timeOfAlerting);
            }

            // Zeile für den aktuellen Bericht ausgeben
            $this->insertRow($report, $parameters);

            // Variablen für den nächsten Durchgang setzen
            $this->previousYear = $this->currentYear;
            $previousQuarter = $currentQuarter;
            $this->previousMonth = $currentMonth;
        }
        $this->endTable();
    }

    /**
     * Gibt den HTML-Code für die Liste zurück
     *
     * @param IncidentReport[] $reports Eine Liste von IncidentReport-Objekten
     * @param Parameters $parameters
     *
     * @return string HTML-Code der Liste
     */
    public function getList($reports, Parameters $parameters): string
    {
        $this->string = '';
        $this->constructList($reports, $parameters);
        return $this->string;
    }

    /**
     * @param IncidentReport[] $reports Eine Liste von IncidentReport-Objekten
     * @param Parameters $parameters
     */
    public function printList($reports, Parameters $parameters)
    {
        echo $this->getList($reports, $parameters);
    }

    /**
     * Beginnt eine neue Tabelle für ein bestimmtes Jahr
     *
     * @param bool|int $year Das Kalenderjahr für die Überschrift oder false um keine Überschrift auszugeben
     * @param Parameters $parameters
     */
    private function beginTable($year, Parameters $parameters)
    {
        if ($parameters->showHeading && $year !== false) {
            // translators: 1: a year
            $this->string .= '<h2>' . esc_html(sprintf(__('Incidents %1$d', 'einsatzverwaltung'), $year)) . '</h2>';
        }
        $this->string .= '<table class="' . self::TABLECLASS . '"><tbody>';
        $this->rowsSinceLastHeader = 0;
    }

    private function endTable()
    {
        $this->string .= '</tbody></table>';
    }

    /**
     * @param Parameters $parameters
     */
    private function insertTableHeader(Parameters $parameters)
    {
        $this->string .= '<tr class="einsatz-header">';
        foreach ($parameters->getColumns() as $column) {
            $style = $column->isNoWrap() ? 'white-space: nowrap;' : '';
            $this->string .= sprintf('<th style="%s">%s</th>', esc_attr($style), esc_html($column->getName()));
        }
        $this->string .= '</tr>';

        $this->rowsSinceLastHeader = 0;
    }

    /**
     * @param int $numberOfColumns
     * @param string $class
     * @param string $text
     */
    private function insertFullWidthRow($numberOfColumns, $class, $text = '&nbsp;')
    {
        $this->string .= sprintf(
            '<tr class="%s"><td colspan="%d">%s</td></tr>',
            esc_attr($class),
            esc_attr($numberOfColumns),
            esc_html($text)
        );
    }

    /**
     * @param IncidentReport $report Der Einsatzbericht
     * @param Parameters $parameters
     */
    private function insertRow($report, Parameters $parameters)
    {
        $this->string .= '<tr class="report">';
        $this->string .= $this->getSmallScreenCell($report, $parameters);
        foreach ($parameters->getColumns() as $column) {
            $cellMarkup = $this->getCellMarkup($report, $parameters, $column->getIdentifier());
            $this->string .= sprintf('<td class="einsatz-column-%s">%s</td>', $column->getIdentifier(), $cellMarkup);
        }
        $this->string .= '</tr>';
        $this->rowsSinceLastHeader++;
    }

    /**
     * @param IncidentReport $report
     * @param Parameters $parameters
     *
     * @return string HTML markup for the table cell only visible on devices with a small screen (e.g. smartphones)
     */
    private function getSmallScreenCell(IncidentReport $report, Parameters $parameters): string
    {
        $content = '';
        $annotations = '';

        foreach ($parameters->getColumns() as $column) {
            $columnValue = $this->getCellMarkup($report, $parameters, $column->getIdentifier());

            // Annotation icons get appended to a different variable
            if (strpos($column->getIdentifier(), 'annotation') === 0) {
                $annotations .= $columnValue;
                continue;
            }

            $columnContent = sprintf('<strong>%1$s:</strong> %2$s', esc_html($column->getName()), $columnValue);

            $content .= sprintf('<span class="einsatz-%s">%s</span><br>', $column->getIdentifier(), $columnContent);
        }

        if (!empty($annotations)) {
            $content = '<div class="annotation-icon-bar">' . $annotations . '</div>' . $content;
        }

        $linkToReport = $parameters->linkEmptyReports || $report->hasContent();
        $permalink = $linkToReport ? get_permalink($report->getPostId()) : '';

        return sprintf(
            '<td class="smallscreen" colspan="%d" data-permalink="%s">%s</td>',
            esc_attr($parameters->getColumnCount()),
            $permalink,
            $content
        );
    }

    /**
     * @param IncidentReport $report
     * @param Parameters $parameters
     * @param string $columnId
     *
     * @return string
     */
    private function getCellMarkup(IncidentReport $report, Parameters $parameters, $columnId): string
    {
        $cellContent = $this->getCellContent($report, $columnId, $parameters);

        if ($this->isCellLinkToReport($report, $columnId, $parameters)) {
            $cellContent = sprintf(
                '<a href="%s" rel="bookmark">%s</a>',
                esc_url(get_permalink($report->getPostId())),
                $cellContent
            );
        }

        return $cellContent;
    }

    /**
     * Determines if a certain table cell should contain a link to the report
     *
     * @param IncidentReport $report
     * @param string $columnId
     * @param Parameters $parameters
     *
     * @return bool
     */
    private function isCellLinkToReport(IncidentReport $report, $columnId, Parameters $parameters): bool
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
        $this->insertFullWidthRow($numberOfColumns, 'zebracorrection');
    }

    /**
     * Gibt den Inhalt der Tabellenzelle einer bestimmten Spalte für einen bestimmten Einsatzbericht zurück
     *
     * @param IncidentReport $report
     * @param string $colId Eindeutige Kennung der Spalte
     * @param Parameters $parameters
     *
     * @return string
     */
    private function getCellContent($report, $colId, Parameters $parameters): string
    {
        if (empty($report)) {
            return '&nbsp;';
        }

        $timeOfAlerting = $report->getTimeOfAlerting();

        switch ($colId) {
            case 'number':
                $cellContent = $report->getNumber();
                break;
            case 'numberRange':
                if ($report->getWeight() > 1 && ReportNumberController::isAutoIncidentNumbers()) {
                    $cellContent = $this->formatter->getReportNumberRange($report);
                } else {
                    $cellContent = $report->getNumber();
                }
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
                $cellContent = $this->formatter->getVehicleString($report->getVehicles(), $parameters->linkVehicles, false);
                break;
            case 'alarmType':
                $cellContent = $this->formatter->getTypesOfAlerting($report, false);
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
                if ($report->getWeight() > 1) {
                    $firstNumber = intval($cellContent);
                    $lastNumber = $firstNumber + $report->getWeight() - 1;
                    $cellContent = sprintf('%1$d&nbsp;- %2$d', $firstNumber, $lastNumber);
                }
                break;
            case 'annotationImages':
                $cellContent = AnnotationIconBar::getInstance()->render($report->getPostId(), array('images'));
                break;
            case 'annotationSpecial':
                $cellContent = AnnotationIconBar::getInstance()->render($report->getPostId(), array('special'));
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
     * @param Parameters $parameters
     */
    private function onYearChange(Parameters $parameters)
    {
        if ($parameters->compact) {
            return;
        }

        // Wenn mindestens schon ein Jahr ausgegeben wurde
        if ($this->previousYear != 0) {
            $this->previousMonth = 0;
            $this->endTable();
        }

        $this->beginTable($this->currentYear, $parameters);
        if (!$parameters->isSplitMonths() && !$parameters->isSplitQuarterly()) {
            $this->insertTableHeader($parameters);
            $this->insertZebraCorrection($parameters->getColumnCount());
        }
    }

    /**
     * @param Parameters $parameters
     * @param int $quarter
     */
    private function onQuarterChange(Parameters $parameters, $quarter)
    {
        if (!$parameters->isSplitQuarterly()) {
            return;
        }

        $heading = sprintf('%d. Quartal', $quarter);
        $this->insertSplit($parameters, 'einsatz-title-quarter', $heading);
    }

    /**
     * @param Parameters $parameters
     * @param DateTime $dateTime
     */
    private function onMonthChange(Parameters $parameters, DateTime $dateTime)
    {
        if (!$parameters->isSplitMonths()) {
            return;
        }

        $heading = date_i18n('F', $dateTime->getTimestamp());
        $this->insertSplit($parameters, 'einsatz-title-month', $heading);
    }

    /**
     * @param Parameters $parameters
     * @param $class
     * @param $heading
     */
    private function insertSplit(Parameters $parameters, $class, $heading)
    {
        $numberOfColumns = $parameters->getColumnCount();

        if ($this->rowsSinceLastHeader > 0 && $this->rowsSinceLastHeader % 2 != 0) {
            $this->insertZebraCorrection($numberOfColumns);
        }

        $this->insertFullWidthRow($numberOfColumns, $class, $heading);
        $this->insertTableHeader($parameters);
    }

    /**
     * Generiert CSS-Code, der von Einstellungen abhängt oder nicht gut von Hand zu pflegen ist
     *
     * @return string
     */
    public static function getDynamicCss(): string
    {
        if (empty(self::$settings)) {
            self::$settings = new Settings();
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

        return $string;
    }
}
