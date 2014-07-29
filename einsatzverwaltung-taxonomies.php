<?php

global $evw_taxonomies;
$evw_taxonomies = array(
    'exteinsatzmittel' => array('url')
);

/**
 * Zeigt zusätzliche Felder beim Anlegen eines externen Einsatzmittels an
 */
function einsatzverwaltung_exteinsatzmittel_additional_fields_add() {
    echo '<div class="form-field">';
    echo '<label for="tag-url">URL</label>';
    echo '<input id="tag-url" type="text" size="40" value="" name="url">';
    echo '<p>URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.</p>';
    echo '</div>';
}
add_action('exteinsatzmittel_add_form_fields', 'einsatzverwaltung_exteinsatzmittel_additional_fields_add');


/**
 * Zeigt zusätzliche Felder beim Bearbeiten eines externen Einsatzmittels an
 */
function einsatzverwaltung_exteinsatzmittel_additional_fields_edit($tag) {
    $exteinsatzmittel_url = get_option('evw_tax_exteinsatzmittel_'.$tag->term_id.'_url', '');
    
    echo '<tr class="form-field">';
    echo '<th scope="row"><label for="url">URL</label></th>';
    echo '<td><input name="url" id="url" type="text" value="'.$exteinsatzmittel_url.'" size="40" />';
    echo '<p class="description">URL zu mehr Informationen &uuml;ber ein externes Einsatzmittel, beispielsweise dessen Webseite.</p></td>';
    echo '</tr>';
}
add_action('exteinsatzmittel_edit_form_fields', 'einsatzverwaltung_exteinsatzmittel_additional_fields_edit');


/**
 * Speichert zusätzliche Infos zu Terms als options ab
 */
function einsatzverwaltung_save_term($term_id, $tt_id, $taxonomy) {
    global $evw_taxonomies;
    
    if(!array_key_exists($taxonomy, $evw_taxonomies) || !is_array($evw_taxonomies[$taxonomy])) {
        return;
    }
    
    foreach($evw_taxonomies[$taxonomy] as $field) {
        if(isset($field) && !empty($field) && isset($_POST[$field])) {
            $value = $_POST[$field];
            update_option('evw_tax_'.$taxonomy.'_'.$term_id.'_'.$field, $value);
        }
    }
    
}
add_action('edited_term', 'einsatzverwaltung_save_term', 10, 3);
add_action('created_term', 'einsatzverwaltung_save_term', 10, 3);


/**
 * Löscht zusätzlich angelegte Felder beim Löschen eines Terms
 */
function einsatzverwaltung_delete_term($term_id, $tt_id, $taxonomy, $deleted_term) {
    if(!isset($taxonomy)) {
        return;
    }
    
    global $evw_taxonomies;
    $taxonomy = $_POST['taxonomy'];
    if(!array_key_exists($taxonomy, $evw_taxonomies) || !is_array($evw_taxonomies[$taxonomy])) {
        return;
    }
    
    foreach($evw_taxonomies[$taxonomy] as $field) {
        if(isset($field) && !empty($field)) {
            delete_option('evw_tax_'.$taxonomy.'_'.$term_id.'_'.$field);
        }
    }
}
add_action('delete_term', 'einsatzverwaltung_delete_term', 10, 4);

?>