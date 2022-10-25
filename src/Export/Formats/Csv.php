<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

/**
 * Exportiert Einsatzberichte in eine CSV-Datei.
 *
 * Die escapeChar-Funktion wurde erst einmal auskommentiert, da diese PHP >=5.5.4
 * voraussetzt, das Plugin jedoch auch unter PHP <5.4 laufen soll.
 */
class Csv extends AbstractFormat
{
    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var string
     */
    protected $enclosure;

    /**
     * @var string
     */
    // protected $escapeChar;

    /**
     * @var boolean
     */
    protected $headers;

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'CSV';
    }

    /**
     * @inheritDoc
     */
    public function renderOptions()
    {
        ?>
        <li>
            <label>
                <span class="label-responsive">Spalten getrennt mit:</span>
                <input name="export_options[csv][delimiter]" type="text" value="," required="required" maxlength="1">
            </label>
        </li>
        <li>
            <label>
                <span class="label-responsive">Spalten eingeschlossen von:</span>
                <input name="export_options[csv][enclosure]" type="text" value="&quot;" required="required" maxlength="1">
            </label>
        </li>
        <!--<li>
            <label>
                <span class="label-responsive">Spalten escaped mit:</span>
                <input name="export_options[csv][escapeChar]" type="text" value=";" required="required">
            </label>
        </li>-->
        <li>
            <input type="checkbox" name="export_options[csv][headers]" id="csv_headers" value="1" checked="checked">
            <label for="csv_headers">Spaltennamen in die erste Zeile setzen</label>
        </li>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options)
    {
        $this->delimiter = @$options['delimiter'];
        if (empty($this->delimiter)) {
            $this->delimiter = ',';
        }
        $this->enclosure = @$options['enclosure'];
        if (empty($this->enclosure)) {
            $this->enclosure = '"';
        }
        // $this->escapeChar = @$options['escapeChar'];
        // if (empty($this->escapeChar)) {
        //     $this->escapeChar = '\\';
        // }
        $this->headers = (boolean)@$options['headers'];
    }

    /**
     * @inheritDoc
     */
    public function getFilename(): string
    {
        return 'Einsatzberichte.csv';
    }

    /**
     * @inheritDoc
     */
    public function export()
    {
        $handle = fopen('php://output', 'w');
        // füge BOM hinzu, damit UTF-8-formatierte Inhalte in Excel funktionieren.
        // siehe: http://php.net/manual/de/function.fputcsv.php#118252
        fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // füge ggf. Spaltennamen als die erste Zeile ein
        if ($this->headers) {
            $data = $this->getColumnNames();
            fputcsv($handle, $data, $this->delimiter, $this->enclosure/*, $this->escapeChar*/);
        }

        $query = $this->getQuery();
        while ($query->have_posts()) {
            $post = $query->next_post();

            $data = $this->getValuesForReport($post);
            fputcsv($handle, $data, $this->delimiter, $this->enclosure/*, $this->escapeChar*/);
        }

        fclose($handle);
    }

    /**
     * @param bool $bool
     *
     * @return mixed
     */
    protected function getBooleanRepresentation(bool $bool): string
    {
        return ($bool === true ? 'Ja' : 'Nein');
    }

    /**
     * @param array $array
     *
     * @return string
     */
    protected function getArrayRepresentation(array $array)
    {
        return join(',', $array);
    }
}
