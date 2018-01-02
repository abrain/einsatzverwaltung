<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Utilities;
use DateTime;
use Exception;

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
     * @var Options
     */
    private $options;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var array
     */
    private $metaFields;

    /**
     * @var array
     */
    private $postFields;

    /**
     * @var array
     */
    private $taxonomies;

    /**
     * Helper constructor.
     * @param Utilities $utilities
     * @param Options $options
     * @param Data $data
     */
    public function __construct(Utilities $utilities, Options $options, Data $data)
    {
        $this->utilities = $utilities;
        $this->options = $options;
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

        if (null === $parsedArgs['name'] || empty($parsedArgs['name'])) {
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
        $string .= '<option value="-"' . ($parsedArgs['selected'] == '-' ? ' selected="selected"' : '') . '>';
        $string .= 'nicht importieren' . '</option>';
        foreach ($fields as $slug => $fieldProperties) {
            $string .= '<option value="' . $slug . '"' . ($parsedArgs['selected'] == $slug ? ' selected="selected"' : '') . '>';
            $string .= $fieldProperties['label'] . '</option>';
        }
        $string .= '</select>';

        echo $string;
    }

    /**
     * @param array $mapping
     * @param array $sourceEntry
     * @param array $insertArgs
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

                $insertArgs['tax_input'][$ownField] = $this->getTaxInputString($ownField, $sourceValue);
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
     * @return string
     */
    public function getTaxInputString($taxonomy, $terms)
    {
        if (is_taxonomy_hierarchical($taxonomy) === false) {
            // Termnamen können direkt verwendet werden
            return $terms;
        }

        // Bei hierarchischen Taxonomien muss die ID statt des Namens verwendet werden
        $termIds = array();

        $termNames = explode(',', $terms);
        foreach ($termNames as $termName) {
            try {
                $termIds[] = $this->getTermId($termName, $taxonomy);
            } catch (Exception $e) {
                $this->utilities->printError($e->getMessage());
            }
        }

        return implode(',', $termIds);
    }

    /**
     * Bestimmt die ID eines Terms einer hierarchischen Taxonomie. Existiert dieser noch nicht, wird er angelegt.
     *
     * @param string $termName
     * @param string $taxonomy
     * @return int
     * @throws Exception
     */
    public function getTermId($termName, $taxonomy)
    {
        if (is_taxonomy_hierarchical($taxonomy) === false) {
            throw new Exception("Die Taxonomie $taxonomy ist nicht hierarchisch!");
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
            throw new Exception(sprintf(
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
     */
    public function import($source, $mapping)
    {
        set_time_limit(0); // Zeitlimit deaktivieren
        
        $sourceEntries = $source->getEntries(array_keys($mapping));
        if (empty($sourceEntries)) {
            $this->utilities->printError('Die Importquelle lieferte keine Ergebnisse. Entweder sind dort keine Eins&auml;tze gespeichert oder es gab ein Problem bei der Abfrage.');
            return;
        }

        // Der Veröffentlichungsstatus der importierten Berichte
        $postStatus = $source->isPublishReports() ? 'publish' : 'draft';

        // Für die Dauer des Imports sollen die laufenden Nummern nicht aktuell gehalten werden, da dies die Performance
        // stark beeinträchtigt
        if ('publish' === $postStatus) {
            $this->data->pauseAutoSequenceNumbers();
        }
        $yearsImported = array();

        $dateFormat = $source->getDateFormat();
        $timeFormat = $source->getTimeFormat();
        if (!empty($dateFormat) && !empty($timeFormat)) {
            $dateTimeFormat = $dateFormat . ' ' . $timeFormat;
        }
        if (empty($dateTimeFormat)) {
            $dateTimeFormat = 'Y-m-d H:i';
        }

        try {
            foreach ($sourceEntries as $sourceEntry) {
                $insertArgs = array();
                $insertArgs['post_content'] = '';

                $this->mapEntryToInsertArgs($mapping, $sourceEntry, $insertArgs);
                $alarmzeit = DateTime::createFromFormat($dateTimeFormat, $insertArgs['post_date']);
                $this->prepareArgsForInsertPost($insertArgs, $dateTimeFormat, $postStatus, $alarmzeit);

                // Neuen Beitrag anlegen
                $postId = wp_insert_post($insertArgs, true);
                if (is_wp_error($postId)) {
                    throw new Exception('Konnte Einsatz nicht importieren: ' . $postId->get_error_message());
                }

                $this->utilities->printInfo('Einsatz importiert, ID ' . $postId);
                $yearsImported[$alarmzeit->format('Y')] = 1;
            }
        } catch (Exception $e) {
            $this->utilities->printError('Import abgebrochen, Ursache: ' . $e->getMessage());
        }

        if ('publish' === $postStatus) {
            // Die automatische Aktualisierung der laufenden Nummern wird wieder aufgenommen
            $this->utilities->printSuccess('Die Berichte wurden importiert');
            $this->utilities->printInfo('Metadaten werden aktualisiert ...');
            flush();
            $this->data->resumeAutoSequenceNumbers();
            foreach (array_keys($yearsImported) as $year) {
                $this->data->updateSequenceNumbers(strval($year));
            }
        }

        $this->utilities->printSuccess('Der Import ist abgeschlossen');
        echo '<a href="edit.php?post_type=einsatz">Zu den Einsatzberichten</a>';
    }

    /**
     * @param array $insertArgs
     * @param string $dateTimeFormat
     * @param string $postStatus
     * @param DateTime $alarmzeit
     * @throws Exception
     */
    public function prepareArgsForInsertPost(&$insertArgs, $dateTimeFormat, $postStatus, $alarmzeit)
    {
        // Datum des Einsatzes prüfen
        if (false === $alarmzeit) {
            throw new Exception(sprintf(
                'Die Alarmzeit %s konnte mit dem angegebenen Format %s nicht eingelesen werden',
                esc_html($insertArgs['post_date']),
                esc_html($dateTimeFormat)
            ));
        }

        $insertArgs['post_date'] = $alarmzeit->format('Y-m-d H:i');
        $insertArgs['post_date_gmt'] = get_gmt_from_date($insertArgs['post_date']);

        // Einsatzende korrekt formatieren
        if (array_key_exists('einsatz_einsatzende', $insertArgs['meta_input']) &&
            !empty($insertArgs['meta_input']['einsatz_einsatzende'])
        ) {
            $endDate = DateTime::createFromFormat($dateTimeFormat, $insertArgs['meta_input']['einsatz_einsatzende']);
            if (false === $endDate) {
                throw new Exception(sprintf(
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

        // Mannschaftsstärke validieren
        // NEEDS_WP4.6 wird durch den Einsatz von register_meta hinfällig
        if (array_key_exists('einsatz_mannschaft', $insertArgs['meta_input'])) {
            $insertArgs['meta_input']['einsatz_mannschaft'] = sanitize_text_field($insertArgs['meta_input']['einsatz_mannschaft']);
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
        if ($this->options->isAutoIncidentNumbers()) {
            $this->utilities->printInfo('Einsatznummern können nur importiert werden, wenn die automatische Verwaltung deaktiviert ist.');

            $unmatchableFields[] = 'einsatz_incidentNumber';
        }

        echo '<form method="post">';
        wp_nonce_field($parsedArgs['nonce_action']);
        echo '<input type="hidden" name="aktion" value="' . $parsedArgs['action_value'] . '" />';
        echo '<table class="evw_match_fields"><tr><th>';
        printf('Feld in %s', $source->getName());
        echo '</th><th>' . 'Feld in Einsatzverwaltung' . '</th></tr><tbody>';
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
     * @param array $metaFields
     */
    public function setMetaFields($metaFields)
    {
        $this->metaFields = $metaFields;
    }

    /**
     * @param array $postFields
     */
    public function setPostFields($postFields)
    {
        $this->postFields = $postFields;
    }

    /**
     * @param array $taxonomies
     */
    public function setTaxonomies($taxonomies)
    {
        $this->taxonomies = $taxonomies;
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
        if ($this->options->isAutoIncidentNumbers()) {
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
