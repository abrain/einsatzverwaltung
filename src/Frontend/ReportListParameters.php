<?php
namespace abrain\Einsatzverwaltung\Frontend;

/**
 * Defines how
 * @package abrain\Einsatzverwaltung\Frontend
 */
class ReportListParameters
{
    const DEFAULT_COLUMNS = 'number,date,time,title';

    /**
     * @var array
     */
    private $columns;

    /**
     * Array mit Spalten-IDs, die mit einem Link zum Einsatzbericht versehen werden sollen
     *
     * @var array
     */
    private $columnsLinkingReport;

    /**
     * Gibt an, ob die Tabelle in kompakter Form, also ohne Trennung zwischen den Jahren angezeigt werden soll
     *
     * @var bool
     */
    public $compact;

    /**
     * Gibt an, ob ein Link für Einsatzberichte ohne Beitragstext erzeugt werden soll
     *
     * @var bool
     */
    public $linkEmptyReports;

    /**
     * Gibt an, ob die zusätzlichen Kräfte mit einem Link zu der ggf. gesetzten Adresse versehen werden sollen
     *
     * @var bool
     */
    public $linkAdditionalForces;

    /**
     * Gibt an, ob die Fahrzeuge mit einem Link zu der ggf. gesetzten Fahrzeugseite versehen werden sollen
     *
     * @var bool
     */
    public $linkVehicles;

    /**
     * Gibt an, ob oberhalb einer Tabelle die Überschrift mit der Jahreszahl angezeigt werden soll
     *
     * @var bool
     */
    public $showHeading;

    /**
     * Gibt an, ob nach jedem Monat eine Trennung eingefügt werden soll
     *
     * @var bool
     */
    private $splitMonths;

    /**
     * Initialize the parameters with default values
     */
    public function __construct()
    {
        $enabledColumns = self::sanitizeColumns(get_option('einsatzvw_list_columns', ''));
        $this->columns = explode(',', $enabledColumns);
        $this->columnsLinkingReport = array('title');
        $this->compact = false;
        $this->linkEmptyReports = true;
        $this->linkAdditionalForces = (get_option('einsatzvw_list_ext_link') === '1');
        $this->linkVehicles = (get_option('einsatzvw_list_fahrzeuge_link') === '1');
        $this->showHeading = true;
        $this->splitMonths = false;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = self::sanitizeColumnsArray($columns);
    }

    /**
     * @return array
     */
    public function getColumnsLinkingReport()
    {
        return $this->columnsLinkingReport;
    }

    /**
     * @param array $columnsLinkingReport
     */
    public function setColumnsLinkingReport($columnsLinkingReport)
    {
        $this->columnsLinkingReport = self::sanitizeColumnsArrayNoDefault($columnsLinkingReport);
    }

    /**
     * @return bool
     */
    public function isSplitMonths()
    {
        return $this->splitMonths && !$this->compact;
    }

    /**
     * @param bool $splitMonths
     */
    public function setSplitMonths($splitMonths)
    {
        $this->splitMonths = $splitMonths;
    }

    /**
     * Stellt sicher, dass nur gültige Spalten-Ids gespeichert werden.
     *
     * @param string $input Kommaseparierte Spalten-Ids
     *
     * @return string Der Eingabestring ohne ungültige Spalten-Ids, bei Problemen werden die Standardspalten
     * zurückgegeben
     */
    public static function sanitizeColumns($input)
    {
        if (empty($input)) {
            return self::DEFAULT_COLUMNS;
        }

        $inputArray = explode(',', $input);
        $validColumnIds = self::sanitizeColumnsArray($inputArray);

        return implode(',', $validColumnIds);
    }

    /**
     * Bereinigt ein Array von Spalten-Ids, sodass nur gültige Ids darin verbleiben. Verbleiben keine Ids, werden die
     * Standard-Spalten zurückgegeben
     *
     * @param array $inputArray
     *
     * @return array
     */
    public static function sanitizeColumnsArray($inputArray)
    {
        $validColumnIds = self::sanitizeColumnsArrayNoDefault($inputArray);

        if (empty($validColumnIds)) {
            $validColumnIds = explode(',', self::DEFAULT_COLUMNS);
        }

        return $validColumnIds;
    }

    /**
     * Bereinigt ein Array von Spalten-Ids, sodass nur gültige Ids darin verbleiben
     *
     * @param array $inputArray
     *
     * @return array
     */
    public static function sanitizeColumnsArrayNoDefault($inputArray)
    {
        $columns = ReportList::getListColumns();
        $columnIds = array_keys($columns);

        $validColumnIds = array();
        foreach ($inputArray as $colId) {
            $colId = trim($colId);
            if (in_array($colId, $columnIds)) {
                $validColumnIds[] = $colId;
            }
        }

        return $validColumnIds;
    }
}
