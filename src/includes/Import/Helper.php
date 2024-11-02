<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\Exceptions\ImportPreparationException;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportNumberController;
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
    }

    /**
     * Gibt ein Auswahlfeld zur Zuordnung der Felder in Einsatzverwaltung aus
     *
     * @param array $args {
     *     @type string $name              Name des Dropdownfelds im Formular
     *     @type string $selected          Wert der ausgewählten Option
     *     @type array  $unmatchableFields Felder, die nicht als Importziel auswählbar sein sollen
     * }
     */
    private function dropdownEigeneFelder($args)
    {
        $defaults = array(
            'name' => null,
            'selected' => '-',
            'unmatchableFields' => array()
        );
        $parsedArgs = wp_parse_args($args, $defaults);

        if (empty($parsedArgs['name'])) {
            _doing_it_wrong(__FUNCTION__, 'Name darf nicht null oder leer sein', '');
        }

        $fields = IncidentReport::getFields();

        // Felder, die automatisch beschrieben werden, nicht zur Auswahl stellen
        foreach ($parsedArgs['unmatchableFields'] as $ownField) {
            unset($fields[$ownField]);
        }

        // Sortieren und ausgeben
        uasort($fields, function ($field1, $field2) {
            return strcmp($field1['label'], $field2['label']);
        });
        $string = '<select name="' . $parsedArgs['name'] . '">';
        /** @noinspection HtmlUnknownAttribute */
        $string .= sprintf(
            '<option value="-" %s>%s</option>',
            selected($parsedArgs['selected'], '-', false),
            'nicht importieren'
        );
        foreach ($fields as $slug => $fieldProperties) {
            /** @noinspection HtmlUnknownAttribute */
            $string .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($slug),
                selected($parsedArgs['selected'], $slug, false),
                esc_html($fieldProperties['label'])
            );
        }
        $string .= '</select>';

        echo $string;
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
     * Gibt das Formular für die Zuordnung zwischen zu importieren Feldern und denen von Einsatzverwaltung aus
     *
     * @param AbstractSource $source
     * @param array $args {
     *     @type array  $mapping           Zuordnung von zu importieren Feldern auf Einsatzverwaltungsfelder
     *     @type array  $next_action       Array der nächsten Action
     *     @type string $nonce_action      Wert der Nonce
     *     @type string $action_value      Wert der action-Variable
     *     @type string submit_button_text Beschriftung für den Button unter dem Formular
     * }
     */
    public function renderMatchForm($source, $args)
    {
        $defaults = array(
            'mapping' => array(),
            'next_action' => null,
            'nonce_action' => '',
            'action_value' => '',
            'submit_button_text' => 'Import starten'
        );

        $parsedArgs = wp_parse_args($args, $defaults);
        $fields = $source->getFields();

        $unmatchableFields = $source->getUnmatchableFields();
        if (ReportNumberController::isAutoIncidentNumbers()) {
            $this->utilities->printInfo('Einsatznummern können nur importiert werden, wenn die automatische Verwaltung deaktiviert ist.');

            $unmatchableFields[] = 'einsatz_incidentNumber';
        }

        echo '<form method="post">';
        wp_nonce_field($parsedArgs['nonce_action']);
        echo '<input type="hidden" name="aktion" value="' . $parsedArgs['action_value'] . '" />';
        echo '<table class="evw_match_fields"><tr><th>';
        printf('Feld in %s', $source->getName());
        echo '</th><th>Feld in Einsatzverwaltung</th></tr><tbody>';
        foreach ($fields as $field) {
            echo '<tr><td><strong>' . $field . '</strong></td><td>';
            if (array_key_exists($field, $source->getAutoMatchFields())) {
                echo 'wird automatisch zugeordnet';
            } elseif (in_array($field, $source->getProblematicFields())) {
                $this->utilities->printWarning(sprintf('Probleme mit Feld %s, siehe Analyse', $field));
            } else {
                $selected = '-';
                if (!empty($parsedArgs['mapping']) &&
                    array_key_exists($field, $parsedArgs['mapping']) &&
                    !empty($parsedArgs['mapping'][$field])
                ) {
                    $selected = $parsedArgs['mapping'][$field];
                }

                $this->dropdownEigeneFelder(array(
                    'name' => $source->getInputName($field),
                    'selected' => $selected,
                    'unmatchableFields' => $unmatchableFields
                ));
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        if (!empty($parsedArgs['next_action'])) {
            $source->echoExtraFormFields($parsedArgs['next_action']);
        }
        submit_button($parsedArgs['submit_button_text']);
        echo '</form>';
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
                $errorMessage = sprintf('Konnte Einsatz nicht importieren: %s', $postId->get_error_message());
                $details = array_filter($postId->get_all_error_data(), function ($value) {
                    return is_string($value);
                });
                throw new ImportException($errorMessage, $details);
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

    /**
     * Prüft, ob das Mapping stimmig ist und gibt Warnungen oder Fehlermeldungen aus
     *
     * @param array $mapping Das zu prüfende Mapping
     * @param AbstractSource $source
     *
     * @return bool True bei bestandener Prüfung, false bei Unstimmigkeiten
     */
    public function validateMapping($mapping, $source)
    {
        $valid = true;

        // Pflichtfelder prüfen
        if (!in_array('post_date', $mapping)) {
            $this->utilities->printError('Pflichtfeld Alarmzeit wurde nicht zugeordnet');
            $valid = false;
        }

        $unmatchableFields = $source->getUnmatchableFields();
        $autoMatchFields = $source->getAutoMatchFields();
        if (ReportNumberController::isAutoIncidentNumbers()) {
            $unmatchableFields[] = 'einsatz_incidentNumber';
        }
        foreach ($unmatchableFields as $unmatchableField) {
            if (in_array($unmatchableField, $mapping) && !in_array($unmatchableField, $autoMatchFields)) {
                $this->utilities->printError(sprintf(
                    'Feld %s kann nicht f&uuml;r ein zu importierendes Feld als Ziel angegeben werden',
                    esc_html($unmatchableField)
                ));
                $valid = false;
            }
        }

        // Mehrfache Zuweisungen prüfen
        foreach (array_count_values($mapping) as $ownField => $count) {
            if ($count > 1) {
                $this->utilities->printError(sprintf(
                    'Feld %s kann nicht f&uuml;r mehr als ein zu importierendes Feld als Ziel angegeben werden',
                    IncidentReport::getFieldLabel($ownField)
                ));
                $valid = false;
            }
        }

        return $valid;
    }
}
