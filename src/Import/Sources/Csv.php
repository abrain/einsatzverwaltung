<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

/**
 * Importiert Einsatzberichte aus einer CSV-Datei
 */
class Csv extends AbstractSource
{
    /**
     * Csv constructor.
     */
    public function __construct()
    {
        $this->actionOrder = array(
            array(
                'slug' => 'selectfile',
                'name' => __('Dateiauswahl', 'einsatzverwaltung'),
                'button_text' => __('Datei ausw&auml;hlen', 'einsatzverwaltung')
            ),
            array(
                'slug' => 'analysis',
                'name' => __('Analyse', 'einsatzverwaltung'),
                'button_text' => __('Datei analysieren', 'einsatzverwaltung'),
                'args' => array('csv_file_id')
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
        // TODO: Implement checkPreconditions() method.
        if (empty($this->args['csv_file_id'])) {
            return false;
        }

        echo $this->args['csv_file_id'];
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
        // TODO: Implement getFields() method.
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
