<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Utilities;

/**
 * Abstraktion für Importquellen
 */
abstract class AbstractSource
{
    protected $autoMatchFields = array();
    protected $internalFields = array('post_name');
    protected $problematicFields = array();

    /**
     * Gibt ein Auswahlfeld zur Zuordnung der Felder in Einsatzverwaltung aus
     *
     * @param string $name Name des Dropdownfelds im Formular
     * @param string $selected Wert der ausgewählten Option
     */
    private function dropdownEigeneFelder($name, $selected = '-')
    {
        $fields = IncidentReport::getFields();

        // Felder, die automatisch beschrieben werden, nicht zur Auswahl stellen
        foreach ($this->getUnmatchableFields() as $ownField) {
            unset($fields[$ownField]);
        }

        // Sortieren und ausgeben
        asort($fields);
        $string = '<select name="' . $name . '">';
        $string .= '<option value="-"' . ($selected == '-' ? ' selected="selected"' : '') . '>';
        $string .= __('nicht importieren', 'einsatzverwaltung') . '</option>';
        foreach ($fields as $slug => $fieldName) {
            $string .= '<option value="' . $slug . '"' . ($selected == $slug ? ' selected="selected"' : '') . '>';
            $string .= $fieldName . '</option>';
        }
        $string .= '</select>';

        echo $string;
    }

    /**
     * Gibt die Beschreibung der Importquelle zurück
     *
     * @return string Beschreibung der Importquelle
     */
    abstract public function getDescription();

    /**
     * @param $action
     * @return string
     */
    public function getActionAttribute($action)
    {
        return $this->getIdentifier() . ':' . $action;
    }

    /**
     * Gibt den eindeutigen Bezeichner der Importquelle zurück
     *
     * @return string Eindeutiger Bezeichner der Importquelle
     */
    abstract public function getIdentifier();

    /**
     * Gibt den Wert für das name-Attribut eines Formularelements zurück
     *
     * @param string $field Bezeichner des Felds
     * @return string Eindeutiger Name bestehend aus Bezeichnern der Importquelle und des Felds
     */
    public function getInputName($field)
    {
        return $this->getIdentifier() . '-' . $field;
    }

    /**
     * @param array $sourceFields Felder der Importquelle
     * @param array $ownFields Felder der Einsatzverwaltung
     *
     * @return array
     */
    protected function getMapping($sourceFields, $ownFields)
    {
        $mapping = array();
        foreach ($sourceFields as $sourceField) {
            $index = $this->getInputName(strtolower($sourceField));
            if (array_key_exists($index, $_POST)) {
                $ownField = $_POST[$index];
                if (!empty($ownField) && is_string($ownField) && $ownField != '-') {
                    if (array_key_exists($ownField, $ownFields)) {
                        $mapping[$sourceField] = $ownField;
                    } else {
                        Utilities::printWarning("Unbekanntes Feld: $ownField");
                    }
                }
            }
        }
        foreach ($this->autoMatchFields as $sourceFieldAuto => $ownFieldAuto) {
            $mapping[$sourceFieldAuto] = $ownFieldAuto;
        }
        return $mapping;
    }

    /**
     * Gibt den Namen der Importquelle zurück
     *
     * @return string Name der Importquelle
     */
    abstract public function getName();

    /**
     * @return array Felder, die nicht als Importziel angeboten werden sollen
     */
    private function getUnmatchableFields()
    {
        return array_merge(array_values($this->autoMatchFields), $this->internalFields);
    }

    /**
     * Prüft, ob das Mapping stimmig ist und gibt Warnungen oder Fehlermeldungen aus
     *
     * @param array $mapping Das zu prüfende Mapping
     *
     * @return bool True bei bestandener Prüfung, false bei Unstimmigkeiten
     */
    protected function validateMapping($mapping)
    {
        $valid = true;
        foreach (array_count_values($mapping) as $ownField => $count) {
            if ($count > 1) {
                Utilities::printError(sprintf(
                    'Feld %s kann nicht f&uuml;r mehr als ein zu importierendes Feld als Ziel angegeben werden',
                    IncidentReport::getFieldLabel($ownField)
                ));
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Gibt das Formular für die Zuordnung zwischen zu importieren Feldern und denen von Einsatzverwaltung aus
     *
     * @param array $args {
     *     @type array  $felder            Liste der Feldnamen aus der Importquelle
     *     @type array  $mapping           Zuordnung von zu importieren Feldern auf Einsatzverwaltungsfelder
     *     @type string $nonce_action      Wert der Nonce
     *     @type string $action_value      Wert der action-Variable
     *     @type string submit_button_text Beschriftung für den Button unter dem Formular
     * }
     */
    protected function renderMatchForm($args)
    {
        $defaults = array(
            'fields' => array(),
            'mapping' => array(),
            'nonce_action' => '',
            'action_value' => '',
            'submit_button_text' => __('Import starten', 'einsatzverwaltung')
        );

        $parsedArgs = wp_parse_args($args, $defaults);

        echo '<form method="post">';
        wp_nonce_field($parsedArgs['nonce_action']);
        echo '<input type="hidden" name="aktion" value="' . $parsedArgs['action_value'] . '" />';
        echo '<table class="evw_match_fields"><tr><th>';
        printf(_x('Feld in %s', 'einsatzverwaltung'), $this->getName());
        echo '</th><th>' . __('Feld in Einsatzverwaltung', 'einsatzverwaltung') . '</th></tr><tbody>';
        foreach ($parsedArgs['fields'] as $field) {
            echo '<tr><td><strong>' . $field . '</strong></td><td>';
            if (array_key_exists($field, $this->autoMatchFields)) {
                _e('wird automatisch zugeordnet', 'einsatzverwaltung');
            } elseif (in_array($field, $this->problematicFields)) {
                    Utilities::printWarning(sprintf('Probleme mit Feld %s, siehe Analyse', $field));
            } else {
                $selected = '-';
                if (!empty($parsedArgs['mapping']) &&
                    array_key_exists($field, $parsedArgs['mapping']) &&
                    !empty($parsedArgs['mapping'][$field])
                ) {
                    $selected = $parsedArgs['mapping'][$field];
                }
                $this->dropdownEigeneFelder($this->getInputName(strtolower($field)), $selected);
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        submit_button($parsedArgs['submit_button_text']);
        echo '</form>';
    }

    /**
     * @param $action
     * @return mixed
     */
    abstract public function renderPage($action);
}
