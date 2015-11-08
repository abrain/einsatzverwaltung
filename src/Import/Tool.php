<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Import\Sources\Csv;
use abrain\Einsatzverwaltung\Import\Sources\WpEinsatz;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Utilities;

/**
 * Werkzeug für den Import von Einsatzberichten aus verschiedenen Quellen
 */
class Tool
{
    const EVW_TOOL_IMPORT_SLUG = 'einsatzvw-tool-import';

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
     * Konstruktor
     */
    public function __construct()
    {
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
            __('Einsatzberichte importieren', 'einsatzverwaltung'),
            __('Einsatzberichte importieren', 'einsatzverwaltung'),
            'manage_options',
            self::EVW_TOOL_IMPORT_SLUG,
            array($this, 'renderToolPage')
        );
    }

    /**
     * @param AbstractSource $source
     * @param string $action
     */
    private function checkNonce($source, $action)
    {
        check_admin_referer($this->getNonceAction($source, $action));
    }

    /**
     * @param AbstractSource $source
     * @param string $action
     * @return string
     */
    private function getNonceAction($source, $action)
    {
        return $source->getIdentifier() . '_' . $action;
    }

    private function loadSources()
    {
        require_once dirname(__FILE__) . '/Sources/AbstractSource.php';
        require_once dirname(__FILE__) . '/Sources/WpEinsatz.php';
        $wpEinsatz = new WpEinsatz();
        $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;

        require_once dirname(__FILE__) . '/Sources/Csv.php';
        $csv = new Csv();
        $this->sources[$csv->getIdentifier()] = $csv;
    }

    /**
     * Generiert den Inhalt der Werkzeugseite
     */
    public function renderToolPage()
    {
        require_once dirname(__FILE__) . '/Helper.php';
        $this->helper = new Helper();

        echo '<div class="wrap">';
        echo '<h1>' . __('Einsatzberichte importieren', 'einsatzverwaltung') . '</h1>';

        $aktion = null;
        if (array_key_exists('aktion', $_POST)) {
            list($identifier, $aktion) = explode(':', $_POST['aktion']);
            if (array_key_exists($identifier, $this->sources)) {
                $this->currentSource = $this->sources[$identifier];
            }
        }

        if (null == $this->currentSource || !($this->currentSource instanceof AbstractSource) || empty($aktion)) {
            echo '<p>Dieses Werkzeug importiert Einsatzberichte aus verschiedenen Quellen.</p>';

            echo '<ul>';
            /** @var AbstractSource $source */
            foreach ($this->sources as $source) {
                $firstAction = $source->getFirstAction();

                echo '<li>';
                echo '<h3>' . $source->getName() . '</h3>';
                echo '<p class="description">' . $source->getDescription() . '</p>';
                if (false !== $firstAction) {
                    echo '<form method="post">';
                    echo '<input type="hidden" name="aktion" value="' . $source->getActionAttribute($firstAction['slug']) . '" />';
                    wp_nonce_field($this->getNonceAction($source, $firstAction['slug']));
                    submit_button($firstAction['button_text']);
                    echo '</form>';
                }
                echo '</li>';
            }
            echo '</ul>';
            return;
        }

        // Nonce überprüfen
        $this->checkNonce($this->currentSource, $aktion);

        $this->currentAction = $this->currentSource->getAction($aktion);
        $this->nextAction = $this->currentSource->getNextAction($this->currentAction);

        if (array_key_exists('args', $this->currentAction) && is_array($this->currentAction['args'])) {
            foreach ($this->currentAction['args'] as $arg) {
                $value = (array_key_exists($arg, $_POST) ? $_POST[$arg] : null); //TODO sanitize
                $this->currentSource->putArg($arg, $value);
            }
        }

        echo "<h3>{$this->currentAction['name']}</h3>";

        // TODO gemeinsame Prüfungen auslagern
        if ('analysis' == $aktion) {
            $this->analysisPage();
        } elseif ('import' == $aktion) {
            $this->importPage();
        } elseif ('selectcsvfile' == $aktion) {
            if (false === $this->nextAction) {
                Utilities::printError('Keine Nachfolgeaktion gefunden!');
                return;
            }

            echo '<form method="post"><input id="csv_file_id" name="csv_file_id" type="text" />';
            wp_nonce_field($this->getNonceAction($this->currentSource, $this->nextAction['slug']));
            // TODO Dialog zur Dateiauswahl
            ?>
            <br/><input id="has_headlines" name="has_headlines" type="checkbox" value="1" />
            <label for="has_headlines">Erste Zeile der Datei enth&auml;lt Spaltenbeschriftung</label>
            <br/><label>
                Trennzeichen zwischen den Spalten:
                <select name="delimiter">
                    <option value=";">Semikolon</option>
                    <option value=",">Komma</option>
                </select>
            </label>
            <?php
            echo '<input type="hidden" name="aktion" value="' . $this->currentSource->getActionAttribute($this->nextAction['slug']) . '" />';
            submit_button($this->nextAction['button_text']);
            echo '</form>';
        }

        echo '</div>';
    }

    private function analysisPage()
    {
        if (!$this->currentSource->checkPreconditions()) {
            return;
        }

        $felder = $this->currentSource->getFields();
        if (empty($felder)) {
            Utilities::printError('Es wurden keine Felder gefunden');
            return;
        }
        Utilities::printSuccess('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));

        // Auf Pflichtfelder prüfen
        $mandatoryFieldsOk = true;
        foreach (array_keys($this->currentSource->getAutoMatchFields()) as $autoMatchField) {
            if (!in_array($autoMatchField, $felder)) {
                Utilities::printError(
                    sprintf('Das automatisch zu importierende Feld %s konnte nicht gefunden werden!', $autoMatchField)
                );
                $mandatoryFieldsOk = false;
            }
        }
        if (!$mandatoryFieldsOk) {
            return;
        }

        // Einsätze zählen
        $entries = $this->currentSource->getEntries(null);
        if (false === $entries) {
            return;
        }
        if (empty($entries)) {
            Utilities::printWarning('Es wurden keine Eins&auml;tze gefunden.');
            return;
        }
        Utilities::printSuccess(sprintf("Es wurden %s Eins&auml;tze gefunden", count($entries)));

        // Hinweise ausgeben
        echo '<h3>Hinweise zu den erwarteten Daten</h3>';
        echo '<p>Die Felder <strong>Berichtstext, Berichtstitel, Einsatzleiter, Einsatzort</strong> und <strong>Mannschaftsst&auml;rke</strong> sind Freitextfelder.</p>';
        echo '<p>F&uuml;r die Felder <strong>Alarmierungsart, Einsatzart, Externe Einsatzmittel</strong> und <strong>Fahrzeuge</strong> wird eine kommagetrennte Liste erwartet.<br>Bisher unbekannte Eintr&auml;ge werden automatisch angelegt, die Einsatzart sollte nur ein einzelner Wert sein.</p>';
        echo '<p>Das Feld <strong>Einsatzende</strong> erwartet eine Datums- und Zeitangabe im Format <code>JJJJ-MM-TT hh:mm:ss</code> (z.B. 2014-04-21 21:48:06). Die Sekundenangabe ist optional.</p>';
        echo '<p>Das Feld <strong>Fehlalarm</strong> erwartet den Wert 1 (= ja) oder 0 (= nein). Es darf auch leer bleiben, was als 0 (= nein) zählt.</p>';

        // Felder matchen
        echo "<h3>Felder zuordnen</h3>";
        if (false === $this->nextAction) {
            Utilities::printError('Keine Nachfolgeaktion gefunden!');
            return;
        }

        $this->helper->renderMatchForm($this->currentSource, array(
            'nonce_action' => $this->getNonceAction($this->currentSource, $this->nextAction['slug']),
            'action_value' => $this->currentSource->getActionAttribute($this->nextAction['slug']),
            'next_action' => $this->nextAction
        ));
    }

    private function importPage()
    {
        if (!$this->currentSource->checkPreconditions()) {
            return;
        }

        $sourceFields = $this->currentSource->getFields();
        if (empty($sourceFields)) {
            Utilities::printError('Es wurden keine Felder gefunden');
            return;
        }

        // Mapping einlesen
        $mapping = $this->currentSource->getMapping($sourceFields, IncidentReport::getFields());

        // Prüfen, ob mehrere Felder das gleiche Zielfeld haben
        if (!$this->helper->validateMapping($mapping)) {
            // Und gleich nochmal...
            $this->nextAction = $this->currentAction;

            $this->helper->renderMatchForm($this->currentSource, array(
                'mapping' => $mapping,
                'nonce_action' => $this->getNonceAction($this->currentSource, $this->nextAction['slug']),
                'action_value' => $this->currentSource->getActionAttribute($this->nextAction['slug']),
                'next_action' => $this->nextAction
            ));
            return;
        }

        // Datenbank auslesen
        $entries = $this->currentSource->getEntries(array_keys($mapping));

        if (empty($entries)) {
            Utilities::printError('Die Importquelle lieferte keine Ergebnisse. Entweder sind dort keine Eins&auml;tze gespeichert oder es gab ein Problem bei der Abfrage.');
            return;
        }

        // Import starten
        echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
        $this->helper->import($entries, $mapping);
    }
}
