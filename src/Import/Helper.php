<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ToolEinsatznummernReparieren;
use abrain\Einsatzverwaltung\Utilities;
use DateTime;

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
     * @var Core
     */
    private $core;

    /**
     * Helper constructor.
     * @param Utilities $utilities
     * @param Core $core
     */
    public function __construct(Utilities $utilities, Core $core)
    {
        $this->utilities = $utilities;
        $this->core = $core;
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
        $string .= __('nicht importieren', 'einsatzverwaltung') . '</option>';
        foreach ($fields as $slug => $fieldProperties) {
            $string .= '<option value="' . $slug . '"' . ($parsedArgs['selected'] == $slug ? ' selected="selected"' : '') . '>';
            $string .= $fieldProperties['label'] . '</option>';
        }
        $string .= '</select>';

        echo $string;
    }

    /**
     * Importiert Einsätze aus der wp-einsatz-Tabelle
     *
     * @param AbstractSource $source
     * @param array $mapping Zuordnung zwischen zu importieren Feldern und denen der Einsatzverwaltung
     */
    public function import($source, $mapping)
    {
        $sourceEntries = $source->getEntries(array_keys($mapping));
        if (empty($sourceEntries)) {
            $this->utilities->printError('Die Importquelle lieferte keine Ergebnisse. Entweder sind dort keine Eins&auml;tze gespeichert oder es gab ein Problem bei der Abfrage.');
            return;
        }

        $dateFormat = $source->getDateFormat();
        $timeFormat = $source->getTimeFormat();
        if (!empty($dateFormat) && !empty($timeFormat)) {
            $dateTimeFormat = $dateFormat . ' ' . $timeFormat;
        }
        if (empty($dateTimeFormat)) {
            $dateTimeFormat = 'Y-m-d H:i';
        }

        foreach ($sourceEntries as $sourceEntry) {
            $metaValues = array();
            $insertArgs = array();
            $insertArgs['post_content'] = '';
            $insertArgs['tax_input'] = array();
            $ownTerms = IncidentReport::getTerms();
            $postFields = IncidentReport::getPostFields();

            foreach ($mapping as $sourceField => $ownField) {
                if (!empty($ownField) && is_string($ownField)) {
                    if (array_key_exists($ownField, IncidentReport::getMetaFields())) {
                        // Wert gehört in ein Metafeld
                        $metaValues[$ownField] = $sourceEntry[$sourceField];
                    } elseif (array_key_exists($ownField, $ownTerms)) {
                        // Wert gehört zu einer Taxonomie
                        if (empty($sourceEntry[$sourceField])) {
                            // Leere Terms überspringen
                            continue;
                        }
                        if (is_taxonomy_hierarchical($ownField)) {
                            // Bei hierarchischen Taxonomien muss die ID statt des Namens verwendet werden
                            $termIds = array();

                            $termNames = explode(',', $sourceEntry[$sourceField]);
                            foreach ($termNames as $termName) {
                                $termName = trim($termName);
                                $term = get_term_by('name', $termName, $ownField);

                                if ($term !== false) {
                                    // Term existiert bereits, ID verwenden
                                    $termIds[] = $term->term_id;
                                    continue;
                                }

                                // Term existiert in dieser Taxonomie noch nicht, neu anlegen
                                $newterm = wp_insert_term($termName, $ownField);
                                if (is_wp_error($newterm)) {
                                    $this->utilities->printError(
                                        sprintf(
                                            "Konnte %s '%s' nicht anlegen: %s",
                                            $ownTerms[$ownField]['label'],
                                            $termName,
                                            $newterm->get_error_message()
                                        )
                                    );
                                    continue;
                                }

                                // Anlegen erfolgreich, zurückgegebene ID verwenden
                                $termIds[] = $newterm['term_id'];
                            }

                            $insertArgs['tax_input'][$ownField] = implode(',', $termIds);
                        } else {
                            // Name kann direkt verwendet werden
                            $insertArgs['tax_input'][$ownField] = $sourceEntry[$sourceField];
                        }
                    } elseif (array_key_exists($ownField, $postFields)) {
                        // Wert gehört direkt zum Post
                        $insertArgs[$ownField] = $sourceEntry[$sourceField];
                    } elseif ($ownField == '-') {
                        $this->utilities->printWarning("Feld '$sourceField' nicht zugeordnet");
                    } else {
                        $this->utilities->printError("Feld '$ownField' unbekannt");
                    }
                } else {
                    $this->utilities->printError("Feld '$ownField' ung&uuml;ltig");
                }
            }

            // Datum des Einsatzes prüfen
            $alarmzeit = DateTime::createFromFormat($dateTimeFormat, $insertArgs['post_date']);
            if (false === $alarmzeit) {
                $this->utilities->printError(
                    sprintf(
                        'Die Alarmzeit %s konnte mit dem angegebenen Format %s nicht eingelesen werden',
                        esc_html($insertArgs['post_date']),
                        esc_html($dateTimeFormat)
                    )
                );
                continue;
            }

            $einsatzjahr = $alarmzeit->format('Y');
            $insertArgs['post_date'] = $alarmzeit->format('Y-m-d H:i');
            $insertArgs['post_date_gmt'] = get_gmt_from_date($insertArgs['post_date']);
            $metaValues['einsatz_alarmzeit'] = $insertArgs['post_date'];

            // Einsatzende korrekt formatieren
            if (array_key_exists('einsatz_einsatzende', $metaValues) && !empty($metaValues['einsatz_einsatzende'])) {
                $einsatzende = DateTime::createFromFormat($dateTimeFormat, $metaValues['einsatz_einsatzende']);
                if (false === $einsatzende) {
                    $this->utilities->printError(
                        sprintf(
                            'Das Einsatzende %s konnte mit dem angegebenen Format %s nicht eingelesen werden',
                            esc_html($metaValues['einsatz_einsatzende']),
                            esc_html($dateTimeFormat)
                        )
                    );
                    continue;
                }

                $metaValues['einsatz_einsatzende'] = $einsatzende->format('Y-m-d H:i');
            }

            $einsatznummer = $this->core->getNextEinsatznummer($einsatzjahr);
            $insertArgs['post_name'] = $einsatznummer;
            $insertArgs['post_type'] = 'einsatz';
            $insertArgs['post_status'] = 'publish';

            // Titel sicherstellen
            if (!array_key_exists('post_title', $insertArgs)) {
                $insertArgs['post_title'] = 'Einsatz';
            }
            $insertArgs['post_title'] = wp_strip_all_tags($insertArgs['post_title']);
            if (empty($insertArgs['post_title'])) {
                $insertArgs['post_title'] = 'Einsatz';
            }

            // Mannschaftsstärke validieren
            if (array_key_exists('einsatz_mannschaft', $metaValues)) {
                $metaValues['einsatz_mannschaft'] = sanitize_text_field($metaValues['einsatz_mannschaft']);
            }

            // Neuen Beitrag anlegen
            $postId = wp_insert_post($insertArgs, true);
            if (is_wp_error($postId)) {
                $this->utilities->printError('Konnte Einsatz nicht importieren: ' . $postId->get_error_message());
            } else {
                $this->utilities->printInfo('Einsatz importiert, ID ' . $postId);
                foreach ($metaValues as $mkey => $mval) {
                    update_post_meta($postId, $mkey, $mval);
                }

                // Einsatznummer prüfen
                $gespeicherteEnr = get_post_field('post_name', $postId);
                if ($gespeicherteEnr != $einsatznummer) {
                    $this->utilities->printWarning('WordPress hat diesem Einsatz nicht die vorgesehene Einsatznummer erteilt.<br>Verwendung des Werkzeugs <a href="'.admin_url('tools.php?page='.ToolEinsatznummernReparieren::EVW_TOOL_ENR_SLUG).'">Einsatznummern reparieren</a> wird empfohlen.');
                }
            }
        }

        $this->utilities->printSuccess('Der Import ist abgeschlossen');
        echo '<a href="edit.php?post_type=einsatz">Zu den Einsatzberichten</a>';
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
            'submit_button_text' => __('Import starten', 'einsatzverwaltung')
        );

        $parsedArgs = wp_parse_args($args, $defaults);
        $fields = $source->getFields();

        echo '<form method="post">';
        wp_nonce_field($parsedArgs['nonce_action']);
        echo '<input type="hidden" name="aktion" value="' . $parsedArgs['action_value'] . '" />';
        echo '<table class="evw_match_fields"><tr><th>';
        printf(__('Feld in %s', 'einsatzverwaltung'), $source->getName());
        echo '</th><th>' . __('Feld in Einsatzverwaltung', 'einsatzverwaltung') . '</th></tr><tbody>';
        foreach ($fields as $field) {
            echo '<tr><td><strong>' . $field . '</strong></td><td>';
            if (array_key_exists($field, $source->getAutoMatchFields())) {
                _e('wird automatisch zugeordnet', 'einsatzverwaltung');
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
                    'name' => $source->getInputName(strtolower($field)),
                    'selected' => $selected,
                    'unmatchableFields' => $source->getUnmatchableFields()
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
     * Prüft, ob das Mapping stimmig ist und gibt Warnungen oder Fehlermeldungen aus
     *
     * @param array $mapping Das zu prüfende Mapping
     *
     * @return bool True bei bestandener Prüfung, false bei Unstimmigkeiten
     */
    public function validateMapping($mapping)
    {
        $valid = true;

        // Pflichtfelder prüfen
        if (!in_array('post_date', $mapping)) {
            $this->utilities->printError('Pflichtfeld Alarmzeit wurde nicht zugeordnet');
            $valid = false;
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
