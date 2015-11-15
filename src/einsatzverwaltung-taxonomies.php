<?php
namespace abrain\Einsatzverwaltung;

use wpdb;

/**
 * Kümmert sich um die an Taxonomien angehängten Zusatzfelder
 */
class Taxonomies
{
    /**
     * @var Utilities
     */
    private $utilities;

    private static $taxonomies = array(
        'exteinsatzmittel' => array('url'),
        'fahrzeug' => array('fahrzeugpid', 'vehicleorder')
    );

    /**
     * Constructor
     *
     * @param Utilities $utilities
     */
    public function __construct($utilities)
    {
        $this->utilities = $utilities;
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('exteinsatzmittel_add_form_fields', array($this, 'addFieldsExteinsatzmittelNew'));
        add_action('exteinsatzmittel_edit_form_fields', array($this, 'addFieldsExteinsatzmittelEdit'));
        add_action('fahrzeug_add_form_fields', array($this, 'addFieldsFahrzeugNew'));
        add_action('fahrzeug_edit_form_fields', array($this, 'addFieldsFahrzeugEdit'));
        add_action('manage_edit-fahrzeug_columns', array($this, 'customColumnsFahrzeug'));
        add_action('manage_fahrzeug_custom_column', array($this, 'columnContentFahrzeug'), 10, 3);
        add_action('manage_edit-exteinsatzmittel_columns', array($this, 'customColumnsExteinsatzmittel'));
        add_action('manage_exteinsatzmittel_custom_column', array($this, 'columnContentExteinsatzmittel'), 10, 3);
        add_action('edited_term', array($this, 'saveTerm'), 10, 3);
        add_action('created_term', array($this, 'saveTerm'), 10, 3);
        add_action('delete_term', array($this, 'deleteTerm'), 10, 4);
        add_action('split_shared_term', array($this, 'splitSharedTerms'), 10, 4);
    }

    /**
     * Zeigt zusätzliche Felder beim Anlegen eines externen Einsatzmittels an
     */
    public function addFieldsExteinsatzmittelNew()
    {
        echo '<div class="form-field">';
        echo '<label for="tag-url">URL</label>';
        echo '<input id="tag-url" type="text" size="40" value="" name="url">';
        echo '<p>URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.</p>';
        echo '</div>';
    }

    /**
     * Zeigt zusätzliche Felder beim Bearbeiten eines externen Einsatzmittels an
     *
     * @param $tag
     */
    public function addFieldsExteinsatzmittelEdit($tag)
    {
        $exteinsatzmittel_url = self::getTermField($tag->term_id, 'exteinsatzmittel', 'url', '');

        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="url">URL</label></th>';
        echo '<td><input name="url" id="url" type="text" value="'.esc_attr($exteinsatzmittel_url).'" size="40" />';
        echo '<p class="description">URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.</p></td>';
        echo '</tr>';
    }

    /**
     * Zeigt zusätzliche Felder beim Anlegen eines Fahrzeugs an
     */
    public function addFieldsFahrzeugNew()
    {
        echo '<div class="form-field">';
        echo '<label for="tag-fahrzeugpid">Fahrzeugseite</label>';
        $this->utilities->dropdownPosts(array(
            'name' => 'fahrzeugpid',
            'post_type' => $this->getFahrzeugPostTypes()
        ));
        echo '<p>Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.</p></div>';

        echo '<div class="form-field">';
        echo '<label for="tag-vehicleorder">Reihenfolge</label>';
        echo '<input id="tag-vehicleorder" type="number" min="0" value="0" name="vehicleorder">';
        echo '<p class="description">Optionale Angabe, mit der die Anzeigereihenfolge der Fahrzeuge beeinflusst werden kann. Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0 in alphabetischer Reihenfolge.</p></div>';
    }

    /**
     * Zeigt zusätzliche Felder beim Bearbeiten eines Fahrzeugs an
     *
     * @param $tag
     */
    public function addFieldsFahrzeugEdit($tag)
    {
        $fahrzeug_pid = self::getTermField($tag->term_id, 'fahrzeug', 'fahrzeugpid', '');

        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="fahrzeugpid">Fahrzeugseite</label></th><td>';
        $this->utilities->dropdownPosts(array(
            'selected' => $fahrzeug_pid,
            'name' => 'fahrzeugpid',
            'post_type' => $this->getFahrzeugPostTypes()
        ));
        echo '<p class="description">Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.</p></td></tr>';

        $vehicleOrder = self::getTermField($tag->term_id, 'fahrzeug', 'vehicleorder', 0);
        echo '<tr class="form-field">';
        echo '<th scope="row"><label for="tag-vehicleorder">Reihenfolge</label></th><td>';
        echo '<input id="tag-vehicleorder" type="number" min="0" value="' . esc_attr($vehicleOrder) . '" name="vehicleorder">';
        echo '<p class="description">Optionale Angabe, mit der die Anzeigereihenfolge der Fahrzeuge beeinflusst werden kann. Fahrzeuge mit der kleineren Zahl werden zuerst angezeigt, anschlie&szlig;end diejenigen ohne Angabe bzw. dem Wert 0 in alphabetischer Reihenfolge.</p></td></tr>';
    }

    /**
     * Filterfunktion für die Spalten der Adminansicht der Taxonomie Fahrzeug
     *
     * @param array $columns Liste der Spaltentitel
     *
     * @return array Die gefilterte Liste
     */
    public function customColumnsFahrzeug($columns)
    {
        // Fahrzeugseite nach der Spalte 'Beschreibung' einblenden, ansonsten am Ende
        $filteredColumns = array();
        if (array_key_exists('description', $columns)) {
            foreach ($columns as $slug => $name) {
                $filteredColumns[$slug] = $name;
                if ($slug == 'description') {
                    $filteredColumns['fahrzeugpage'] = 'Fahrzeugseite';
                    $filteredColumns['vehicleorder'] = 'Reihenfolge';
                }
            }
        } else {
            $filteredColumns['fahrzeugpage'] = 'Fahrzeugseite';
            $filteredColumns['vehicleorder'] = 'Reihenfolge';
        }
        return $filteredColumns;
    }

    /**
     * Filterfunktion für den Inhalt der selbst angelegten Spalten
     *
     * @param string $string Leerer String.
     * @param string $column_name Name der Spalte
     * @param int $term_id Term ID
     *
     * @return string Inhalt der Spalte
     */
    public function columnContentFahrzeug($string, $column_name, $term_id)
    {
        switch ($column_name) {
            case 'fahrzeugpage':
                $fahrzeugpid = self::getTermField($term_id, 'fahrzeug', 'fahrzeugpid');
                if (false === $fahrzeugpid) {
                    return '&nbsp;';
                } else {
                    $url = get_permalink($fahrzeugpid);
                    $title = get_the_title($fahrzeugpid);
                    return sprintf(
                        '<a href="%1$s" title="&quot;%2$s&quot; ansehen" target="_blank">%3$s</a>',
                        esc_attr($url),
                        esc_attr($title),
                        esc_html($title)
                    );
                }
                break;
            case 'vehicleorder':
                $vehicleOrder = self::getTermField($term_id, 'fahrzeug', 'vehicleorder');
                return (empty($vehicleOrder) ? '&nbsp;' : esc_html($vehicleOrder));
                break;
            default:
                return '&nbsp;';
        }
    }

    /**
     * Filterfunktion für die Spalten der Adminansicht der Taxonomie Externe Einsatzmittel
     *
     * @param array $columns Liste der Spaltentitel
     *
     * @return array Die gefilterte Liste
     */
    public function customColumnsExteinsatzmittel($columns)
    {
        // URL nach der Spalte 'Beschreibung' einblenden, ansonsten am Ende
        $filteredColumns = array();
        if (array_key_exists('description', $columns)) {
            foreach ($columns as $slug => $name) {
                $filteredColumns[$slug] = $name;
                if ($slug == 'description') {
                    $filteredColumns['exturl'] = 'URL';
                }
            }
        } else {
            $filteredColumns['exturl'] = 'URL';
        }
        return $filteredColumns;
    }

    /**
     * Filterfunktion für den Inhalt der selbst angelegten Spalten
     *
     * @param string $string Leerer String.
     * @param string $column_name Name der Spalte
     * @param int $term_id Term ID
     *
     * @return string Inhalt der Spalte
     */
    public function columnContentExteinsatzmittel($string, $column_name, $term_id)
    {
        $url = self::getTermField($term_id, 'exteinsatzmittel', 'url');
        if (false === $url) {
            return '&nbsp;';
        } else {
            return sprintf(
                '<a href="%1$s" title="%1$s besuchen" target="_blank">%2$s</a>',
                esc_attr($url),
                esc_html($url)
            );
        }
    }

    /**
     * Speichert zusätzliche Infos zu Terms als options ab
     *
     * @param int $term_id Term ID
     * @param int $tt_id Term taxonomy ID
     * @param string $taxonomy Taxonomy slug
     */
    public function saveTerm($term_id, $tt_id, $taxonomy)
    {
        $evw_taxonomies = $this->getTaxonomies();

        if (!array_key_exists($taxonomy, $evw_taxonomies) || !is_array($evw_taxonomies[$taxonomy])) {
            return;
        }

        foreach ($evw_taxonomies[$taxonomy] as $field) {
            if (isset($field) && !empty($field) && isset($_POST[$field])) {
                $value = $_POST[$field]; //FIXME sanitize
                $key = self::getTermOptionKey($term_id, $taxonomy, $field);
                if (empty($value)) {
                    delete_option($key);
                } else {
                    update_option($key, $value);
                }
            }
        }
    }

    /**
     * Löscht zusätzlich angelegte Felder nach dem Löschen eines Terms
     *
     * @param int $term_id Term ID
     * @param int $tt_id Term taxonomy ID
     * @param string $taxonomy Taxonomy slug
     * @param mixed $deleted_term Kopie des bereits gelöschten Terms
     */
    public function deleteTerm($term_id, $tt_id, $taxonomy, $deleted_term)
    {
        if (!isset($taxonomy)) {
            return;
        }

        $evw_taxonomies = $this->getTaxonomies();

        if (!array_key_exists($taxonomy, $evw_taxonomies) || !is_array($evw_taxonomies[$taxonomy])) {
            return;
        }

        foreach ($evw_taxonomies[$taxonomy] as $field) {
            if (isset($field) && !empty($field)) {
                delete_option(self::getTermOptionKey($term_id, $taxonomy, $field));
            }
        }
    }

    /**
     * Liefert den Wert eines zusätzlich angelegten Feldes zurück
     *
     * @param $term_id
     * @param $taxonomy
     * @param $field
     * @param mixed $default
     *
     * @return mixed|void
     */
    public static function getTermField($term_id, $taxonomy, $field, $default = false)
    {
        $key = self::getTermOptionKey($term_id, $taxonomy, $field);
        return get_option($key, $default);
    }

    /**
     * Liefert den Schlüssel eines zusätzlich angelegten Feldes zurück
     *
     * @param $term_id
     * @param $taxonomy
     * @param $field
     *
     * @return string
     */
    private static function getTermOptionKey($term_id, $taxonomy, $field)
    {
        return 'evw_tax_'.$taxonomy.'_'.$term_id.'_'.$field;
    }

    /**
     * @return array
     */
    public static function getTaxonomies()
    {
        return self::$taxonomies;
    }

    /**
     * Terms, die von mehreren Taxonimien genutzt werden, bekommen ab WordPress 4.2 verschiedene IDs. Bestehende doppelt
     * genutzte Terms werden beim erneuten Speichern in zwei Terms mit dem gleichen Namen aber verschiedenen IDs
     * aufgespalten. Danach wird diese Methode über den Filter split_shared_term aufgerufen, um Einträge in der
     * Datenbank, die IDs von Terms enthalten, zu aktualisieren.
     * Siehe auch https://make.wordpress.org/core/2015/02/16/taxonomy-term-splitting-in-4-2-a-developer-guide/
     *
     * @param $old_term_id
     * @param $new_term_id
     * @param $term_taxonomy_id
     * @param $taxonomy
     */
    public function splitSharedTerms($old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy)
    {
        if (!array_key_exists($taxonomy, self::$taxonomies)) {
            return;
        }

        error_log("split_shared_term for $taxonomy: ttid $term_taxonomy_id from $old_term_id to $new_term_id");

        global $wpdb; /** @var wpdb $wpdb */
        $fields = self::$taxonomies[$taxonomy];

        foreach ($fields as $field) {
            $oldKey = self::getTermOptionKey($old_term_id, $taxonomy, $field);
            $newKey = self::getTermOptionKey($new_term_id, $taxonomy, $field);
            $result = $wpdb->update($wpdb->options, array('option_name' => $newKey), array('option_name' => $oldKey));
            if (false === $result) {
                error_log('Fehler beim Termsplit ' . $taxonomy . ': ' . $wpdb->last_error);
            }
        }
    }

    /**
     * @since 1.0.0
     *
     * @return array
     */
    private function getFahrzeugPostTypes()
    {
        $postTypes = get_post_types(array('public' => true));

        return array_filter(
            $postTypes,
            function ($value) {
                return !in_array($value, array('einsatz', 'attachment'));
            }
        );
    }
}
