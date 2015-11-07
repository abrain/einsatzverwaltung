<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
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
    }

    /**
     * Generiert den Inhalt der Werkzeugseite
     */
    public function renderToolPage()
    {
        require_once dirname(__FILE__) . '/Helper.php';
        $helper = new Helper();

        echo '<div class="wrap">';
        echo '<h1>' . __('Einsatzberichte importieren', 'einsatzverwaltung') . '</h1>';

        $source = null;
        $aktion = null;
        if (array_key_exists('aktion', $_POST)) {
            list($identifier, $aktion) = explode(':', $_POST['aktion']);
            if (array_key_exists($identifier, $this->sources)) {
                $source = $this->sources[$identifier];
            }
        }

        if (null == $source || !($source instanceof AbstractSource) || empty($aktion)) {
            echo '<p>Dieses Werkzeug importiert Einsatzberichte aus verschiedenen Quellen.</p>';

            echo '<ul>';
            /** @var AbstractSource $source */
            foreach ($this->sources as $source) {
                echo '<li>';
                echo '<h3>' . $source->getName() . '</h3>';
                echo '<p class="description">' . $source->getDescription() . '</p>';
                echo '<form method="post">';
                echo '<input type="hidden" name="aktion" value="' . $source->getActionAttribute('begin') . '" />';
                wp_nonce_field($this->getNonceAction($source, 'begin'));
                submit_button(__('Assistent starten', 'einsatzverwaltung'));
                echo '</form>';
                echo '</li>';
            }
            echo '</ul>';
            return;
        }

        // Nonce überprüfen
        $this->checkNonce($source, $aktion);

        // TODO gemeinsame Prüfungen auslagern
        if ('begin' == $aktion) {
            echo "<h3>Analyse</h3>";
            if (!$source->checkPreconditions()) {
                return;
            }

            $felder = $source->getFields();
            if (empty($felder)) {
                Utilities::printError('Es wurden keine Felder gefunden');
                return;
            }
            Utilities::printSuccess('Es wurden folgende Felder gefunden: ' . implode($felder, ', '));

            // Auf Pflichtfelder prüfen
            $mandatoryFieldsOk = true;
            foreach (array_keys($source->getAutoMatchFields()) as $mandatoryField) {
                if (!in_array($mandatoryField, $felder)) {
                    Utilities::printError(
                        sprintf('Das Pflichtfeld %s konnte nicht gefunden werden!', $mandatoryField)
                    );
                    $mandatoryFieldsOk = false;
                }
            }
            if (!$mandatoryFieldsOk) {
                return;
            }

            // Auf problematische Felder prüfen
            $source->checkForProblems($felder);

            // Einsätze zählen
            $entries = $source->getEntries(null);
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
            $helper->renderMatchForm($source, array(
                'nonce_action' => $this->getNonceAction($source, 'import'),
                'action_value' => $source->getActionAttribute('import')
            ));
        } elseif ('import' == $aktion) {
            echo '<h3>Import</h3>';

            $sourceFields = $source->getFields();
            if (empty($sourceFields)) {
                Utilities::printError('Es wurden keine Felder gefunden');
                return;
            }

            // Mapping einlesen
            $mapping = $source->getMapping($sourceFields, IncidentReport::getFields());

            // Prüfen, ob mehrere Felder das gleiche Zielfeld haben
            if (!$helper->validateMapping($mapping)) {
                $helper->renderMatchForm($source, array(
                    'mapping' => $mapping,
                    'nonce_action' => $this->getNonceAction($source, 'import'),
                    'action_value' => $source->getActionAttribute('import')
                ));
                return;
            }

            // Datenbank auslesen
            $entries = $source->getEntries(array_keys($mapping));

            if (empty($entries)) {
                Utilities::printError('Die Importquelle lieferte keine Ergebnisse. Entweder sind dort keine Eins&auml;tze gespeichert oder es gab ein Problem bei der Abfrage.');
                return;
            }

            // Import starten
            echo '<p>Die Daten werden eingelesen, das kann einen Moment dauern.</p>';
            $helper->import($entries, $mapping);
        }

        echo '</div>';
    }
}
