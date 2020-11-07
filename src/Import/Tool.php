<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Exceptions\ImportPreparationException;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Utilities;
use WP_Post;

/**
 * Werkzeug für den Import von Einsatzberichten aus verschiedenen Quellen
 */
class Tool
{
    /**
     * @var AbstractSource
     */
    private $currentSource;



    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Generiert den Inhalt der Werkzeugseite
     */
    public function renderToolPage()
    {
        $this->helper = new Helper($this->utilities, $this->data);
        $this->helper->metaFields = IncidentReport::getMetaFields();
        $this->helper->taxonomies = IncidentReport::getTerms();
        $this->helper->postFields = IncidentReport::getPostFields();

        // Einstellungen an die Importquelle übergeben
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

        // 'Sofort veröffentlichen'-Option übernehmen
        $publishReports = filter_input(INPUT_POST, 'import_publish_reports', FILTER_SANITIZE_STRING);
        $this->currentSource->putArg(
            'import_publish_reports',
            Utilities::sanitizeCheckbox($publishReports)
        );

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

            echo '<p>Die CSV-Datei muss f&uuml;r den Import ein bestimmtes Format aufweisen. Jede Zeile in der Datei steht f&uuml;r einen Einsatzbericht und jede Spalte f&uuml;r ein Feld des Einsatzberichts (z.B. Alarmzeit, Einsatzort, ...). Die Reihenfolge der Spalten ist unerheblich, im n&auml;chsten Schritt k&ouml;nnen die Felder aus der Datei denen in der Einsatzverwaltung zugeordnet werden. Die erste Zeile in der Datei kann als Beschriftung der Spalten verwendet werden.</p>';
            $this->printDataNotice();

            echo '<h3>In der Mediathek gefundene CSV-Dateien</h3>';
            echo 'Bevor eine Datei f&uuml;r den Import verwendet werden kann, muss sie in die <a href="' . admin_url('upload.php') . '">Mediathek</a> hochgeladen worden sein. Nach erfolgreichem Import kann die Datei gel&ouml;scht werden.';
            $this->utilities->printWarning('Der Inhalt der Mediathek ist &ouml;ffentlich abrufbar. Achte darauf, dass die Importdatei keine sensiblen Daten enth&auml;lt.');

            $csvAttachments = get_posts(array(
                'post_type' => 'attachment',
                'post_mime_type' => 'text/csv'
            ));

            if (empty($csvAttachments)) {
                echo '<p>Keine CSV-Dateien gefunden.</p>';
                return;
            }

            echo '<form method="post">';
            wp_nonce_field($this->getNonceAction($this->currentSource, $this->nextAction['slug']));

            echo '<fieldset>';
            foreach ($csvAttachments as $csvAttachment) {
                /** @var WP_Post $csvAttachment */
                printf(
                    '<label><input type="radio" name="csv_file_id" value="%d">%s</label><br/>',
                    esc_attr($csvAttachment->ID),
                    esc_html($csvAttachment->post_title)
                );
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
            <p class="description">Als Feldbegrenzerzeichen (umschlie&szlig;t ggf. den Inhalt einer Spalte) wird das Anf&uuml;hrungszeichen <code>&quot;</code> erwartet.</p>
            <?php
            echo '<input type="hidden" name="aktion" value="' . $this->currentSource->getActionAttribute($this->nextAction['slug']) . '" />';
            submit_button($this->nextAction['button_text']);
            echo '</form>';
        }
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
        $this->utilities->printSuccess('Es wurden ' . count($felder) . ' Feld(er) gefunden: ' . implode($felder, ', '));

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
        if (empty($entries)) {
            $this->utilities->printWarning('Es wurden keine Eins&auml;tze gefunden.');
            return;
        }
        $this->utilities->printSuccess(sprintf("Es wurden %s Eins&auml;tze gefunden", count($entries)));

        if ('evw_wpe' == $this->currentSource->getIdentifier()) {
            $this->printDataNotice();
        }

        // Felder matchen
        echo "<h3>Felder zuordnen</h3>";

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
        if (!$this->helper->validateMapping($mapping, $this->currentSource)) {
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
        $importStatus = new ImportStatus($this->utilities, 0);
        try {
            $this->helper->import($this->currentSource, $mapping, $importStatus);
        } catch (ImportException $e) {
            $importStatus->abort('Import abgebrochen, Ursache: ' . $e->getMessage());
            return;
        } catch (ImportPreparationException $e) {
            $importStatus->abort('Importvorbereitung abgebrochen, Ursache: ' . $e->getMessage());
            return;
        }

        $this->utilities->printSuccess('Der Import ist abgeschlossen');
        $url = admin_url('edit.php?post_type=einsatz');
        printf('<a href="%s">Zu den Einsatzberichten</a>', $url);
    }

    private function printDataNotice()
    {
        // Hinweise ausgeben
        echo '<h3>Hinweise zu den erwarteten Daten</h3>';
        echo '<p>Die Felder <strong>Berichtstext, Berichtstitel, Einsatzleiter, Einsatzort</strong> und <strong>Mannschaftsst&auml;rke</strong> sind Freitextfelder.</p>';
        echo '<p>F&uuml;r die Felder <strong>Alarmierungsart, Einsatzart, Externe Einsatzmittel</strong> und <strong>Fahrzeuge</strong> wird eine kommagetrennte Liste erwartet.<br>Bisher unbekannte Eintr&auml;ge werden automatisch angelegt, die Einsatzart sollte nur ein einzelner Wert sein.</p>';
        if ('evw_wpe' == $this->currentSource->getIdentifier()) {
            echo '<p>Das Feld <strong>Einsatzende</strong> erwartet eine Datums- und Zeitangabe im Format <code>JJJJ-MM-TT hh:mm:ss</code> (z.B. 2014-04-21 21:48:06). Die Sekundenangabe ist optional.</p>';
        }
        if ('evw_csv' == $this->currentSource->getIdentifier()) {
            echo '<p>Die Felder <strong>Alarmzeit</strong> und <strong>Einsatzende</strong> erwarten eine Datums- und Zeitangabe, das Format kann bei der Zuordnung der Felder angegeben werden.</p>';
        }
        echo '<p>Die Felder <strong>Besonderer Einsatz</strong> und <strong>Fehlalarm</strong> erwarten Ja/Nein-Werte. Als Ja interpretiert werden <code>1</code> und <code>Ja</code> (Gro&szlig;- und Kleinschreibung unerheblich), alle anderen Werte einschlie&szlig;lich eines leeren Feldes z&auml;hlen als Nein.</p>';
    }
}
