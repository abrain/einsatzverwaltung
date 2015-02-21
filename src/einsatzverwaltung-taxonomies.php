<?php
namespace abrain\Einsatzverwaltung;

/**
 * Kümmert sich um die an Taxonomien angehängten Zusatzfelder
 */
class Taxonomies
{
    private static $taxonomies = array(
        'exteinsatzmittel' => array('url'),
        'fahrzeug' => array('fahrzeugpid')
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('exteinsatzmittel_add_form_fields', array($this, 'addFieldsExteinsatzmittelNew'));
        add_action('exteinsatzmittel_edit_form_fields', array($this, 'addFieldsExteinsatzmittelEdit'));
        add_action('fahrzeug_add_form_fields', array($this, 'addFieldsFahrzeugNew'));
        add_action('fahrzeug_edit_form_fields', array($this, 'addFieldsFahrzeugEdit'));
        add_action('edited_term', array($this, 'saveTerm'), 10, 3);
        add_action('created_term', array($this, 'saveTerm'), 10, 3);
        add_action('delete_term', array($this, 'deleteTerm'), 10, 4);
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
        echo '<td><input name="url" id="url" type="text" value="'.$exteinsatzmittel_url.'" size="40" />';
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
        wp_dropdown_pages(
            array (
                'name' => 'fahrzeugpid',
                'show_option_none' => '- keine -',
                'option_none_value' => ''
            )
        );
        echo '<p>Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.</p></div>';
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
        wp_dropdown_pages(
            array (
                'selected' => $fahrzeug_pid,
                'name' => 'fahrzeugpid',
                'show_option_none' => '- keine -',
                'option_none_value' => ''
            )
        );
        echo '<p class="description">Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.</p></td></tr>';
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
                $value = $_POST[$field];
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
}
