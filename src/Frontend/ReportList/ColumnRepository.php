<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

/**
 * Contains all the available columns for the report list.
 *
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class ColumnRepository
{
    const DEFAULT_COLUMNS = 'number,date,time,title';

    /**
     * All available columns.
     *
     * @var Column[]
     */
    private $columns;

    /**
     * Only instance of this class (Singleton).
     *
     * @var ColumnRepository
     */
    private static $instance;

    /**
     * Constructor, not to be called directly. Use ColumnRepository::getInstance().
     */
    private function __construct()
    {
        $this->addColumn(new Column('number', __('Number', 'einsatzverwaltung'), __('Incident number', 'einsatzverwaltung'), true));
        $this->addColumn(new Column('date', __('Date', 'einsatzverwaltung'), '', true));
        $this->addColumn(new Column('time', __('Time', 'einsatzverwaltung'), '', true));
        $this->addColumn(new Column('datetime', __('Date', 'einsatzverwaltung'), __('Date and time', 'einsatzverwaltung'), true));
        $this->addColumn(new Column('title', 'Einsatzmeldung'));
        $this->addColumn(new Column('incidentCommander', 'Einsatzleiter'));
        $this->addColumn(new Column('location', __('Location', 'einsatzverwaltung')));
        $this->addColumn(new Column('workforce', 'Mannschaftsst&auml;rke'));
        $this->addColumn(new Column('duration', __('Duration', 'einsatzverwaltung'), '', true));
        $this->addColumn(new Column('vehicles', __('Vehicles', 'einsatzverwaltung')));
        $this->addColumn(new Column('alarmType', __('Alerting method', 'einsatzverwaltung')));
        $this->addColumn(new Column('additionalForces', 'Weitere Kr&auml;fte'));
        $this->addColumn(new Column('units', __('Units', 'einsatzverwaltung')));
        $this->addColumn(new Column('incidentType', __('Incident Category', 'einsatzverwaltung')));
        $this->addColumn(new Column('seqNum', 'Lfd.', 'Laufende Nummer'));
        $this->addColumn(new Column('annotationImages', '', 'Vermerk &quot;Bilder im Bericht&quot;'));
        $this->addColumn(new Column('annotationSpecial', '', 'Vermerk &quot;Besonderer Einsatz&quot;'));
    }

    /**
     * @param Column $column
     */
    private function addColumn(Column $column)
    {
        $this->columns[$column->getIdentifier()] = $column;
    }

    /**
     * @return Column[]
     */
    public function getAvailableColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param string $identifier
     *
     * @return Column|null
     */
    public function getColumn($identifier): ?Column
    {
        if (!$this->hasColumn($identifier)) {
            return null;
        }

        return $this->columns[$identifier];
    }

    /**
     * @param string[] $identifiers
     *
     * @return Column[]
     */
    public function getColumnsByIdentifier($identifiers): array
    {
        $columns = array();
        foreach ($identifiers as $identifier) {
            if ($this->hasColumn($identifier)) {
                $columns[] = $this->getColumn($identifier);
            }
        }
        return $columns;
    }

    /**
     * @return Column[]
     */
    public function getDefaultColumns(): array
    {
        return $this->getColumnsByIdentifier(explode(',', self::DEFAULT_COLUMNS));
    }

    /**
     * @param Column[] $columns
     *
     * @return string
     */
    public function getIdentifiers($columns): string
    {
        $columnIds = array_map(function (Column $column) {
            return $column->getIdentifier();
        }, $columns);

        return join(',', $columnIds);
    }

    /**
     * @return ColumnRepository
     */
    public static function getInstance(): ColumnRepository
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasColumn($identifier): bool
    {
        return array_key_exists($identifier, $this->columns);
    }

    /**
     * Stellt sicher, dass nur gültige Spalten-Ids gespeichert werden.
     *
     * @param string $input Kommaseparierte Spalten-Ids
     *
     * @return string Der Eingabestring ohne ungültige Spalten-Ids, bei Problemen werden die Standardspalten
     * zurückgegeben
     */
    public static function sanitizeColumns($input): string
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
     * @param string[] $inputArray
     *
     * @return string[]
     */
    public static function sanitizeColumnsArray($inputArray): array
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
     * @param string[] $inputArray
     *
     * @return string[]
     */
    public static function sanitizeColumnsArrayNoDefault($inputArray): array
    {
        $columnRepository = self::getInstance();

        $validColumnIds = array();
        foreach ($inputArray as $colId) {
            $colId = trim($colId);
            if ($columnRepository->hasColumn($colId)) {
                $validColumnIds[] = $colId;
            }
        }

        return $validColumnIds;
    }
}
