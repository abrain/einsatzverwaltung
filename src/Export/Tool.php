<?php
namespace abrain\Einsatzverwaltung\Export;

//use abrain\Einsatzverwaltung\Core;
//use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
//use abrain\Einsatzverwaltung\Import\Sources\Csv;
//use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;
//use abrain\Einsatzverwaltung\Model\IncidentReport;
//use abrain\Einsatzverwaltung\Options;
//use abrain\Einsatzverwaltung\Utilities;

/**
 * Werkzeug für den Export von Einsatzberichten in verschiedenen Formaten
 */
class Tool
{
    const EVW_TOOL_EXPORT_SLUG = 'einsatzvw-tool-export';

    private $sources = array();

    /**
     * @var AbstractSource
     */
    private $currentSource;

    /**
     * @var array
     */
    private $currentAction;

    /**
     * @var array
     */
    private $nextAction;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Core
     */
    private $core;

    /**
     * @var Options
     */
    private $options;

    /**
     * Konstruktor
     *
     * @param Core $core
     * @param Utilities $utilities
     * @param Options $options
     */
    public function __construct($core, $utilities, $options)
    {
        $this->core = $core;
        $this->utilities = $utilities;
        $this->options = $options;
        $this->addHooks();
        $this->loadSources();
    }

    private function addHooks()
    {
        add_action('admin_menu', array($this, 'addToolToMenu'));
    }

    /**
     * Fügt das Werkzeug zum Menü hinzu
     */
    public function addToolToMenu()
    {
        add_management_page(
            'Einsatzberichte exportieren',
            'Einsatzberichte exportieren',
            'manage_options',
            self::EVW_TOOL_EXPORT_SLUG,
            array($this, 'renderToolPage')
        );
    }

    // /**
    //  * @param AbstractSource $source
    //  * @param string $action
    //  */
    // private function checkNonce($source, $action)
    // {
    //     check_admin_referer($this->getNonceAction($source, $action));
    // }

    // /**
    //  * @param AbstractSource $source
    //  * @param string $action
    //  * @return string
    //  */
    // private function getNonceAction($source, $action)
    // {
    //     return $source->getIdentifier() . '_' . $action;
    // }

    private function loadSources()
    {
        // require_once dirname(__FILE__) . '/Sources/AbstractSource.php';
        // require_once dirname(__FILE__) . '/Sources/WpEinsatz.php';
        // $wpEinsatz = new WpEinsatz($this->utilities);
        // $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;

        // require_once dirname(__FILE__) . '/Sources/Csv.php';
        // $csv = new Csv($this->utilities);
        // $this->sources[$csv->getIdentifier()] = $csv;
    }

    /**
     * Generiert den Inhalt der Werkzeugseite
     */
    public function renderToolPage()
    {
        echo '<div class="wrap">';
        echo '<h1>' . 'Einsatzberichte exportieren' . '</h1>';
        echo '<p>Dieses Werkzeug exportiert Einsatzberichte in verschiedenen Formaten.</p>';
?>
<form method="get" id="export-form">
    <h2>Wähle, welche Einsatzberichte du exportieren möchtest</h2>
    <fieldset>
        <legend class="screen-reader-text">Wähle, welche Einsatzberichte du exportieren möchtest</legend>
        <ul id="export-filters">
            <li>
                <fieldset>
                    <legend class="screen-reader-text">Zeitraum:</legend>
                    <label for="post-start-date" class="label-responsive">Alarmzeit von:</label>
                    <select name="post_start_date" id="post-start-date">
                        <option value="0">— Auswählen —</option>
                        <option value="2017-05">Mai 2017</option>
                    </select>
                    <label for="post-end-date" class="label-responsive">bis:</label>
                    <select name="post_end_date" id="post-end-date">
                        <option value="0">— Auswählen —</option>
                        <option value="2017-06">Juni 2017</option>
                    </select>
                </fieldset>
            </li>
        </ul>
    </fieldset>

    <script type="text/javascript">
        jQuery(document).ready(function($){
            var form = $('#export-form'),
                options = form.find('.export-options');
            options.hide();
            form.find('input[name="format"]').change(function() {
                options.slideUp('fast');
                switch ( $(this).val() ) {
                    case 'csv': $('#csv-options').slideDown(); break;
                    case 'excel': $('#excel-options').slideDown(); break;
                    case 'json': $('#json-options').slideDown(); break;
                }
            });
        });
    </script>
    <h2>Wähle, in welches Format du exportieren möchtest</h2>
    <fieldset>
        <legend class="screen-reader-text">Wähle, in welches Format du exportieren möchtest</legend>
        <input type="hidden" name="download" value="true">
        <p>
            <label><input type="radio" name="format" value="csv"> CSV</label>
        </p>
        <ul id="csv-options" class="export-options export-filters">
            <li>
                <label>
                    <span class="label-responsive">Spalten getrennt mit:</span>
                    <input name="csv_separator" type="text" value="," required="required">
                </label>
            </li>
            <li>
                <label>
                    <span class="label-responsive">Spalten eingeschlossen von:</span>
                    <input name="csv_enclosed" type="text" value=";" required="required">
                </label>
            </li>
            <li>
                <label>
                    <span class="label-responsive">Spalten escaped mit:</span>
                    <input name="csv_escaped" type="text" value=";" required="required">
                </label>
            </li>
            <li>
                <input type="checkbox" name="csv_columns" id="csv_columns" value="1" checked="checked">
                <label for="csv_columns">Spaltennamen in die erste Zeile setzen</label>
            </li>
        </ul>
        
        <p>
            <label><input type="radio" name="format" value="excel"> CSV für Microsoft Excel</label>
        </p>
        <ul id="excel-options" class="export-options export-filters">
            <li>
                <input type="checkbox" name="csv_columns" id="csv_columns" value="1" checked="checked">
                <label for="csv_columns">Spaltennamen in die erste Zeile setzen</label>
            </li>
        </ul>

        <p>
            <label><input type="radio" name="format" value="json"> JSON</label>
        </p>
        <ul id="json-options" class="export-options export-filters">
            <li>
                <input type="checkbox" name="csv_columns" id="csv_columns" value="1">
                <label for="json_pretty_print">Mit Whitespace formatiertes JSON ausgeben (Menschenlesbares Format verwenden)</label>
            </li>
        </ul>
    </fieldset>

    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Export-Datei herunterladen">
    </p>
</form>

<?php
        echo '</div>';
    }

    // private function analysisPage()
    // {
    //     if (!$this->currentSource->checkPreconditions()) {
    //         return;
    //     }

    //     $felder = $this->currentSource->getFields();
    //     if (empty($felder)) {
    //         $this->utilities->printError('Es wurden keine Felder gefunden');
    //         return;
    //     }
    //     $this->utilities->printSuccess('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));

    //     // Auf Pflichtfelder prüfen
    //     $mandatoryFieldsOk = true;
    //     foreach (array_keys($this->currentSource->getAutoMatchFields()) as $autoMatchField) {
    //         if (!in_array($autoMatchField, $felder)) {
    //             $this->utilities->printError(
    //                 sprintf('Das automatisch zu importierende Feld %s konnte nicht gefunden werden!', $autoMatchField)
    //             );
    //             $mandatoryFieldsOk = false;
    //         }
    //     }
    //     if (!$mandatoryFieldsOk) {
    //         return;
    //     }

    //     // Einsätze zählen
    //     $entries = $this->currentSource->getEntries(null);
    //     if (false === $entries) {
    //         return;
    //     }
    //     if (empty($entries)) {
    //         $this->utilities->printWarning('Es wurden keine Eins&auml;tze gefunden.');
    //         return;
    //     }
    //     $this->utilities->printSuccess(sprintf("Es wurden %s Eins&auml;tze gefunden", count($entries)));

    //     if ('evw_wpe' == $this->currentSource->getIdentifier()) {
    //         $this->printDataNotice();
    //     }

    //     // Felder matchen
    //     echo "<h3>Felder zuordnen</h3>";
    //     if (false === $this->nextAction) {
    //         $this->utilities->printError('Keine Nachfolgeaktion gefunden!');
    //         return;
    //     }

    //     $this->helper->renderMatchForm($this->currentSource, array(
    //         'nonce_action' => $this->getNonceAction($this->currentSource, $this->nextAction['slug']),
    //         'action_value' => $this->currentSource->getActionAttribute($this->nextAction['slug']),
    //         'next_action' => $this->nextAction
    //     ));
    // }

    // private function importPage()
    // {
    //     if (!$this->currentSource->checkPreconditions()) {
    //         return;
    //     }

    //     $sourceFields = $this->currentSource->getFields();
    //     if (empty($sourceFields)) {
    //         $this->utilities->printError('Es wurden keine Felder gefunden');
    //         return;
    //     }

    //     // Mapping einlesen
    //     $mapping = $this->currentSource->getMapping($sourceFields, IncidentReport::getFields());

    //     // Prüfen, ob mehrere Felder das gleiche Zielfeld haben
    //     if (!$this->helper->validateMapping($mapping, $this->currentSource)) {
    //         // Und gleich nochmal...
    //         $this->nextAction = $this->currentAction;

    //         $this->helper->renderMatchForm($this->currentSource, array(
    //             'mapping' => $mapping,
    //             'nonce_action' => $this->getNonceAction($this->currentSource, $this->nextAction['slug']),
    //             'action_value' => $this->currentSource->getActionAttribute($this->nextAction['slug']),
    //             'next_action' => $this->nextAction
    //         ));
    //         return;
    //     }

    //     // Import starten
    //     echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
    //     $this->helper->import($this->currentSource, $mapping);
    // }

    // private function printDataNotice()
    // {
    //     // Hinweise ausgeben
    //     echo '<h3>Hinweise zu den erwarteten Daten</h3>';
    //     echo '<p>Die Felder <strong>Berichtstext, Berichtstitel, Einsatzleiter, Einsatzort</strong> und <strong>Mannschaftsst&auml;rke</strong> sind Freitextfelder.</p>';
    //     echo '<p>F&uuml;r die Felder <strong>Alarmierungsart, Einsatzart, Externe Einsatzmittel</strong> und <strong>Fahrzeuge</strong> wird eine kommagetrennte Liste erwartet.<br>Bisher unbekannte Eintr&auml;ge werden automatisch angelegt, die Einsatzart sollte nur ein einzelner Wert sein.</p>';
    //     if ('evw_wpe' == $this->currentSource->getIdentifier()) {
    //         echo '<p>Das Feld <strong>Einsatzende</strong> erwartet eine Datums- und Zeitangabe im Format <code>JJJJ-MM-TT hh:mm:ss</code> (z.B. 2014-04-21 21:48:06). Die Sekundenangabe ist optional.</p>';
    //     }
    //     if ('evw_csv' == $this->currentSource->getIdentifier()) {
    //         echo '<p>Die Felder <strong>Alarmzeit</strong> und <strong>Einsatzende</strong> erwarten eine Datums- und Zeitangabe, das Format kann bei der Zuordnung der Felder angegeben werden.</p>';
    //     }
    //     echo '<p>Die Felder <strong>Besonderer Einsatz</strong> und <strong>Fehlalarm</strong> erwarten den Wert <code>1</code> (= ja) oder <code>0</code> (= nein). Sie d&uuml;rfen auch leer bleiben, was als 0 (= nein) zählt.</p>';
    // }
}
