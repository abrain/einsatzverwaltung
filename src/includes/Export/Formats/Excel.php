<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

/**
 * Exportiert Einsatzberichte in eine für Excel formatierte CSV-Datei.
 *
 * Die escapeChar-Funktion wurde erst einmal auskommentiert, da diese PHP >=5.5.4
 * voraussetzt, das Plugin jedoch auch unter PHP <5.4 laufen soll.
 */
class Excel extends Csv
{
    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'CSV für Microsoft Excel';
    }

    /**
     * @inheritDoc
     */
    public function renderOptions()
    {
        ?>
        <li>
            <input type="checkbox" name="export_options[excel][columns]" id="excel_columns" value="1" checked="checked">
            <label for="excel_columns">Spaltennamen in die erste Zeile setzen</label>
        </li>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options)
    {
        $this->delimiter = ';';
        $this->enclosure = '"';
        $this->headers = (boolean)@$options['columns'];
    }
}
