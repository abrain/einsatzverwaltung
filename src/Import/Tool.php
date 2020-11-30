<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Exceptions\ImportPreparationException;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Utilities;

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

        // TODO gemeinsame Prüfungen auslagern
        if ('analysis' == $aktion) {
            $this->analysisPage();
        } elseif ('import' == $aktion) {
            $this->importPage();
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
}
