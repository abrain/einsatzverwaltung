<?php

global $evw_taxonomies;
$evw_taxonomies = array(
    'exteinsatzmittel' => array('url'),
    'fahrzeug' => array('fahrzeugpid')
);


/**
 * Zeigt zusätzliche Felder beim Anlegen eines externen Einsatzmittels an
 */
function einsatzverwaltung_exteinsatzmittel_additional_fields_add()
{
    echo '<div class="form-field">';
    echo '<label for="tag-url">URL</label>';
    echo '<input id="tag-url" type="text" size="40" value="" name="url">';
    echo '<p>URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.</p>';
    echo '</div>';
}
add_action('exteinsatzmittel_add_form_fields', 'einsatzverwaltung_exteinsatzmittel_additional_fields_add');


/**
 * Zeigt zusätzliche Felder beim Bearbeiten eines externen Einsatzmittels an
 *
 * @param $tag
 */
function einsatzverwaltung_exteinsatzmittel_additional_fields_edit($tag)
{
    $exteinsatzmittel_url = get_option(einsatzverwaltung_get_term_option_key($tag->term_id, 'exteinsatzmittel', 'url'), '');

    echo '<tr class="form-field">';
    echo '<th scope="row"><label for="url">URL</label></th>';
    echo '<td><input name="url" id="url" type="text" value="'.$exteinsatzmittel_url.'" size="40" />';
    echo '<p class="description">URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.</p></td>';
    echo '</tr>';
}
add_action('exteinsatzmittel_edit_form_fields', 'einsatzverwaltung_exteinsatzmittel_additional_fields_edit');


/**
 * Zeigt zusätzliche Felder beim Anlegen eines Fahrzeugs an
 */
function einsatzverwaltung_fahrzeug_additional_fields_add()
{
    echo '<div class="form-field">';
    echo '<label for="tag-fahrzeugpid">Fahrzeugseite</label>';
    wp_dropdown_pages(array ('name' => 'fahrzeugpid', 'show_option_none' => '- keine -', 'option_none_value' => ''));
    echo '<p>Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.</p></div>';
}
add_action('fahrzeug_add_form_fields', 'einsatzverwaltung_fahrzeug_additional_fields_add');


/**
 * Zeigt zusätzliche Felder beim Bearbeiten eines Fahrzeugs an
 *
 * @param $tag
 */
function einsatzverwaltung_fahrzeug_additional_fields_edit($tag)
{
    $fahrzeug_pid = get_option(einsatzverwaltung_get_term_option_key($tag->term_id, 'fahrzeug', 'fahrzeugpid'), '');

    echo '<tr class="form-field">';
    echo '<th scope="row"><label for="fahrzeugpid">Fahrzeugseite</label></th><td>';
    wp_dropdown_pages(array ('selected' => $fahrzeug_pid, 'name' => 'fahrzeugpid', 'show_option_none' => '- keine -', 'option_none_value' => ''));
    echo '<p class="description">Seite mit mehr Informationen &uuml;ber das Fahrzeug. Wird in Einsatzberichten mit diesem Fahrzeug verlinkt.</p></td></tr>';
}
add_action('fahrzeug_edit_form_fields', 'einsatzverwaltung_fahrzeug_additional_fields_edit');


/**
 * Speichert zusätzliche Infos zu Terms als options ab
 *
 * @param $term_id
 * @param $tt_id
 * @param $taxonomy
 */
function einsatzverwaltung_save_term($term_id, $tt_id, $taxonomy)
{
    global $evw_taxonomies;

    if (!array_key_exists($taxonomy, $evw_taxonomies) || !is_array($evw_taxonomies[$taxonomy])) {
        return;
    }

    foreach ($evw_taxonomies[$taxonomy] as $field) {
        if (isset($field) && !empty($field) && isset($_POST[$field])) {
            $value = $_POST[$field];
            if (empty($value)) {
                delete_option(einsatzverwaltung_get_term_option_key($term_id, $taxonomy, $field));
            } else {
                update_option(einsatzverwaltung_get_term_option_key($term_id, $taxonomy, $field), $value);
            }
        }
    }

}
add_action('edited_term', 'einsatzverwaltung_save_term', 10, 3);
add_action('created_term', 'einsatzverwaltung_save_term', 10, 3);


/**
 * Löscht zusätzlich angelegte Felder beim Löschen eines Terms
 *
 * @param $term_id
 * @param $tt_id
 * @param $taxonomy
 * @param $deleted_term
 */
function einsatzverwaltung_delete_term($term_id, $tt_id, $taxonomy, $deleted_term)
{
    if (!isset($taxonomy)) {
        return;
    }

    global $evw_taxonomies;
    if (!array_key_exists($taxonomy, $evw_taxonomies) || !is_array($evw_taxonomies[$taxonomy])) {
        return;
    }

    foreach ($evw_taxonomies[$taxonomy] as $field) {
        if (isset($field) && !empty($field)) {
            delete_option(einsatzverwaltung_get_term_option_key($term_id, $taxonomy, $field));
        }
    }
}
add_action('delete_term', 'einsatzverwaltung_delete_term', 10, 4);


/**
 * Liefert den Wert eines zusätzlich angelegten Feldes zurück
 *
 * @param $term_id
 * @param $taxonomy
 * @param $field
 *
 * @return mixed|void
 */
function einsatzverwaltung_get_term_field($term_id, $taxonomy, $field)
{
    return get_option(einsatzverwaltung_get_term_option_key($term_id, $taxonomy, $field));
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
function einsatzverwaltung_get_term_option_key($term_id, $taxonomy, $field)
{
    return 'evw_tax_'.$taxonomy.'_'.$term_id.'_'.$field;
}
