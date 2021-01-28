<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

/**
 * Defines how
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class Parameters
{
    /**
     * @var Column[]
     */
    private $columns;

    /**
     * Array mit Spalten-IDs, die mit einem Link zum Einsatzbericht versehen werden sollen
     *
     * @var string[]
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
     * @var int
     */
    private $splitType;

    /**
     * Initialize the parameters with default values
     */
    public function __construct()
    {
        $columnRepository = ColumnRepository::getInstance();

        $columnIdentifiers = explode(',', get_option('einsatzvw_list_columns', ''));
        $this->columns = $columnRepository->getColumnsByIdentifier($columnIdentifiers);
        if (empty($this->columns)) {
            $this->columns = $columnRepository->getDefaultColumns();
        }

        $this->reset();
    }

    /**
     * Reset the parameters to default values
     */
    public function reset()
    {
        $this->columnsLinkingReport = array('title');
        $this->compact = false;
        $this->linkEmptyReports = true;
        $this->linkAdditionalForces = (get_option('einsatzvw_list_ext_link') === '1');
        $this->linkVehicles = (get_option('einsatzvw_list_fahrzeuge_link') === '1');
        $this->showHeading = true;
        $this->splitType = SplitType::NONE;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return int
     */
    public function getColumnCount(): int
    {
        return count($this->columns);
    }

    /**
     * @return string[]
     */
    public function getColumnsLinkingReport(): array
    {
        return $this->columnsLinkingReport;
    }

    /**
     * @param string[] $columnsLinkingReport
     */
    public function setColumnsLinkingReport(array $columnsLinkingReport)
    {
        $this->columnsLinkingReport = ColumnRepository::sanitizeColumnsArrayNoDefault($columnsLinkingReport);
    }

    /**
     * @return bool
     */
    public function isSplitMonths(): bool
    {
        return $this->splitType === SplitType::MONTHLY && !$this->compact;
    }

    /**
     * @return bool
     */
    public function isSplitQuarterly(): bool
    {
        return $this->splitType === SplitType::QUARTERLY && !$this->compact;
    }

    /**
     * @param int $splitType
     */
    public function setSplitType(int $splitType)
    {
        $this->splitType = $splitType;
    }
}
