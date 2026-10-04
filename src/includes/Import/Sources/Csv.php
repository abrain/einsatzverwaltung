<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Util\CsvReader;
use abrain\Einsatzverwaltung\Utilities;

/**
 * Importiert Einsatzberichte aus einer CSV-Datei
 */
class Csv extends AbstractSource
{
    private $dateFormats = array('d.m.Y', 'd.m.y', 'Y-m-d', 'm/d/Y', 'm/d/y');
    private $timeFormats = array('H:i', 'G:i', 'H:i:s', 'G:i:s');
    private $csvFilePath;
    private $delimiter = ';';
    private $enclosure = '"';
    private $fileHasHeadlines = false;

    /**
     * Csv constructor.
     */
    public function __construct()
    {
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
     * @inheritDoc
     */
    public function checkPreconditions(): void
    {
        $this->fileHasHeadlines = (bool) Utilities::getArrayValueIfKey($this->args, 'has_headlines', false);

        $delimiter = Utilities::getArrayValueIfKey($this->args, 'delimiter', false);
        if (in_array($delimiter, array(';', ','))) {
            $this->delimiter = $delimiter;
        } else {
            throw new ImportCheckException(__('Invalid CSV delimiter given', 'einsatzverwaltung'));
        }

        $attachmentId = $this->args['csv_file_id'];
        if (empty($attachmentId)) {
            throw new ImportCheckException(__('No file selected', 'einsatzverwaltung'));
        }

        if (!is_numeric($attachmentId)) {
            throw new ImportCheckException(__('The attachment ID is not a number', 'einsatzverwaltung'));
        }

        $csvFilePath = get_attached_file($attachmentId);
        if (empty($csvFilePath)) {
            // translators: 1: the attachment ID
            throw new ImportCheckException(sprintf(__('Could not find attachment with ID %d.', 'einsatzverwaltung'), $attachmentId));
        }

        $this->csvFilePath = $csvFilePath;
        if (!file_exists($csvFilePath)) {
            throw new ImportCheckException(__('File does not exist', 'einsatzverwaltung'));
        }

        $csvReader = new CsvReader($csvFilePath, $this->delimiter, $this->enclosure);
        try {
            $csvReader->getLines(1);
        } catch (FileReadException $e) {
            throw new ImportCheckException($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function echoExtraFormFields(array $nextAction)
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
     * @inheritDoc
     */
    public function getDateFormat(): string
    {
        if (!array_key_exists('import_date_format', $this->args)) {
            $fallbackDateFormat = $this->dateFormats[0];
            return $fallbackDateFormat;
        }

        return $this->args['import_date_format'];
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Importiert Einsatzberichte aus einer CSV-Datei.';
    }

    /**
     * @inheritDoc
     */
    public function getEntries(array $requestedFields = []): array
    {
        $fields = $this->getFields();
        $fieldMap = array();
        $requestedColumnIndices = array();
        foreach ($requestedFields as $requestedField) {
            $fieldIndex = array_search($requestedField, $fields);
            if ($fieldIndex === false) {
                // translators: 1: field name
                throw new ImportCheckException(sprintf(__('The requested field %1$s is not available in the source.', 'einsatzverwaltung'), $requestedField));
            }
            $fieldMap[$fieldIndex] = $requestedField;
            $requestedColumnIndices[] = $fieldIndex;
        }

        $csvReader = new CsvReader($this->csvFilePath, $this->delimiter, $this->enclosure);
        try {
            $lines = $csvReader->getLines(0, $requestedColumnIndices, 0, $fieldMap);
        } catch (FileReadException $e) {
            throw new ImportCheckException($e->getMessage());
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
            throw new ImportCheckException($e->getMessage());
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
    public function getIdentifier(): string
    {
        return 'evw_csv';
    }

    /**
     * Gibt den Namen der Importquelle zurück
     *
     * @return string Name der Importquelle
     */
    public function getName(): string
    {
        return 'CSV';
    }

    /**
     * @return string
     */
    public function getTimeFormat(): string
    {
        if (!array_key_exists('import_time_format', $this->args)) {
            $fallbackTimeFormat = $this->timeFormats[0];
            return $fallbackTimeFormat;
        }

        return $this->args['import_time_format'];
    }
}
