<?php
namespace abrain\Einsatzverwaltung\Frontend;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
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
     * @var AnnotationIconBar
     */
    private $annotationIconBar;

    /**
     * @var array
     */
    private $columns;

    /**
     * Gibt an, ob die Tabelle in kompakter Form, also ohne Trennung zwischen den Jahren angezeigt werden soll
     *
     * @var bool
     */
    private $compact;

    /**
     * Array mit Spalten-IDs, die nicht mit einem Link zum Einsatzbericht versehen werden dürfen
     *
     * @var array
     */
    private $columnsLinkBlacklist = array('incidentCommander', 'location', 'vehicles', 'alarmType', 'additionalForces',
        'incidentType');

    /**
     * Array mit Spalten-IDs, die mit einem Link zum Einsatzbericht versehen werden sollen
     *
     * @var array
     */
    private $columnsWithLink;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * Gibt an, ob ein Link für Einsatzberichte ohne Beitragstext erzeugt werden soll
     *
     * @var bool
     */
    private $linkEmptyReports;

    /**
     * Gibt an, ob die zusätzlichen Kräfte mit einem Link zu der ggf. gesetzten Adresse versehen werden sollen
     *
     * @var bool
     */
    private $linkToAddForces;

    /**
     * Gibt an, ob die Fahrzeuge mit einem Link zu der ggf. gesetzten Fahrzeugseite versehen werden sollen
     *
     * @var bool
     */
    private $linkToVehicles;

    /**
     * @var int
     */
    private $numberOfColumns;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var ReportListSettings
     */
    private static $settings;

    /**
     * Gibt an, ob oberhalb einer Tabelle die Überschrift mit der Jahreszahl angezeigt werden soll
     *
     * @var bool
     */
    private $showHeading;

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
     * @param Options $options
     * @param Formatter $formatter
     */
    public function __construct($utilities, $core, $options, $formatter)
    {
        $this->utilities = $utilities;
        $this->core = $core;
        $this->options = $options;
        $this->formatter = $formatter;
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

        // Argumente auswerten
        $defaults = array(
            'splitMonths' => false,
            'columns' => array(),
            'linkToVehicles' => $this->options->getBoolOption('einsatzvw_list_fahrzeuge_link'),
            'linkToAddForces' => $this->options->getBoolOption('einsatzvw_list_ext_link'),
            'columnsWithLink' => array('title'),
            'linkEmptyReports' => true,
            'showHeading' => true,
            'compact' => false,
        );
        $parsedArgs = wp_parse_args($args, $defaults);

        // Variablen setzen
        $this->compact = (bool) $parsedArgs['compact'];
        $this->splitMonths = (bool) $parsedArgs['splitMonths'] && !$this->compact;
        $this->columns = $this->utilities->sanitizeColumnsArray($parsedArgs['columns']);
        $this->numberOfColumns = count($this->columns);
        $this->linkToVehicles = (true === $parsedArgs['linkToVehicles']);
        $this->linkToAddForces = (true === $parsedArgs['linkToAddForces']);
        $this->columnsWithLink = $parsedArgs['columnsWithLink'];
        if ($this->columnsWithLink !== false) {
            $this->columnsWithLink = $this->utilities->sanitizeColumnsArray($this->columnsWithLink);
        }
        $this->linkEmptyReports = (true === $parsedArgs['linkEmptyReports']);
        $this->showHeading = (bool) $parsedArgs['showHeading'];

        if (in_array('annotationImages', $this->columns) || in_array('annotationSpecial', $this->columns)) {
            require_once dirname(__FILE__) . '/AnnotationIconBar.php';
            $this->annotationIconBar = new AnnotationIconBar($this->core);
        }

        // Berichte abarbeiten
        $currentYear = null;
        $currentMonth = null;
        $previousYear = null;
        $previousMonth = null;
        $monthlyCounter = 0;
        if ($this->compact) {
            $this->beginTable(false);
            $this->insertTableHeader();
        }
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $timeOfAlerting = $report->getTimeOfAlerting();
            $currentYear = intval($timeOfAlerting->format('Y'));
            $currentMonth = intval($timeOfAlerting->format('m'));

            // Ein neues Jahr beginnt
            if (!$this->compact && $currentYear != $previousYear) {
                // Wenn mindestens schon ein Jahr ausgegeben wurde
                if ($previousYear != null) {
                    $previousMonth = null;
                    $this->endTable();
                }

                $this->beginTable($currentYear);
                if (!$this->splitMonths) {
                    $this->insertTableHeader();
                    $this->insertZebraCorrection();
                }

                $monthlyCounter = 0;
            }

            // Monatswechsel bei aktivierter Monatstrennung
            if ($this->splitMonths && $currentMonth != $previousMonth) {
                if ($monthlyCounter > 0 && $monthlyCounter % 2 != 0) {
                    $this->insertZebraCorrection();
                }
                $this->insertMonthSeparator($timeOfAlerting);
                $this->insertTableHeader();
                $monthlyCounter = 0;
            }

            // Zeile für den aktuellen Bericht ausgeben
            $this->insertRow($report);
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
     * @param bool|int $year Das Kalenderjahr für die Überschrift oder false um keine Überschrift auszugeben
     */
    private function beginTable($year)
    {
        if ($this->showHeading && $year !== false) {
            $this->string .= '<h2>Eins&auml;tze '.$year.'</h2>';
        }
        $this->string .= '<table class="' . self::TABLECLASS . '"><tbody>';
    }

    private function endTable()
    {
        $this->string .= '</tbody></table>';
    }

    private function insertTableHeader()
    {
        $allColumns = self::getListColumns();

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
        $this->string .= '<tr class="einsatz-title-month"><td colspan="' . $this->numberOfColumns . '">';
        $this->string .=  date_i18n('F', $date->getTimestamp()) . '</td></tr>';
    }

    /**
     * @param IncidentReport $report Der Einsatzbericht
     */
    private function insertRow($report)
    {
        $this->string .= '<tr class="report">';
        foreach ($this->columns as $colId) {
            $this->string .= '<td class="einsatz-column-' . $colId . '">';
            $linkToReport = $this->linkEmptyReports || $report->hasContent();
            $linkThisColumn = $linkToReport && !empty($this->columnsWithLink) &&
                in_array($colId, $this->columnsWithLink) && !in_array($colId, $this->columnsLinkBlacklist);
            if ($linkThisColumn) {
                $this->string .= '<a href="' . get_permalink($report->getPostId()) . '" rel="bookmark">';
            }
            $this->string .= $this->getCellContent($report, $colId);
            if ($linkThisColumn) {
                $this->string .= '</a>';
            }
            $this->string .= '</td>';
        }
        $this->string .= '</tr>';
    }

    /**
     * Fügt eine unsichtbare Zeile ein, um das Zebramuster in bestimmten Fällen zu erhalten
     */
    private function insertZebraCorrection()
    {
        $this->string .= '<tr class="zebracorrection"><td colspan="'.$this->numberOfColumns.'">&nbsp;</td></tr>';
    }

    /**
     * Gibt den Inhalt der Tabellenzelle einer bestimmten Spalte für einen bestimmten Einsatzbericht zurück
     *
     * @param IncidentReport $report
     * @param string $colId Eindeutige Kennung der Spalte
     *
     * @return string
     */
    private function getCellContent($report, $colId)
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
                $minutes = Data::getDauer($report);
                $cellContent = $this->utilities->getDurationString($minutes, true);
                break;
            case 'vehicles':
                $cellContent = $this->formatter->getVehicles($report, $this->linkToVehicles, false);
                break;
            case 'alarmType':
                $cellContent = $this->formatter->getTypesOfAlerting($report);
                break;
            case 'additionalForces':
                $cellContent = $this->formatter->getAdditionalForces($report, $this->linkToAddForces, false);
                break;
            case 'incidentType':
                $showHierarchy = $this->options->getBoolOption('einsatzvw_list_art_hierarchy');
                $cellContent = $this->formatter->getTypeOfIncident($report, false, false, $showHierarchy);
                break;
            case 'seqNum':
                $cellContent = $report->getSequentialNumber();
                break;
            case 'annotationImages':
                $cellContent = $this->annotationIconBar->render($report, array('images'));
                break;
            case 'annotationSpecial':
                $cellContent = $this->annotationIconBar->render($report, array('special'));
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
        if (empty($reportListSettings)) {
            self::$settings = new ReportListSettings();
        }

        $string = '';

        // Sollen Zebrastreifen angezeigt werden?
        if (self::$settings->isZebraTable()) {
            $string .= '.einsatzverwaltung-reportlist tr.report:nth-child(' . self::$settings->getZebraNthChildArg() . ') {';
            $string .= 'background-color: ' . self::$settings->getZebraColor() . '; }';
        }

        // Bei der responsiven Ansicht die selben Begriffe voranstellen wie im Tabellenkopf
        $string .= '@media (max-width: 767px) {';
        foreach (self::getListColumns() as $colId => $colInfo) {
            $string .= "." . self::TABLECLASS . " td.einsatz-column-$colId:before ";
            $string .= '{content: "' . (array_key_exists('cssname', $colInfo) ? $colInfo['cssname'] : $colInfo['name']) . ':";}';
        }
        $string .= '}';

        return $string;
    }
}
