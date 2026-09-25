<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use abrain\Einsatzverwaltung\Util\CsvReader;
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

        try {
            $csvReader = new CsvReader($csvFilePath, $this->delimiter, $this->enclosure);
            $csvReader->getLines(1);
        } catch (FileReadException $e) {
            $this->utilities->printError($e->getMessage());
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
     * @inheritDoc
     */
    public function getEntries(array $requestedFields)
    {
        $fields = $this->getFields();
        $fieldMap = array();
        $requestedColumnIndices = array();
        foreach ($requestedFields as $requestedField) {
            $fieldIndex = array_search($requestedField, $fields);
            if ($fieldIndex === false) {
                // translators: 1: field name
                $this->utilities->printError(sprintf(__('The requested field %1$s is not available in the source.', 'einsatzverwaltung'), $requestedField));
                return false;
            }
            $fieldMap[$fieldIndex] = $requestedField;
            $requestedColumnIndices[] = $fieldIndex;
        }

        $csvReader = new CsvReader($this->csvFilePath, $this->delimiter, $this->enclosure);
        try {
            $lines = $csvReader->getLines(0, $requestedColumnIndices, 0, $fieldMap);
        } catch (FileReadException $e) {
            $this->utilities->printError($e->getMessage());
            return false;
        }

        if (empty($lines)) {
            return false;
        }

        if ($this->fileHasHeadlines) {
            return array_slice($lines, 1);
        }

        return $lines;
    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        if (!empty($this->cachedFields)) {
            return $this->cachedFields;
        }

        $csvReader = new CsvReader($this->csvFilePath, $this->delimiter, $this->enclosure);
        try {
            $lines = $csvReader->getLines(1);
            $fields = $lines[0];
        } catch (FileReadException $e) {
            $this->utilities->printError($e->getMessage());
        }

        if (empty($fields)) {
            return array();
        }

        // If the first line does not contain the column names, return names like Column 1, Column 2, ...
        if (!$this->fileHasHeadlines) {
            return array_map(function ($number) {
                // translators: 1: column number
                return sprintf(__('Column %d', 'einsatzverwaltung'), $number);
            }, range(1, count($fields)));
        }

        $this->cachedFields = $fields;

        return $fields;
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
}
