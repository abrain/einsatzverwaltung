<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Utilities;

/**
 * Importiert Einsatzberichte aus einer CSV-Datei
 */
class Csv extends AbstractSource
{
    /**
     * @var Utilities
     */
    protected $utilities;
    private $dateFormats = array('d.m.Y', 'd.m.y', 'Y-m-d', 'm/d/Y', 'm/d/y');
    private $timeFormats = array('H:i', 'G:i', 'H:i:s', 'G:i:s');
    private $csvFilePath;
    private $delimiter = ';';
    private $enclosure = '"';
    private $fileHasHeadlines = false;

    /**
     * Csv constructor.
     *
     * @param Utilities $utilities
     */
    public function __construct($utilities)
    {
        $this->utilities = $utilities;

        $this->actionOrder = array(
            array(
                'slug' => 'selectcsvfile',
                'name' => 'Dateiauswahl',
                'button_text' => 'Datei ausw&auml;hlen',
                'args' => array()
            ),
            array(
                'slug' => 'analysis',
                'name' => 'Analyse',
                'button_text' => 'Datei analysieren',
                'args' => array('csv_file_id', 'has_headlines', 'delimiter')
            ),
            array(
                'slug' => 'import',
                'name' => 'Import',
                'button_text' => 'Import starten',
                'args' => array('csv_file_id', 'has_headlines', 'delimiter')
            )
        );
    }

    /**
     * @return boolean True, wenn Voraussetzungen stimmen, ansonsten false
     */
    public function checkPreconditions()
    {
        $this->fileHasHeadlines = (bool) Utilities::getArrayValueIfKey($this->args, 'has_headlines', false);

        $delimiter = Utilities::getArrayValueIfKey($this->args, 'delimiter', false);
        if (in_array($delimiter, array(';', ','))) {
            $this->delimiter = $delimiter;
        }

        $attachmentId = $this->args['csv_file_id'];
        if (empty($attachmentId)) {
            $this->utilities->printError('Keine Datei ausgew&auml;hlt');
            return false;
        }

        if (!is_numeric($attachmentId)) {
            $this->utilities->printError('Attachment ID ist keine Zahl');
            return false;
        }

        $csvFilePath = get_attached_file($attachmentId);
        if (empty($csvFilePath)) {
            $this->utilities->printError(sprintf('Konnte Attachment mit ID %d nicht finden', $attachmentId));
            return false;
        }

        $this->csvFilePath = $csvFilePath;
        if (!file_exists($csvFilePath)) {
            $this->utilities->printError('Datei existiert nicht');
            return false;
        }

        $readFile = $this->readFile(0);
        if (false === $readFile) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function echoExtraFormFields($nextAction)
    {
        echo '<h3>Datums- und Zeitformat</h3>';
        $dateExample = strtotime('December 31st 5:29 am');

        echo '<div class="import-date-formats">';
        foreach ($this->dateFormats as $dateFormat) {
            echo '<label><input type="radio" name="import_date_format" value="'.$dateFormat.'"';
            checked($this->getDateFormat(), $dateFormat);
            echo ' />' . date($dateFormat, $dateExample) . '</label><br/>';
        }
        echo '</div>';

        echo '<div class="import-time-formats">';
        foreach ($this->timeFormats as $timeFormat) {
            echo '<label><input type="radio" name="import_time_format" value="'.$timeFormat.'"';
            checked($this->getTimeFormat(), $timeFormat);
            echo ' />' . date($timeFormat, $dateExample) . '</label><br/>';
        }
        echo '</div>';

        parent::echoExtraFormFields($nextAction);
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        if (!array_key_exists('import_date_format', $this->args)) {
            $fallbackDateFormat = $this->dateFormats[0];
            return $fallbackDateFormat;
        }

        return $this->args['import_date_format'];
    }

    /**
     * Gibt die Beschreibung der Importquelle zurück
     *
     * @return string Beschreibung der Importquelle
     */
    public function getDescription()
    {
        return 'Importiert Einsatzberichte aus einer CSV-Datei.';
    }

    /**
     * Gibt die Einsatzberichte der Importquelle zurück
     *
     * @param array $fields Felder der Importquelle, die abgefragt werden sollen. Ist dieser Parameter null, werden alle
     * Felder abgefragt.
     *
     * @return array|bool
     */
    public function getEntries($fields)
    {
        $lines = $this->readFile(null, $fields);

        if (empty($lines)) {
            return false;
        }

        if ($this->fileHasHeadlines) {
            return array_slice($lines, 1);
        }

        return $lines;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        if (!empty($this->cachedFields)) {
            return $this->cachedFields;
        }

        $fields = $this->readFile(1);

        if (empty($fields)) {
            return array();
        }

        // Gebe nummerierte Spalten zurück, wenn es keine Überschriften gibt
        if (!$this->fileHasHeadlines) {
            return array_map(function ($number) {
                return sprintf('Spalte %d', $number);
            }, range(1, count($fields[0])));
        }

        $this->cachedFields = $fields[0];

        // Gebe die Überschriften der Spalten zurück
        return $fields[0];
    }

    /**
     * Gibt den eindeutigen Bezeichner der Importquelle zurück
     *
     * @return string Eindeutiger Bezeichner der Importquelle
     */
    public function getIdentifier()
    {
        return 'evw_csv';
    }

    /**
     * Gibt den Namen der Importquelle zurück
     *
     * @return string Name der Importquelle
     */
    public function getName()
    {
        return 'CSV';
    }

    /**
     * @return string
     */
    public function getTimeFormat()
    {
        if (!array_key_exists('import_time_format', $this->args)) {
            $fallbackTimeFormat = $this->timeFormats[0];
            return $fallbackTimeFormat;
        }

        return $this->args['import_time_format'];
    }

    /**
     * @param int|null $numLines Maximale Anzahl zu lesender Zeilen, oder null um alle Zeilen einzulesen
     * @param array $requestedFields
     *
     * @return array|bool
     */
    private function readFile($numLines = null, $requestedFields = array())
    {
        $fieldMap = array();
        if (!empty($requestedFields)) {
            $fields = $this->getFields();
            foreach ($requestedFields as $requestedField) {
                $fieldMap[$requestedField] = array_search($requestedField, $fields);
            }
        }

        $handle = fopen($this->csvFilePath, 'r');
        if (empty($handle)) {
            $this->utilities->printError('Konnte Datei nicht öffnen');
            return false;
        }

        if ($numLines === 0) {
            fclose($handle);
            return array();
        }

        $lines = array();
        while (null === $numLines || count($lines) < $numLines) {
            $line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);

            // Problem beim Lesen oder Ende der Datei
            if (empty($line)) {
                break;
            }

            // Leere Zeile
            if (is_array($line) && $line[0] == null) {
                continue;
            }

            if (empty($requestedFields)) {
                $lines[] = $line;
                continue;
            }

            $filteredLine = array();
            foreach ($fieldMap as $fieldName => $index) {
                // Fehlende Felder in zu kurzen Zeilen werden als leer gewertet
                $filteredLine[$fieldName] = array_key_exists($index, $line) ? $line[$index] : '';
            }
            $lines[] = $filteredLine;
        }

        fclose($handle);
        return $lines;
    }
}
