<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Utilities;

/**
 * Importiert Einsatzberichte aus einer CSV-Datei
 */
class Csv extends AbstractSource
{
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
                'name' => __('Dateiauswahl', 'einsatzverwaltung'),
                'button_text' => __('Datei ausw&auml;hlen', 'einsatzverwaltung')
            ),
            array(
                'slug' => 'analysis',
                'name' => __('Analyse', 'einsatzverwaltung'),
                'button_text' => __('Datei analysieren', 'einsatzverwaltung'),
                'args' => array('csv_file_id', 'has_headlines', 'delimiter')
            ),
            array(
                'slug' => 'import',
                'name' => __('Import', 'einsatzverwaltung'),
                'button_text' => __('Import starten', 'einsatzverwaltung')
            )
        );
    }

    /**
     * @param $felder
     * @param bool|false $quiet
     */
    public function checkForProblems($felder, $quiet = false)
    {
        // Probleme? Welche Probleme?
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
            Utilities::printError('Keine Attachment ID angegeben');
            return false;
        }

        if (!is_numeric($attachmentId)) {
            Utilities::printError('Attachment ID ist keine Zahl');
            return false;
        }

        $csvFilePath = get_attached_file($attachmentId);
        if (empty($csvFilePath)) {
            Utilities::printError(sprintf('Konnte Attachment mit ID %d nicht finden', $attachmentId));
            return false;
        }

        $this->csvFilePath = $csvFilePath;
        Utilities::printInfo('File path: ' . $csvFilePath);

        if (!file_exists($csvFilePath)) {
            Utilities::printError('Datei existiert nicht');
            return false;
        }

        $readFile = $this->readFile(0);
        if (false === $readFile) {
            return false;
        }

        return true;
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
     * @return array
     */
    public function getEntries($fields)
    {
        $lines = $this->readFile();

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
        $fields = $this->readFile(1);

        // Gebe nummerierte Spalten zurück, wenn es keine Überschriften gibt
        if (!$this->fileHasHeadlines) {
            return array_map(function ($number) {
                return sprintf('Spalte %d', $number);
            }, range(1, count($fields[0])));
        }

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
     * @param int|null $numLines Maximale Anzahl zu lesender Zeilen, oder null um alle Zeilen einzulesen
     *
     * @return array|bool
     */
    private function readFile($numLines = null)
    {
        $handle = fopen($this->csvFilePath, 'r');
        if (empty($handle)) {
            Utilities::printError('Konnte Datei nicht öffnen');
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

            $lines[] = $line;
        }

        fclose($handle);
        return $lines;
    }
}
