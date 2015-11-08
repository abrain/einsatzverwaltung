<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Utilities;
use Exception;

/**
 * Importiert Einsatzberichte aus einer CSV-Datei
 */
class Csv extends AbstractSource
{
    private $csvFileHandle;
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
        // TODO: Implement checkForProblems() method.
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

        Utilities::printInfo('File path: ' . $csvFilePath);

        try {
            if (!file_exists($csvFilePath)) {
                throw new Exception('Datei existiert nicht');
            }

            $this->csvFileHandle = fopen($csvFilePath, 'r');
            if (empty($this->csvFileHandle)) {
                throw new Exception('Konnte Datei nicht öffnen');
            }
        } catch (Exception $e) {
            Utilities::printError($e->getMessage());
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
        // TODO: Implement getEntries() method.
    }

    /**
     * @return array
     */
    public function getFields()
    {
        // Zum Anfang der Datei gehen
        if (!rewind($this->csvFileHandle)) {
            Utilities::printError('Konnte nicht zum Anfang der Datei springen');
            return false;
        }

        do {
            $fields = fgetcsv($this->csvFileHandle, 0, $this->delimiter, $this->enclosure);

            // Problem beim Lesen
            if (empty($fields)) {
                return false;
            }
        } while (is_array($fields) && $fields[0] == null);

        // Gebe nummerierte Spalten zurück, wenn es keine Überschriften gibt
        if (!$this->fileHasHeadlines) {
            return array_map(function ($number) {
                return sprintf('Spalte %d', $number);
            }, range(1, count($fields)));
        }

        // Gebe die Überschriften der Spalten zurück
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
}
