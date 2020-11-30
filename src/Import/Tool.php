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
