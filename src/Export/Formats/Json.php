<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

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
    public function getTitle(): string
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
    public function getFilename(): string
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

        $keys = $this->getColumnNames();

        $query = $this->getQuery();
        while ($query->have_posts()) {
            $post = $query->next_post();

            $values = $this->getValuesForReport($post);
            $data = array_combine($keys, $values);

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

    /**
     * @param bool $bool
     *
     * @return mixed
     */
    protected function getBooleanRepresentation(bool $bool): bool
    {
        return $bool;
    }

    /**
     * @param array $array
     *
     * @return mixed
     */
    protected function getArrayRepresentation(array $array): array
    {
        return $array;
    }
}
