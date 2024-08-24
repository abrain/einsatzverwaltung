<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Exceptions\ImportPreparationException;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Utilities;
use DateTime;
use function array_map;
use function explode;

/**
 * Verschiedene Funktionen für den Import von Einsatzberichten
 */
class Helper
{
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var array
     */
    public $metaFields;

    /**
     * @var array
     */
    public $postFields;

    /**
     * @var array
     */
    public $taxonomies;

    /**
     * Helper constructor.
     * @param Utilities $utilities
     * @param Data $data
     */
    public function __construct(Utilities $utilities, Data $data)
    {
        $this->utilities = $utilities;
        $this->data = $data;

        $this->metaFields = IncidentReport::getMetaFields();
        $this->taxonomies = IncidentReport::getTerms();
        $this->postFields = IncidentReport::getPostFields();
    }

    /**
     * @param array $mapping
     * @param array $sourceEntry
     * @param array $insertArgs
     * @throws ImportPreparationException
     */
    public function mapEntryToInsertArgs($mapping, $sourceEntry, &$insertArgs)
    {
        foreach ($mapping as $sourceField => $ownField) {
            if (empty($ownField) || !is_string($ownField)) {
                $this->utilities->printError("Feld '$ownField' ung&uuml;ltig");
                continue;
            }

            $sourceValue = trim($sourceEntry[$sourceField]);
            if (array_key_exists($ownField, $this->metaFields)) {
                // Wert gehört in ein Metafeld
                $insertArgs['meta_input'][$ownField] = $sourceValue;
            } elseif (array_key_exists($ownField, $this->taxonomies)) {
                // Wert gehört zu einer Taxonomie
                if (empty($sourceValue)) {
                    // Leere Terms überspringen
                    continue;
                }

                $insertArgs['tax_input'][$ownField] = $this->getTaxInputList($ownField, $sourceValue);
            } elseif (array_key_exists($ownField, $this->postFields)) {
                // Wert gehört direkt zum Post
                $insertArgs[$ownField] = $sourceValue;
            } elseif ($ownField == '-') {
                $this->utilities->printWarning("Feld '$sourceField' nicht zugeordnet");
            } else {
                $this->utilities->printError("Feld '$ownField' unbekannt");
            }
        }
    }

    /**
     * Bereitet eine kommaseparierte Auflistung von Terms einer bestimmten Taxonomie so, dass sie beim Anlegen eines
     * Einsatzberichts für die gegebene Taxonomie als tax_input verwendet werden kann.
     *
     * @param string $taxonomy
     * @param string $terms
     *
     * @return string[]|int[]
     * @throws ImportPreparationException
     */
    public function getTaxInputList(string $taxonomy, string $terms): array
    {
        if (is_taxonomy_hierarchical($taxonomy) === false) {
            // Use term names/slugs as they are
            return array_map('trim', explode(',', $terms));
        }

        // Hierarchical taxonomies require a list of IDs instead of names
        $termIds = array();

        $termNames = explode(',', $terms);
        foreach ($termNames as $termName) {
            $termIds[] = $this->getTermId($termName, $taxonomy);
        }

        return $termIds;
    }

    /**
     * Bestimmt die ID eines Terms einer hierarchischen Taxonomie. Existiert dieser noch nicht, wird er angelegt.
     *
     * @param string $termName
     * @param string $taxonomy
     * @return int
     * @throws ImportPreparationException
     */
    public function getTermId($termName, $taxonomy)
    {
        if (is_taxonomy_hierarchical($taxonomy) === false) {
            throw new ImportPreparationException("Die Taxonomie $taxonomy ist nicht hierarchisch!");
        }

        $termName = trim($termName);
        $term = get_term_by('name', $termName, $taxonomy);

        if ($term !== false) {
            // Term existiert bereits, ID verwenden
            return $term->term_id;
        }

        // Term existiert in dieser Taxonomie noch nicht, neu anlegen
        $newterm = wp_insert_term($termName, $taxonomy);

        if (is_wp_error($newterm)) {
            throw new ImportPreparationException(sprintf(
                "Konnte %s '%s' nicht anlegen: %s",
                $this->taxonomies[$taxonomy]['label'],
                $termName,
                $newterm->get_error_message()
            ));
        }

        // Anlegen erfolgreich, zurückgegebene ID verwenden
        return $newterm['term_id'];
    }

    /**
     * Importiert Einsätze aus der wp-einsatz-Tabelle
     *
     * @param AbstractSource $source
     * @param array $mapping Zuordnung zwischen zu importieren Feldern und denen der Einsatzverwaltung
     * @param ImportStatus $importStatus
     * @throws ImportException
     * @throws ImportPreparationException
     */
    public function import($source, $mapping, $importStatus)
    {
        $preparedInsertArgs = array();
        $yearsAffected = array();

        // Den Import vorbereiten, um möglichst alle Fehler vorher abzufangen
        $this->prepareImport($source, $mapping, $preparedInsertArgs, $yearsAffected);

        $importStatus->totalSteps = count($preparedInsertArgs);
        $importStatus->displayMessage('Daten eingelesen, starte den Import...');

        // Den tatsächlichen Import starten
        $this->runImport($preparedInsertArgs, $source, $yearsAffected, $importStatus);
    }

    /**
     * @param array $insertArgs
     * @param string $dateTimeFormat
     * @param string $postStatus
     * @param DateTime $alarmzeit
     * @throws ImportPreparationException
     */
    public function prepareArgsForInsertPost(&$insertArgs, $dateTimeFormat, $postStatus, $alarmzeit)
    {
        // Datum des Einsatzes prüfen
        if (false === $alarmzeit) {
            throw new ImportPreparationException(sprintf(
                'Die Alarmzeit %s konnte mit dem angegebenen Format %s nicht eingelesen werden',
                esc_html($insertArgs['post_date']),
                esc_html($dateTimeFormat)
            ));
        }

        // Solange der Einsatzbericht ein Entwurf ist, soll kein Datum gesetzt werden (vgl. wp_update_post()).
        if ($postStatus === 'draft') {
            // Wird bis zur Veröffentlichung in Postmeta zwischengespeichert.
            $insertArgs['meta_input']['_einsatz_timeofalerting'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            unset($insertArgs['post_date']);
            unset($insertArgs['post_date_gmt']);
        } else {
            $insertArgs['post_date'] = $alarmzeit->format('Y-m-d H:i:s');
            $insertArgs['post_date_gmt'] = get_gmt_from_date($insertArgs['post_date']);
        }

        // Einsatzende korrekt formatieren
        if (array_key_exists('einsatz_einsatzende', $insertArgs['meta_input']) &&
            !empty($insertArgs['meta_input']['einsatz_einsatzende'])
        ) {
            $endDate = DateTime::createFromFormat($dateTimeFormat, $insertArgs['meta_input']['einsatz_einsatzende']);
            if (false === $endDate) {
                throw new ImportPreparationException(sprintf(
                    'Das Einsatzende %s konnte mit dem angegebenen Format %s nicht eingelesen werden',
                    esc_html($insertArgs['meta_input']['einsatz_einsatzende']),
                    esc_html($dateTimeFormat)
                ));
            }

            $insertArgs['meta_input']['einsatz_einsatzende'] = $endDate->format('Y-m-d H:i');
        }

        $insertArgs['post_type'] = 'einsatz';
        $insertArgs['post_status'] = $postStatus;

        // Titel sicherstellen
        if (!array_key_exists('post_title', $insertArgs)) {
            $insertArgs['post_title'] = 'Einsatz';
        }
        $insertArgs['post_title'] = wp_strip_all_tags($insertArgs['post_title']);
        if (empty($insertArgs['post_title'])) {
            $insertArgs['post_title'] = 'Einsatz';
        }

        // sicherstellen, dass boolsche Werte als 0 oder 1 dargestellt werden
        $boolAnnotations = array('einsatz_special', 'einsatz_fehlalarm', 'einsatz_hasimages');
        foreach ($boolAnnotations as $metaKey) {
            $insertArgs['meta_input'][$metaKey] = $this->sanitizeBooleanValues(@$insertArgs['meta_input'][$metaKey]);
        }
    }

    /**
     * Stellt sicher, dass boolsche Werte durch 0 und 1 dargestellt werden
     * @param string $value
     * @return string
     */
    public function sanitizeBooleanValues($value)
    {
        if (empty($value)) {
            return '0';
        }

        return (in_array(strtolower($value), array('1', 'ja')) ? '1' : '0');
    }

    /**
     * @param AbstractSource $source
     * @param array $mapping
     * @param array $preparedInsertArgs
     * @param array $yearsAffected
     * @throws ImportException
     * @throws ImportPreparationException
     */
    public function prepareImport($source, $mapping, &$preparedInsertArgs, &$yearsAffected)
    {
        $sourceEntries = $source->getEntries(array_keys($mapping));
        if (empty($sourceEntries)) {
            throw new ImportPreparationException('Die Importquelle lieferte keine Ergebnisse. Entweder sind dort keine Eins&auml;tze gespeichert oder es gab ein Problem bei der Abfrage.');
        }

        $dateFormat = $source->getDateFormat();
        $timeFormat = $source->getTimeFormat();
        if (!empty($dateFormat) && !empty($timeFormat)) {
            $dateTimeFormat = $dateFormat . ' ' . $timeFormat;
        }
        if (empty($dateTimeFormat)) {
            $dateTimeFormat = 'Y-m-d H:i';
        }

        // Der Veröffentlichungsstatus der importierten Berichte
        $postStatus = $source->isPublishReports() ? 'publish' : 'draft';

        foreach ($sourceEntries as $sourceEntry) {
            $insertArgs = array();
            $insertArgs['post_content'] = '';
            $insertArgs['tax_input'] = array();
            $insertArgs['meta_input'] = array();

            $this->mapEntryToInsertArgs($mapping, $sourceEntry, $insertArgs);
            $alarmzeit = DateTime::createFromFormat($dateTimeFormat, $insertArgs['post_date']);
            $this->prepareArgsForInsertPost($insertArgs, $dateTimeFormat, $postStatus, $alarmzeit);

            $preparedInsertArgs[] = $insertArgs;
            $yearsAffected[$alarmzeit->format('Y')] = 1;
        }
    }

    /**
     * @param array $preparedInsertArgs
     * @param AbstractSource $source
     * @param array $yearsAffected
     * @param ImportStatus $importStatus
     * @throws ImportException
     */
    public function runImport($preparedInsertArgs, $source, $yearsAffected, $importStatus)
    {
        // Für die Dauer des Imports sollen die laufenden Nummern nicht aktuell gehalten werden, da dies die Performance
        // stark beeinträchtigt
        if ($source->isPublishReports()) {
            $this->data->pauseAutoSequenceNumbers();
        }

        foreach ($preparedInsertArgs as $insertArgs) {
            // Neuen Beitrag anlegen
            $postId = wp_insert_post($insertArgs, true);
            if (is_wp_error($postId)) {
                throw new ImportException('Konnte Einsatz nicht importieren: ' . $postId->get_error_message());
            }

            $importStatus->importSuccesss($postId);
        }

        if ($source->isPublishReports()) {
            // Die automatische Aktualisierung der laufenden Nummern wird wieder aufgenommen
            $this->data->resumeAutoSequenceNumbers();
            foreach (array_keys($yearsAffected) as $year) {
                $importStatus->displayMessage(sprintf('Aktualisiere laufende Nummern für das Jahr %d...', $year));
                $this->data->updateSequenceNumbers(strval($year));
            }
        }
    }
}
