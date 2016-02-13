<?php
namespace abrain\Einsatzverwaltung\Frontend;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Frontend;
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
    /**
     * @var array
     */
    private $columns;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var int
     */
    private $numberOfColumns;

    /**
     * @var Options
     */
    private $options;

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
     */
    public function __construct($utilities, $core, $options)
    {
        $this->utilities = $utilities;
        $this->core = $core;
        $this->options = $options;
        $this->formatter = new Formatter($options, $utilities);
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
            $this->string .= $this->getCellContent($report, $colId);
            $this->string .= '</td>';
        }
        $this->string .= '</tr>';
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
                $post_title = get_the_title($report->getPostId());
                if (empty($post_title)) {
                    $post_title = '(kein Titel)';
                }
                $url = get_permalink($report->getPostId());
                $cellContent = '<a href="' . $url . '" rel="bookmark">' . $post_title . '</a>';
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
                $minutes = Data::getDauer($report->getPostId());
                $cellContent = $this->utilities->getDurationString($minutes, true);
                break;
            case 'vehicles':
                $makeFahrzeugLinks = $this->options->getBoolOption('einsatzvw_list_fahrzeuge_link');
                $cellContent = $this->formatter->getVehicles($report, $makeFahrzeugLinks, false);
                break;
            case 'alarmType':
                $cellContent = $this->formatter->getTypesOfAlerting($report);
                break;
            case 'additionalForces':
                $makeLinks = $this->options->getBoolOption('einsatzvw_list_ext_link');
                $cellContent = $this->formatter->getAdditionalForces($report, $makeLinks, false);
                break;
            case 'incidentType':
                $showHierarchy = $this->options->getBoolOption('einsatzvw_list_art_hierarchy');
                $cellContent = $this->formatter->getTypeOfIncident($report, false, false, $showHierarchy);
                break;
            case 'seqNum':
                $cellContent = $report->getSequentialNumber();
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
                'name' => 'Mannschaftsst&auml;rke'
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
                'name' => 'Weitere Kräfte'
            ),
            'incidentType' => array(
                'name' => 'Einsatzart'
            ),
            'seqNum' => array(
                'name' => 'Lfd.',
                'longName' => 'Laufende Nummer'
            )
        );
    }
}
