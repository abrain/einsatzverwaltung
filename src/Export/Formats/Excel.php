<?php
namespace abrain\Einsatzverwaltung\Export\Formats;

/**
 * Exportiert Einsatzberichte in eine für Excel formatierte CSV-Datei.
 */
class Excel extends Csv
{
    /**
     * @inheritDoc
     */
    public function getTitle()
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
        $this->escape_char = '\\';
        $this->header = (boolean)@$options['columns'];
    }
}
