<?php
use abrain\Einsatzverwaltung\Utilities;

/**
 * Fügt die Metabox zum Bearbeiten der Einsatzdetails ein
 */
function einsatzverwaltung_add_einsatzdetails_meta_box()
{
    add_meta_box(
        'einsatzverwaltung_meta_box',
        'Einsatzdetails',
        'einsatzverwaltung_display_meta_box',
        'einsatz',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_einsatz', 'einsatzverwaltung_add_einsatzdetails_meta_box');


/**
 * Zusätzliche Skripte im Admin-Bereich einbinden
 *
 * @param string $hook Name der aufgerufenen Datei
 */
function einsatzverwaltung_enqueue_edit_scripts($hook)
{
    if ('post.php' == $hook || 'post-new.php' == $hook) {
        // Nur auf der Bearbeitungsseite anzeigen
        wp_enqueue_script(
            'einsatzverwaltung-edit-script',
            EINSATZVERWALTUNG__SCRIPT_URL . 'einsatzverwaltung-edit.js',
            array('jquery', 'jquery-ui-autocomplete')
        );
        wp_enqueue_style(
            'einsatzverwaltung-edit',
            EINSATZVERWALTUNG__STYLE_URL . 'style-edit.css'
        );
    }

    wp_enqueue_style(
        'einsatzverwaltung-fontawesome',
        EINSATZVERWALTUNG__PLUGIN_URL . 'font-awesome/css/font-awesome.min.css'
    );
    wp_enqueue_style(
        'einsatzverwaltung-admin',
        EINSATZVERWALTUNG__STYLE_URL . 'style-admin.css'
    );
}
add_action('admin_enqueue_scripts', 'einsatzverwaltung_enqueue_edit_scripts');


/**
 * Inhalt der Metabox zum Bearbeiten der Einsatzdetails
 *
 * @param WP_Post $post Das Post-Objekt des aktuell bearbeiteten Einsatzberichts
 */
function einsatzverwaltung_display_meta_box($post)
{
    // Use nonce for verification
    wp_nonce_field('save_einsatz_details', 'einsatzverwaltung_nonce');

    // The actual fields for data entry
    // Use get_post_meta to retrieve an existing value from the database and use the value for the form
    $nummer = get_post_field('post_name', $post->ID);
    $alarmzeit = get_post_meta($post->ID, $key = 'einsatz_alarmzeit', $single = true);
    $einsatzende = get_post_meta($post->ID, $key = 'einsatz_einsatzende', $single = true);
    $einsatzort = get_post_meta($post->ID, $key = 'einsatz_einsatzort', $single = true);
    $einsatzleiter = get_post_meta($post->ID, $key = 'einsatz_einsatzleiter', $single = true);
    $fehlalarm = get_post_meta($post->ID, $key = 'einsatz_fehlalarm', $single = true);
    $mannschaftsstaerke = get_post_meta($post->ID, $key = 'einsatz_mannschaft', $single = true);

    $names = einsatzverwaltung_get_einsatzleiter();
    echo '<input type="hidden" id="einsatzleiter_used_values" value="' . implode(',', $names) . '" />';

    echo '<table><tbody>';

    echo '<tr><td><label for="einsatzverwaltung_nummer">' . __("Einsatznummer", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_nummer" name="einsatzverwaltung_nummer" value="'.esc_attr($nummer).'" size="10" placeholder="'.einsatzverwaltung_get_next_einsatznummer(date('Y')).'" /></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_alarmzeit">'. __("Alarmzeit", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_alarmzeit" name="einsatzverwaltung_alarmzeit" value="'.esc_attr($alarmzeit).'" size="20" placeholder="JJJJ-MM-TT hh:mm" />&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_alarmzeit_hint"></span></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_einsatzende">'. __("Einsatzende", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzende" name="einsatzverwaltung_einsatzende" value="'.esc_attr($einsatzende).'" size="20" placeholder="JJJJ-MM-TT hh:mm" />&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_einsatzende_hint"></span></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_fehlalarm">'. __("Fehlalarm", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="checkbox" id="einsatzverwaltung_fehlalarm" name="einsatzverwaltung_fehlalarm" value="1" ' . Utilities::checked($fehlalarm) . '/></td></tr>';

    echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_einsatzort">'. __("Einsatzort", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzort" name="einsatzverwaltung_einsatzort" value="'.esc_attr($einsatzort).'" size="20" /></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_einsatzleiter">'. __("Einsatzleiter", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_einsatzleiter" name="einsatzverwaltung_einsatzleiter" value="'.esc_attr($einsatzleiter).'" size="20" /></td></tr>';

    echo '<tr><td><label for="einsatzverwaltung_mannschaft">'. __("Mannschaftsst&auml;rke", 'einsatzverwaltung') . '</label></td>';
    echo '<td><input type="text" id="einsatzverwaltung_mannschaft" name="einsatzverwaltung_mannschaft" value="'.esc_attr($mannschaftsstaerke).'" size="20" /></td></tr>';

    echo '</tbody></table>';
}


/**
 * Zeigt die Metabox für die Einsatzart
 *
 * @param WP_Post $post Post-Object
 */
function einsatzverwaltung_display_einsatzart_metabox($post)
{
    $einsatzart = einsatzverwaltung_get_einsatzart($post->ID);
    einsatzverwaltung_dropdown_einsatzart($einsatzart ? $einsatzart->term_id : 0);
}


/**
 * Legt fest, welche Spalten bei der Übersicht der Einsatzberichte im
 * Adminbereich angezeigt werden
 *
 * @param array $columns
 *
 * @return array
 */
function einsatzverwaltung_edit_einsatz_columns($columns)
{
    unset($columns['author']);
    unset($columns['date']);
    $columns['title'] = __('Einsatzbericht', 'einsatzverwaltung');
    $columns['e_nummer'] = __('Nummer', 'einsatzverwaltung');
    $columns['e_alarmzeit'] = __('Alarmzeit', 'einsatzverwaltung');
    $columns['e_einsatzende'] = __('Einsatzende', 'einsatzverwaltung');
    $columns['e_art'] = __('Art', 'einsatzverwaltung');
    $columns['e_fzg'] = __('Fahrzeuge', 'einsatzverwaltung');

    return $columns;
}
add_filter('manage_edit-einsatz_columns', 'einsatzverwaltung_edit_einsatz_columns') ;


/**
 * Liefert den Inhalt für die jeweiligen Spalten bei der Übersicht der
 * Einsatzberichte im Adminbereich
 *
 * @param string $column
 * @param int $post_id
 */
function einsatzverwaltung_manage_einsatz_columns($column, $post_id)
{
    global $post;

    switch($column) {

        case 'e_nummer':
            $einsatz_nummer = get_post_field('post_name', $post_id);

            if (empty($einsatz_nummer)) {
                echo '-';
            } else {
                echo $einsatz_nummer;
            }

            break;

        case 'e_einsatzende':
            $einsatz_einsatzende = get_post_meta($post_id, 'einsatz_einsatzende', true);

            if (empty($einsatz_einsatzende)) {
                echo '-';
            } else {
                $timestamp = strtotime($einsatz_einsatzende);
                echo date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
            }

            break;

        case 'e_alarmzeit':
            $einsatz_alarmzeit = get_post_meta($post_id, 'einsatz_alarmzeit', true);

            if (empty($einsatz_alarmzeit)) {
                echo '-';
            } else {
                $timestamp = strtotime($einsatz_alarmzeit);
                echo date("d.m.Y", $timestamp)."<br>".date("H:i", $timestamp);
            }

            break;

        case 'e_art':

            $term = einsatzverwaltung_get_einsatzart($post_id);
            if ($term) {
                $url = esc_url(add_query_arg(array('post_type' => $post->post_type, 'einsatzart' => $term->slug), 'edit.php'));
                $text = esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'einsatzart', 'display'));
                echo '<a href="' . $url . '">' . $text . '</a>';
            } else {
                echo '-';
            }

            break;

        case 'e_fzg':

            $terms = get_the_terms($post_id, 'fahrzeug');

            if (!empty($terms)) {
                $out = array();
                foreach ($terms as $term) {
                    $url = esc_url(add_query_arg(array('post_type' => $post->post_type, 'fahrzeug' => $term->slug), 'edit.php'));
                    $text = esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'fahrzeug', 'display'));
                    $out[] = '<a href="' . $url . '">' . $text . '</a>';
                }

                echo join(', ', $out);
            } else {
                echo '-';
            }

            break;

        default:
            break;
    }
}
add_action('manage_einsatz_posts_custom_column', 'einsatzverwaltung_manage_einsatz_columns', 10, 2);


/**
 * Zahl der Einsatzberichte im Dashboard anzeigen
 *
 * @param array $items
 *
 * @return array
 */
function einsatzverwaltung_add_einsatzberichte_to_dashboard($items)
{
    $postType = 'einsatz';
    if (post_type_exists($postType)) {
        $pt_info = get_post_type_object($postType); // get a specific CPT's details
        $num_posts = wp_count_posts($postType); // retrieve number of posts associated with this CPT
        $num = number_format_i18n($num_posts->publish); // number of published posts for this CPT
        $text = _n($pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish)); // singular/plural text label for CPT
        echo '<li class="'.$pt_info->name.'-count page-count">';
        if (current_user_can('edit_einsatzberichte')) {
            echo '<a href="edit.php?post_type='.$postType.'">'.$num.' '.$text.'</a>';
        } else {
            echo '<span>'.$num.' '.$text.'</span>';
        }
        echo '</li>';
    }

    return $items;
}
add_action('dashboard_glance_items', 'einsatzverwaltung_add_einsatzberichte_to_dashboard'); // since WP 3.8


/**
 * Zahl der Einsatzberichte im Dashboard anzeigen (für WordPress 3.7 und älter)
 */
function einsatzverwaltung_add_einsatzberichte_to_dashboard_legacy()
{
    if (post_type_exists('einsatz')) {
        $postType = 'einsatz';
        $pt_info = get_post_type_object($postType); // get a specific CPT's details
        $num_posts = wp_count_posts($postType); // retrieve number of posts associated with this CPT
        $num = number_format_i18n($num_posts->publish); // number of published posts for this CPT
        $text = _n($pt_info->labels->singular_name, $pt_info->labels->name, intval($num_posts->publish)); // singular/plural text label for CPT
        echo '<tr><td class="first b">';
        if (current_user_can('edit_einsatzberichte')) {
            echo '<a href="edit.php?post_type='.$postType.'">'.$num.'</a>';
        } else {
            echo $num;
        }
        echo '</td><td class="t">';
        if (current_user_can('edit_einsatzberichte')) {
            echo '<a href="edit.php?post_type='.$postType.'">'.$text.'</a>';
        } else {
            echo $text;
        }
        echo '</td></tr>';
    }
}
add_action('right_now_content_table_end', 'einsatzverwaltung_add_einsatzberichte_to_dashboard_legacy'); // before WP 3.8
