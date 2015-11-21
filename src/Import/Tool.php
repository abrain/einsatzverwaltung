<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Core;
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
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Core
     */
    private $core;

    /**
     * Konstruktor
     *
     * @param Core $core
     * @param Utilities $utilities
     */
    public function __construct($core, $utilities)
    {
        $this->core = $core;
        $this->utilities = $utilities;
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
        $wpEinsatz = new WpEinsatz($this->utilities);
        $this->sources[$wpEinsatz->getIdentifier()] = $wpEinsatz;

        require_once dirname(__FILE__) . '/Sources/Csv.php';
        $csv = new Csv($this->utilities);
        $this->sources[$csv->getIdentifier()] = $csv;
    }

    /**
     * Generiert den Inhalt der Werkzeugseite
     */
    public function renderToolPage()
    {
        require_once dirname(__FILE__) . '/Helper.php';
        $this->helper = new Helper($this->utilities, $this->core);

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
                echo '<h2>' . $source->getName() . '</h2>';
                echo '<p class="description">' . $source->getDescription() . '</p>';
                if (false !== $firstAction) {
                    echo '<form method="post">';
                    echo '<input type="hidden" name="aktion" value="' . $source->getActionAttribute($firstAction['slug']) . '" />';
                    wp_nonce_field($this->getNonceAction($source, $firstAction['slug']));
                    submit_button($firstAction['button_text'], 'secondary', 'submit', false);
                    echo '</form>';
                }
                echo '</li>';
            }
            echo '</ul>';
            return;
        }

        // Nonce überprüfen
        $this->checkNonce($this->currentSource, $aktion);

        // Variablen für die Ablaufsteuerung
        $this->currentAction = $this->currentSource->getAction($aktion);
        $this->nextAction = $this->currentSource->getNextAction($this->currentAction);

        // Einstellungen an die Imortquelle übergeben
        if (array_key_exists('args', $this->currentAction) && is_array($this->currentAction['args'])) {
            foreach ($this->currentAction['args'] as $arg) {
                $value = (array_key_exists($arg, $_POST) ? sanitize_text_field($_POST[$arg]) : null);
                $this->currentSource->putArg($arg, $value);
            }
        }

        // Datums- und Zeitformat für CSV-Import übernehmen
        if ('evw_csv' == $this->currentSource->getIdentifier()) {
            if (array_key_exists('import_date_format', $_POST)) {
                $this->currentSource->putArg('import_date_format', sanitize_text_field($_POST['import_date_format']));
            }

            if (array_key_exists('import_time_format', $_POST)) {
                $this->currentSource->putArg('import_time_format', sanitize_text_field($_POST['import_time_format']));
            }
        }

        echo "<h2>{$this->currentAction['name']}</h2>";

        // TODO gemeinsame Prüfungen auslagern
        if ('analysis' == $aktion) {
            $this->analysisPage();
        } elseif ('import' == $aktion) {
            $this->importPage();
        } elseif ('selectcsvfile' == $aktion) {
            if (false === $this->nextAction) {
                $this->utilities->printError('Keine Nachfolgeaktion gefunden!');
                return;
            }

            echo '<form method="post">';
            wp_nonce_field($this->getNonceAction($this->currentSource, $this->nextAction['slug']));

            $csvAttachments = get_posts(array(
                'post_type' => 'attachment',
                'post_mime_type' => 'text/csv'
            ));

            if (empty($csvAttachments)) {
                $this->utilities->printInfo('Bitte lade die zu importierende CSV-Datei <a href="' . admin_url('media-new.php') . '">hier</a> in die Mediathek hoch. Nach dem Import kann und sollte die Datei aus der Mediathek gel&ouml;scht werden, sofern sie nicht &ouml;ffentlich zug&auml;nglich sein soll.');
                return;
            }

            echo '<h3>In der Mediathek gefundene CSV-Dateien</h3>';
            echo '<fieldset>';
            foreach ($csvAttachments as $csvAttachment) {
                /** @var \WP_Post $csvAttachment */
                echo '<label><input type="radio" name="csv_file_id" value="' . $csvAttachment->ID . '">';
                echo $csvAttachment->post_title . '</label><br/>';
            }
            echo '</fieldset>';
            ?>
            <h3>Aufbau der CSV-Datei</h3>
            <input id="has_headlines" name="has_headlines" type="checkbox" value="1" />
            <label for="has_headlines">Erste Zeile der Datei enth&auml;lt Spaltenbeschriftung</label>
            <p class="description">Setze diesen Haken, wenn die erste Zeile der CSV-Datei keine Daten von Eins&auml;tzen enth&auml;lt, sondern nur die &Uuml;berschriften der jeweiligen Spalten.</p>
            <br>
            Trennzeichen zwischen den Spalten:&nbsp;
            <label><input type="radio" name="delimiter" value=";" checked="checked"><code>;</code> Semikolon</label>
            &nbsp;<label><input type="radio" name="delimiter" value=","><code>,</code> Komma</label>
            <p class="description">Meist werden die Spalten mit einem Semikolon voneinander getrennt. Wenn du unsicher bist, solltest du die CSV-Datei mit einem Texteditor &ouml;ffnen und nachsehen.</p>
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
            $this->utilities->printError('Es wurden keine Felder gefunden');
            return;
        }
        $this->utilities->printSuccess('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));

        // Auf Pflichtfelder prüfen
        $mandatoryFieldsOk = true;
        foreach (array_keys($this->currentSource->getAutoMatchFields()) as $autoMatchField) {
            if (!in_array($autoMatchField, $felder)) {
                $this->utilities->printError(
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
            $this->utilities->printWarning('Es wurden keine Eins&auml;tze gefunden.');
            return;
        }
        $this->utilities->printSuccess(sprintf("Es wurden %s Eins&auml;tze gefunden", count($entries)));

        // Hinweise ausgeben
        echo '<h3>Hinweise zu den erwarteten Daten</h3>';
        echo '<p>Die Felder <strong>Berichtstext, Berichtstitel, Einsatzleiter, Einsatzort</strong> und <strong>Mannschaftsst&auml;rke</strong> sind Freitextfelder.</p>';
        echo '<p>F&uuml;r die Felder <strong>Alarmierungsart, Einsatzart, Externe Einsatzmittel</strong> und <strong>Fahrzeuge</strong> wird eine kommagetrennte Liste erwartet.<br>Bisher unbekannte Eintr&auml;ge werden automatisch angelegt, die Einsatzart sollte nur ein einzelner Wert sein.</p>';
        if ('evw_wpe' == $this->currentSource->getIdentifier()) {
            echo '<p>Das Feld <strong>Einsatzende</strong> erwartet eine Datums- und Zeitangabe im Format <code>JJJJ-MM-TT hh:mm:ss</code> (z.B. 2014-04-21 21:48:06). Die Sekundenangabe ist optional.</p>';
        }
        if ('evw_csv' == $this->currentSource->getIdentifier()) {
            echo '<p>Die Felder <strong>Alarmzeit</strong> und <strong>Einsatzende</strong> erwarten eine Datums- und Zeitangabe im unten einstellbaren Format.</p>';
        }
        echo '<p>Das Feld <strong>Fehlalarm</strong> erwartet den Wert 1 (= ja) oder 0 (= nein). Es darf auch leer bleiben, was als 0 (= nein) zählt.</p>';

        // Felder matchen
        echo "<h3>Felder zuordnen</h3>";
        if (false === $this->nextAction) {
            $this->utilities->printError('Keine Nachfolgeaktion gefunden!');
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
            $this->utilities->printError('Es wurden keine Felder gefunden');
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

        // Import starten
        echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
        $this->helper->import($this->currentSource, $mapping);
    }
}
