<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Data;

/**
 * Exportiert Einsatzberichte in eine JSON-Datei.
 *
 * Die prettyPrint-Funktion wurde erst einmal auskommentiert, da diese PHP >=5.4
 * voraussetzt, das Plugin jedoch auch unter PHP <5.4 laufen soll. 
 */
class Json extends AbstractFormat
{
    /**
     * @var boolean
     */
    // protected $prettyPrint;

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'JSON';
    }

    /**
     * @inheritDoc
     */
    public function renderOptions()
    {
 /*       ?>
        <li>
            <input type="checkbox" name="export_options[json][prettyPrint]" id="json_pretty_print" value="1">
            <label for="json_pretty_print">
                Mit Whitespace formatiertes JSON ausgeben (Menschenlesbares Format verwenden)
            </label>
        </li>
<?php */
    }

    /**
     * @inheritDoc
     */
    public function getFilename()
    {
        return 'Einsatzberichte.json';
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options)
    {
        // $this->prettyPrint = (boolean)@$options['prettyPrint'];
    }

    /**
     * Gibt den die gewünschten Einsatzberichte im JSON-Format aus.
     * Um den Speicherverbrauch dieser Methode so gering wie möglich zu halten,
     * wurde davon abgesehen, erst alle Einsatzberichte aus der Datenbank in ein
     * Array zwischenzuspeichern um dann in einen Rutsch der Methode json_encode()
     * übergeben zu können.
     * Stattdessen wird nur ein Einsatzbericht zur Zeit abgerufen und dann einzleln
     * via json_encode entsprechend formatiert und dann ausgegeben.
     */
    public function export()
    {
        $options = 0;
        // // verwende ggf. menschenlesbares Format für die Ausgabe
        // if ($this->prettyPrint) {
        //     $options = JSON_PRETTY_PRINT;
        // }

        echo '[';
        // if ($this->prettyPrint) {
        //     echo "\n";
        // }

        $query = $this->getQuery();
        while ($query->have_posts()) {
            $post = $query->next_post();

            $report = new IncidentReport($post);

	        $duration = Data::getDauer($report);
	        // $duration soll stets eine Zahl sein
	        if (empty($duration)) {
		        $duration = 0;
	        }

	        $typeOfIncident = $report->getTypeOfIncident()->name;
	        // $typeOfIncident soll stets ein String sein
	        if (empty($typeOfIncident)) {
		        $typeOfIncident = '';
	        }
    
            $data = array(
               'Einsatznummer' => $report->getSequentialNumber(),
               'Alarmierungsart' => implode(',', array_map(function($e) { return $e->name; }, $report->getTypesOfAlerting())),
               'Alarmzeit' => $report->getTimeOfAlerting()->format('Y-m-d H:i'),
               'Einsatzende' => $report->getTimeOfEnding(),
               'Dauer (Minuten)' => $duration,
               'Einsatzort' => $report->getLocation(),
               'Einsatzart' => $typeOfIncident,
               'Fahrzeuge' => implode(',', array_map(function($e) { return $e->name; }, $report->getVehicles())),
               'Externe Einsatzmittel' => implode(',', array_map(function($e) { return $e->name; }, $report->getAdditionalForces())),
               'Mannschaftsstärke' => $report->getWorkforce(),
               'Einsatzleiter' => $report->getIncidentCommander(),
               'Berichtstitel' => $post->post_title,
               'Berichtstext' => $post->post_content,
               'Besonderer Einsatz' => $report->isSpecial(),
               'Fehlalarm' => $report->isFalseAlarm(),
            );

            $output = json_encode($data, $options);

            // solange es sich nicht um den letzten Einsatzbericht handelt, müssen
            // wir die Einsatzberichte in JSON über ein Komma (,) voneinander
            // trennen
            if (($query->current_post + 1) != $query->post_count) {
                $output .= ',';
            }

            // // rücke die JSON-Ausgabe des Einsatzberichtes ein, falls die Ausgabe
            // // menschenlesbar formatiert werden soll
            // if ($this->prettyPrint) {
            //     $output = preg_replace("/^(.*)$/m", "    $1", $output) . "\n";
            // }

            echo $output;
        }

        echo ']';
    }
}
