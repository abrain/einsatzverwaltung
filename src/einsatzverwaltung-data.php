<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use WP_Query;
use wpdb;

/**
 * Stellt Methoden zur Datenabfrage und Datenmanipulation bereit
 */
class Data
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var Options
     */
    private $options;

    /**
     * Constructor
     *
     * @param Core $core
     * @param Utilities $utilities
     * @param Options $options
     */
    public function __construct($core, $utilities, $options)
    {
        $this->core = $core;
        $this->utilities = $utilities;
        $this->options = $options;

        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('save_post_einsatz', array($this, 'savePostdata'), 10, 2);
        add_action('private_einsatz', array($this, 'onPublish'), 10, 2);
        add_action('publish_einsatz', array($this, 'onPublish'), 10, 2);
        add_action('trash_einsatz', array($this, 'onTrash'), 10, 2);
        add_filter('sanitize_post_meta_einsatz_fehlalarm', array($this->utilities, 'sanitizeCheckbox'));
        add_filter('sanitize_post_meta_einsatz_special', array($this->utilities, 'sanitizeCheckbox'));
        if ($this->options->isAutoIncidentNumbers()) {
            add_action('updated_postmeta', array($this, 'adjustIncidentNumber'), 10, 4);
            add_action('added_post_meta', array($this, 'adjustIncidentNumber'), 10, 4);
        }
    }

    /**
     * Gibt die Einsatzdauer in Minuten zurück
     *
     * @param IncidentReport $report
     *
     * @return bool|int Dauer in Minuten oder false, wenn Alarmzeit und/oder Einsatzende nicht verfügbar sind
     */
    public static function getDauer($report)
    {
        $timeOfAlerting = $report->getTimeOfAlerting();
        $timeOfEnding = $report->getTimeOfEnding();

        if (empty($timeOfAlerting) || empty($timeOfEnding)) {
            return false;
        }

        $timestamp1 = $timeOfAlerting->getTimestamp();
        $timestamp2 = strtotime($timeOfEnding);
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
            'post_status' => array('publish', 'private'),
            'year' => $kalenderjahr
        ));
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
     * Gibt die Anzahl der veröffentlichten Einsatzberichte zurück
     *
     * @param int|null $year Das Jahr für das die Anfrage gestellt werden soll. Wird dieser Parameter weggelassen,
     * werden alle Jahre berücksichtigt.
     *
     * @return int Die Anzahl der veröffentlichten Einsatzberichte
     */
    public function getNumberOfIncidentReports($year = null)
    {
        $args = array(
            'post_type' => 'einsatz',
            'post_status' => array('publish', 'private'),
            'nopaging' => true
        );

        if (!empty($year) && is_numeric($year)) {
            $args['year'] = $year;
        }

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Zusätzliche Metadaten des Einsatzberichts speichern
     *
     * @param int $postId ID des Posts
     * @param \WP_Post $post Das Post-Objekt
     */
    public function savePostdata($postId, $post)
    {
        // Automatische Speicherungen sollen nicht berücksichtigt werden
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Fängt Speichervorgänge per QuickEdit ab
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Schreibrechte prüfen
        if (!current_user_can('edit_einsatzbericht', $postId)) {
            return;
        }

        // Prüfen, ob Aufruf über das Formular erfolgt ist
        if (!array_key_exists('einsatzverwaltung_nonce', $_POST) ||
            !wp_verify_nonce($_POST['einsatzverwaltung_nonce'], 'save_einsatz_details')
        ) {
            return;
        }

        $updateArgs = array();

        // Alarmzeit validieren
        $inputAlarmzeit = sanitize_text_field($_POST['einsatzverwaltung_alarmzeit']);
        if (!empty($inputAlarmzeit)) {
            $alarmzeit = date_create($inputAlarmzeit);
        }
        if (empty($alarmzeit)) {
            $alarmzeit = date_create($post->post_date);
        }

        // Solange der Einsatzbericht ein Entwurf ist, soll kein Datum gesetzt werden (vgl. wp_update_post()).
        if (in_array($post->post_status, array('draft', 'pending', 'auto-draft'))) {
            // Wird bis zur Veröffentlichung in Postmeta zwischengespeichert.
            update_post_meta($postId, '_einsatz_timeofalerting', date_format($alarmzeit, 'Y-m-d H:i:s'));
        } else {
            $updateArgs['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            $updateArgs['post_date_gmt'] = get_gmt_from_date($updateArgs['post_date']);
        }

        // Einsatznummer setzen, sofern sie nicht automatisch verwaltet wird
        if (!$this->options->isAutoIncidentNumbers()) {
            $inputIncidentNumber = sanitize_text_field($_POST['einsatzverwaltung_nummer']);
            if (!empty($inputIncidentNumber)) {
                $this->setEinsatznummer($postId, $inputIncidentNumber);
            }
        }

        // Einsatzende validieren
        $inputEinsatzende = sanitize_text_field($_POST['einsatzverwaltung_einsatzende']);
        if (!empty($inputEinsatzende)) {
            $einsatzende = date_create($inputEinsatzende);
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

        // Vermerke validieren
        $fehlalarm = $this->utilities->sanitizeCheckbox(array($_POST, 'einsatzverwaltung_fehlalarm'));
        $hasImages = $this->utilities->sanitizeCheckbox(array($_POST, 'einsatzverwaltung_hasimages'));
        $isSpecial = $this->utilities->sanitizeCheckbox(array($_POST, 'einsatzverwaltung_special'));

        // Metadaten schreiben
        update_post_meta($postId, 'einsatz_einsatzende', $einsatzende);
        update_post_meta($postId, 'einsatz_einsatzort', $einsatzort);
        update_post_meta($postId, 'einsatz_einsatzleiter', $einsatzleiter);
        update_post_meta($postId, 'einsatz_mannschaft', $mannschaftsstaerke);
        update_post_meta($postId, 'einsatz_fehlalarm', $fehlalarm);
        update_post_meta($postId, 'einsatz_hasimages', $hasImages);
        update_post_meta($postId, 'einsatz_special', $isSpecial);

        if (!empty($updateArgs)) {
            $updateArgs['ID'] = $postId;

            // save_post Filter kurzzeitig deaktivieren, damit keine Dauerschleife entsteht
            remove_action('save_post_einsatz', array($this, 'savePostdata'));
            wp_update_post($updateArgs);
            add_action('save_post_einsatz', array($this, 'savePostdata'), 10, 2);
        }
    }

    /**
     * Aktualisiert die laufende Nummer der Einsatzberichte
     *
     * @param string|null $yearToUpdate Kalenderjahr, für das die laufenden Nummern aktualisiert werden soll. Wird der
     * Parameter weggelassen, werden die Einsatzberichte aus allen Jahren aktualisiert.
     */
    public function updateSequenceNumbers($yearToUpdate = null)
    {
        if (empty($yearToUpdate)) {
            $years = self::getJahreMitEinsatz();
        }

        if (!is_array($yearToUpdate) && is_string($yearToUpdate) && is_numeric($yearToUpdate)) {
            $years = array($yearToUpdate);
        }

        if (empty($years) || !is_array($years)) {
            return;
        }

        foreach ($years as $year) {
            $posts = self::getEinsatzberichte($year);

            $counter = 1;
            foreach ($posts as $post) {
                $this->setSequenceNumber($post->ID, $counter);
                $counter++;
            }
        }
    }

    /**
     * Wird aufgerufen, sobald ein Einsatzbericht veröffentlicht wird
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param \WP_Post $post Das Post-Objekt des Einsatzberichts
     */
    public function onPublish($postId, $post)
    {
        $report = new IncidentReport($post);

        // Laufende Nummern aktualisieren
        $date = $report->getTimeOfAlerting();
        $this->updateSequenceNumbers($date->format('Y'));

        // Kategoriezugehörigkeit aktualisieren
        $category = $this->options->getEinsatzberichteCategory();
        if ($category != -1) {
            if (!($this->options->isOnlySpecialInLoop()) || $report->isSpecial()) {
                $this->utilities->addPostToCategory($postId, $category);
            } else {
                $this->utilities->removePostFromCategory($postId, $category);
            }
        }
        
        // Zwischenspeicher wird nur in der Entwurfsphase benötigt
        delete_post_meta($postId, '_einsatz_timeofalerting');
    }

    /**
     * Wird aufgerufen, sobald ein Einsatzbericht in den Papierkorb verschoben wird
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param \WP_Post $post Das Post-Objekt des Einsatzberichts
     */
    public function onTrash($postId, $post)
    {
        // Laufende Nummern aktualisieren
        $date = date_create($post->post_date);
        $this->updateSequenceNumbers(date_format($date, 'Y'));
        delete_post_meta($postId, 'einsatz_seqNum');

        // Kategoriezugehörigkeit aktualisieren
        $category = $this->options->getEinsatzberichteCategory();
        if ($category != -1) {
            $this->utilities->removePostFromCategory($postId, $category);
        }
    }

    /**
     * Sobald die laufende Nummer aktualisiert wird, muss die Einsatznummer neu generiert werden.
     *
     * @param int $metaId ID des postmeta-Eintrags
     * @param int $objectId Post-ID
     * @param string $metaKey Der Key des postmeta-Eintrags
     * @param string $metaValue Der Wert des postmeta-Eintrags
     */
    public function adjustIncidentNumber($metaId, $objectId, $metaKey, $metaValue)
    {
        // Nur Änderungen an der laufenden Nummer sind interessant
        if ('einsatz_seqNum' != $metaKey) {
            return;
        }

        // Für den unwahrscheinlichen Fall, dass der Metakey bei anderen Beitragstypen verwendet wird, ist hier Schluss
        $postType = get_post_type($objectId);
        if ('einsatz' != $postType) {
            return;
        }

        $date = date_create(get_post_field('post_date', $objectId));
        $newIncidentNumber = $this->core->formatEinsatznummer(date_format($date, 'Y'), $metaValue);
        $this->setEinsatznummer($objectId, $newIncidentNumber);
    }

    /**
     * Ändert die Einsatznummer eines bestehenden Einsatzes
     *
     * @param int $postId ID des Einsatzberichts
     * @param string $einsatznummer Einsatznummer
     */
    public function setEinsatznummer($postId, $einsatznummer)
    {
        if (empty($postId) || empty($einsatznummer)) {
            return;
        }

        update_post_meta($postId, 'einsatz_incidentNumber', $einsatznummer);
    }

    /**
     * Ändert die laufende Nummer eines bestehenden Einsatzes
     *
     * @param int $postId ID des Einsatzberichts
     * @param string $seqNum Zu setzende laufende Nummer
     */
    public function setSequenceNumber($postId, $seqNum)
    {
        if (empty($postId) || empty($seqNum)) {
            return;
        }

        update_post_meta($postId, 'einsatz_seqNum', $seqNum);
    }

    /**
     * Generiert für alle Einsatzberichte eine Einsatznummer gemäß dem aktuell konfigurierten Format.
     */
    public function updateAllIncidentNumbers()
    {
        $years = self::getJahreMitEinsatz();
        foreach ($years as $year) {
            $posts = self::getEinsatzberichte($year);
            foreach ($posts as $post) {
                $incidentReport = new IncidentReport($post);
                $seqNum = $incidentReport->getSequentialNumber();
                $newIncidentNumber = $this->core->formatEinsatznummer($year, $seqNum);
                $this->setEinsatznummer($post->ID, $newIncidentNumber);
            }
        }
    }
}
