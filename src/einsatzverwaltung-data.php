<?php
namespace abrain\Einsatzverwaltung;

use WP_Query;
use wpdb;

/**
 * Stellt Methoden zur Datenabfrage und Datenmanipulation bereit
 */
class Data
{
    /**
     * Gibt das Term-Object der Alarmierungsart zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return array|bool|\WP_Error
     */
    public static function getAlarmierungsart($postId)
    {
        return get_the_terms($postId, 'alarmierungsart');
    }

    /**
     * Gibt Alarmdatum und -zeit zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return mixed
     */
    public static function getAlarmzeit($postId)
    {
        return get_post_meta($postId, 'einsatz_alarmzeit', true);
    }

    /**
     * Gibt die Einsatzdauer in Minuten zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return bool|int Dauer in Minuten oder false, wenn Alarmzeit und/oder Einsatzende nicht verfügbar sind
     */
    public static function getDauer($postId)
    {
        $alarmzeit = self::getAlarmzeit($postId);
        $einsatzende = self::getEinsatzende($postId);

        if (empty($alarmzeit) || empty($einsatzende)) {
            return false;
        }

        $timestamp1 = strtotime($alarmzeit);
        $timestamp2 = strtotime($einsatzende);
        $differenz = $timestamp2 - $timestamp1;
        return intval($differenz / 60);
    }

    /**
     * @param $kalenderjahr
     *
     * @return array
     */
    public static function getEinsatzberichte($kalenderjahr)
    {
        if (empty($kalenderjahr) || strlen($kalenderjahr)!=4 || !is_numeric($kalenderjahr)) {
            $kalenderjahr = '';
        }

        return get_posts(array(
            'nopaging' => true,
            'orderby' => 'post_date',
            'order' => 'ASC',
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'year' => $kalenderjahr
        ));
    }

    /**
     * Bestimmt die Einsatzart eines bestimmten Einsatzes. Ist nötig, weil die Taxonomie
     * 'einsatzart' mehrere Werte speichern kann, aber nur einer genutzt werden soll
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return object|bool
     */
    public static function getEinsatzart($postId)
    {
        $einsatzarten = get_the_terms($postId, 'einsatzart');
        if ($einsatzarten && !is_wp_error($einsatzarten) && !empty($einsatzarten)) {
            $keys = array_keys($einsatzarten);
            return $einsatzarten[$keys[0]];
        } else {
            return false;
        }
    }

    /**
     * Gibt Datum und Zeit des Einsatzendes zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return mixed
     */
    public static function getEinsatzende($postId)
    {
        return get_post_meta($postId, 'einsatz_einsatzende', true);
    }

    /**
     * Gibt die Namen aller bisher verwendeten Einsatzleiter zurück
     *
     * @return array
     */
    public static function getEinsatzleiterNamen()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $names = array();
        $query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'einsatz_einsatzleiter' AND meta_value <> ''";
        $results = $wpdb->get_results($query, OBJECT);

        foreach ($results as $result) {
            $names[] = $result->meta_value;
        }
        return $names;
    }

    /**
     * Gibt den eingetragenen Einsatzleiter zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return mixed
     */
    public static function getEinsatzleiter($postId)
    {
        return get_post_meta($postId, 'einsatz_einsatzleiter', true);
    }

    /**
     * @param int $postId ID des Einsatzberichts
     *
     * @return string
     */
    public static function getEinsatznummer($postId)
    {
        return get_post_field('post_name', $postId);
    }

    /**
     * Gibt den eingetragenen Einsatzort zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return mixed
     */
    public static function getEinsatzort($postId)
    {
        return get_post_meta($postId, 'einsatz_einsatzort', true);
    }

    /**
     * Gibt die Fahrzeuge eines Einsatzberichts aus
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return array|bool|\WP_Error
     */
    public static function getFahrzeuge($postId)
    {
        return get_the_terms($postId, 'fahrzeug');
    }

    /**
     * @param int $postId ID des Einsatzberichts
     *
     * @return mixed
     */
    public static function getFehlalarm($postId)
    {
        return get_post_meta($postId, 'einsatz_fehlalarm', true);
    }

    /**
     * Gibt ein Array mit Jahreszahlen zurück, in denen Einsätze vorliegen
     */
    public static function getJahreMitEinsatz()
    {
        $jahre = array();
        $query = new WP_Query('&post_type=einsatz&post_status=publish&nopaging=true');
        while ($query->have_posts()) {
            $nextPost = $query->next_post();
            $timestamp = strtotime($nextPost->post_date);
            $jahre[date("Y", $timestamp)] = 1;
        }
        return array_keys($jahre);
    }

    /**
     * Gibt die eingetragene Mannschaftsstärke zurück
     *
     * @param int $postId ID des Einsatzberichts
     *
     * @return mixed
     */
    public static function getMannschaftsstaerke($postId)
    {
        return get_post_meta($postId, 'einsatz_mannschaft', true);
    }

    /**
     * @param int $postId ID des Einsatzberichts
     *
     * @return array|bool|\WP_Error
     */
    public static function getWeitereKraefte($postId)
    {
        return get_the_terms($postId, 'exteinsatzmittel');
    }

    /**
     * Zusätzliche Metadaten des Einsatzberichts speichern
     *
     * @param int $post_id ID des Posts
     */
    public function savePostdata($post_id)
    {
        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!array_key_exists('post_type', $_POST) || 'einsatz' !== $_POST['post_type']) {
            return;
        }

        // Prüfen, ob Aufruf über das Formular erfolgt ist
        if (!array_key_exists('einsatzverwaltung_nonce', $_POST) ||
            !wp_verify_nonce($_POST['einsatzverwaltung_nonce'], 'save_einsatz_details')
        ) {
            return;
        }

        // Schreibrechte prüfen
        if (!current_user_can('edit_einsatzbericht', $post_id)) {
            return;
        }

        $update_args = array();

        // Alarmzeit validieren
        $input_alarmzeit = sanitize_text_field($_POST['einsatzverwaltung_alarmzeit']);
        if (!empty($input_alarmzeit)) {
            $alarmzeit = date_create($input_alarmzeit);
        }
        if (empty($alarmzeit)) {
            $alarmzeit = date_create(
                sprintf(
                    '%s-%s-%s %s:%s:%s',
                    $_POST['aa'],
                    $_POST['mm'],
                    $_POST['jj'],
                    $_POST['hh'],
                    $_POST['mn'],
                    $_POST['ss']
                )
            );
        } else {
            $update_args['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            $update_args['post_date_gmt'] = get_gmt_from_date($update_args['post_date']);
        }

        // Einsatznummer validieren
        $einsatzjahr = date_format($alarmzeit, 'Y');
        $einsatzNrFallback = Core::getNextEinsatznummer($einsatzjahr, $einsatzjahr == date('Y'));
        $einsatznummer = sanitize_title($_POST['einsatzverwaltung_nummer'], $einsatzNrFallback, 'save');
        if (!empty($einsatznummer)) {
            $update_args['post_name'] = $einsatznummer; // Slug setzen
        }

        // Einsatzende validieren
        $input_einsatzende = sanitize_text_field($_POST['einsatzverwaltung_einsatzende']);
        if (!empty($input_einsatzende)) {
            $einsatzende = date_create($input_einsatzende);
        }
        if (empty($einsatzende)) {
            $einsatzende = "";
        } else {
            $einsatzende = date_format($einsatzende, 'Y-m-d H:i');
        }

        // Einsatzort validieren
        $einsatzort = sanitize_text_field($_POST['einsatzverwaltung_einsatzort']);

        // Einsatzleiter validieren
        $einsatzleiter = sanitize_text_field($_POST['einsatzverwaltung_einsatzleiter']);

        // Mannschaftsstärke validieren
        $mannschaftsstaerke = sanitize_text_field($_POST['einsatzverwaltung_mannschaft']);

        // Fehlalarm validieren
        $fehlalarm = Utilities::sanitizeCheckbox(array($_POST, 'einsatzverwaltung_fehlalarm'));

        // Metadaten schreiben
        update_post_meta($post_id, 'einsatz_alarmzeit', date_format($alarmzeit, 'Y-m-d H:i'));
        update_post_meta($post_id, 'einsatz_einsatzende', $einsatzende);
        update_post_meta($post_id, 'einsatz_einsatzort', $einsatzort);
        update_post_meta($post_id, 'einsatz_einsatzleiter', $einsatzleiter);
        update_post_meta($post_id, 'einsatz_mannschaft', $mannschaftsstaerke);
        update_post_meta($post_id, 'einsatz_fehlalarm', $fehlalarm);

        if (!empty($update_args)) {
            if (! wp_is_post_revision($post_id)) {
                $update_args['ID'] = $post_id;

                // save_post Filter kurzzeitig deaktivieren, damit keine Dauerschleife entsteht
                remove_action('save_post', array($this, 'savePostdata'));
                wp_update_post($update_args);
                add_action('save_post', array($this, 'savePostdata'));
            }
        }
    }

    /**
     * Ändert die Einsatznummer eines bestehenden Einsatzes
     *
     * @param int $post_id ID des Einsatzberichts
     * @param string $einsatznummer Einsatznummer
     */
    public function setEinsatznummer($post_id, $einsatznummer)
    {
        if (empty($post_id) || empty($einsatznummer)) {
            return;
        }

        $update_args = array();
        $update_args['post_name'] = $einsatznummer;
        $update_args['ID'] = $post_id;

        // keine Sonderbehandlung beim Speichern
        remove_action('save_post', array($this, 'savePostdata'));
        wp_update_post($update_args);
        add_action('save_post', array($this, 'savePostdata'));
    }
}
