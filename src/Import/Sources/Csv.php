<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Import\Step;
use abrain\Einsatzverwaltung\Utilities;
use Exception;
use function __;
use function in_array;

/**
 * Importiert Einsatzberichte aus einer CSV-Datei
 */
class Csv extends FileSource
{
    private $csvFilePath;
    private $dateFormats = array('d.m.Y', 'd.m.y', 'Y-m-d', 'm/d/Y', 'm/d/y');
    private $delimiter = ';';
    private $enclosure = '"';
    private $fileHasHeadlines = false;
    private $timeFormats = array('H:i', 'G:i', 'H:i:s', 'G:i:s');

    /**
     * Csv constructor.
     *
     */
    public function __construct()
    {
        $this->description = 'Importiert Einsatzberichte aus einer CSV-Datei.';
        $this->identifier = 'evw_csv';
        $this->mimeType = 'text/csv';
        $this->name = 'CSV';

        $this->steps[] = new Step(self::STEP_CHOOSEFILE, __('Choose a CSV file', 'einsatzverwaltung'), 'Datei ausw&auml;hlen');
        $this->steps[] = new Step(self::STEP_ANALYSIS, 'Analyse', 'Datei analysieren', ['file_id', 'has_headlines', 'delimiter']);
        $this->steps[] = new Step(self::STEP_IMPORT, 'Import', 'Import starten', ['file_id', 'has_headlines', 'delimiter']);
    }

    /**
     * @inheritDoc
     */
    public function checkPreconditions()
    {
        $this->fileHasHeadlines = (bool) Utilities::getArrayValueIfKey($this->args, 'has_headlines', false);

        $delimiter = Utilities::getArrayValueIfKey($this->args, 'delimiter', false);
        if (in_array($delimiter, array(';', ','))) {
            $this->delimiter = $delimiter;
        }

        $attachmentId = $this->args['file_id'];
        if (empty($attachmentId)) {
            throw new ImportCheckException('Keine Datei ausgew&auml;hlt');
        }

        if (!is_numeric($attachmentId)) {
            throw new ImportCheckException('Attachment ID ist keine Zahl');
        }

        $csvFilePath = get_attached_file($attachmentId);
        if (empty($csvFilePath)) {
            throw new ImportCheckException(sprintf('Konnte Attachment mit ID %d nicht finden', $attachmentId));
        }

        $this->csvFilePath = $csvFilePath;
        if (!file_exists($csvFilePath)) {
            throw new ImportCheckException('Datei existiert nicht');
        }

        try {
            $this->readFile(0);
        } catch (Exception $e) {
            throw new ImportCheckException($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function echoExtraFormFields(string $currentAction, Step $nextStep)
    {
        if ($currentAction === self::STEP_CHOOSEFILE) {
            echo '<h3>Aufbau der CSV-Datei</h3>';
            echo '<input id="has_headlines" name="has_headlines" type="checkbox" value="1" />';
            echo '<label for="has_headlines">Erste Zeile der Datei enth&auml;lt Spaltenbeschriftung</label>';
            echo '<p class="description">Setze diesen Haken, wenn die erste Zeile der CSV-Datei keine Daten von Eins&auml;tzen enth&auml;lt, sondern nur die &Uuml;berschriften der jeweiligen Spalten.</p>';
            echo '<br>Trennzeichen zwischen den Spalten:&nbsp;';
            echo '<label><input type="radio" name="delimiter" value=";" checked="checked"><code>;</code> Semikolon</label>';
            echo '&nbsp;<label><input type="radio" name="delimiter" value=","><code>,</code> Komma</label>';
            echo '<p class="description">Wenn du unsicher bist, kannst du die CSV-Datei mit einem Texteditor &ouml;ffnen und nachsehen.</p>';
            echo '<p class="description">Als Feldbegrenzerzeichen (umschlie&szlig;t ggf. den Inhalt einer Spalte) wird das Anf&uuml;hrungszeichen <code>&quot;</code> erwartet.</p>';
        } elseif (in_array($currentAction, [self::STEP_ANALYSIS, self::STEP_IMPORT])) {
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
        }

        parent::echoExtraFormFields($currentAction, $nextStep);
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        if (!array_key_exists('import_date_format', $this->args)) {
            return $this->dateFormats[0];
        }

        return $this->args['import_date_format'];
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
     * @return string
     */
    public function getTimeFormat()
    {
        if (!array_key_exists('import_time_format', $this->args)) {
            return $this->timeFormats[0];
        }

        return $this->args['import_time_format'];
    }

    /**
     * @param int|null $numLines Maximale Anzahl zu lesender Zeilen, oder null um alle Zeilen einzulesen
     * @param array $requestedFields
     *
     * @return array|bool
     * @throws Exception
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
            throw new Exception('Konnte Datei nicht öffnen');
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
