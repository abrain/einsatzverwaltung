<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Report;
use DateTime;
use WP_Post;
use wpdb;

/**
 * Stellt Methoden zur Datenabfrage und Datenmanipulation bereit
 */
class Data
{
    /**
     * @var Options
     */
    private $options;

    /**
     * Regelt, ob automatisch die laufenden Nummern aktualisiert werden sollen. Es ist aus Gründen der Performance
     * vereinzelt sinnvoll, diesen Automatismus temporär abzuschalten.
     *
     * @var bool
     */
    private $assignSequenceNumbers = true;

    /**
     * Constructor
     *
     * @param Options $options
     */
    public function __construct($options)
    {
        $this->options = $options;

        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('save_post_einsatz', array($this, 'savePostdata'), 10, 2);
        add_action('private_einsatz', array($this, 'onPublish'), 10, 2);
        add_action('publish_einsatz', array($this, 'onPublish'), 10, 2);
        add_action('trash_einsatz', array($this, 'onTrash'), 10, 2);
        add_action('transition_post_status', array($this, 'onTransitionPostStatus'), 10, 3);
    }

    /**
     * @param $kalenderjahr
     *
     * @return WP_Post[]
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
     * Gibt ein Array mit Jahreszahlen zurück, in denen Einsätze vorliegen
     *
     * @return string[]
     */
    public static function getJahreMitEinsatz()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT YEAR(post_date) AS years FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s;",
            array('einsatz', 'publish')
        ));
    }

    /**
     * Returns the years
     * @return int[]
     */
    public function getYearsWithReports()
    {
        $yearStrings = self::getJahreMitEinsatz();
        return array_map('intval', $yearStrings);
    }

    /**
     * Zusätzliche Metadaten des Einsatzberichts speichern
     *
     * @param int $postId ID des Posts
     * @param WP_Post $post Das Post-Objekt
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

        // Alarmzeit validieren
        $inputAlarmzeit = sanitize_text_field($_POST['einsatzverwaltung_alarmzeit']);
        if (!empty($inputAlarmzeit)) {
            $alarmzeit = date_create($inputAlarmzeit);
        }
        if (empty($alarmzeit)) {
            $alarmzeit = date_create($post->post_date);
        }

        $updateArgs = array(
            'ID' => $postId,
            'meta_input' => array()
        );

        /**
         * Solange der Einsatzbericht ein Entwurf ist, soll kein Datum gesetzt werden (vgl. wp_update_post()). Deshalb
         * wird bei diesen und bei geplanten Berichten der Alarmzeitpunkt in den Metadaten zwischengespeichert.
         */
        if (in_array($post->post_status, array('draft', 'pending', 'auto-draft', 'future'))) {
            $updateArgs['meta_input']['_einsatz_timeofalerting'] = date_format($alarmzeit, 'Y-m-d H:i:s');
        } elseif ($post->post_status === 'publish') {
            $updateArgs['post_date'] = date_format($alarmzeit, 'Y-m-d H:i:s');
            $updateArgs['post_date_gmt'] = get_gmt_from_date($updateArgs['post_date']);
        }

        // Einsatznummer setzen, sofern sie nicht automatisch verwaltet wird
        if (!ReportNumberController::isAutoIncidentNumbers()) {
            $number = filter_input(INPUT_POST, 'einsatzverwaltung_nummer', FILTER_SANITIZE_STRING);
            $updateArgs['meta_input']['einsatz_incidentNumber'] = $number;
        }

        // Einsatzdetails speichern
        $metaFields = array('einsatz_einsatzende', 'einsatz_einsatzort', 'einsatz_einsatzleiter', 'einsatz_mannschaft');
        foreach ($metaFields as $metaField) {
            $value = filter_input(INPUT_POST, $metaField, FILTER_SANITIZE_STRING);
            $updateArgs['meta_input'][$metaField] = empty($value) ? '' : $value;
        }

        // Vermerke speichern (werden explizit deaktiviert, wenn sie nicht gesetzt wurden)
        $annotations = array('einsatz_fehlalarm', 'einsatz_hasimages', 'einsatz_special');
        foreach ($annotations as $annotation) {
            $value = filter_input(INPUT_POST, $annotation, FILTER_SANITIZE_STRING);
            $updateArgs['meta_input'][$annotation] = empty($value) ? '0' : $value;
        }

        // save_post Filter kurzzeitig deaktivieren, damit keine Dauerschleife entsteht
        remove_action('save_post_einsatz', array($this, 'savePostdata'));
        wp_update_post($updateArgs);
        add_action('save_post_einsatz', array($this, 'savePostdata'), 10, 2);
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

            $expectedNumber = 1;
            foreach ($posts as $post) {
                $actualNumber = get_post_meta($post->ID, 'einsatz_seqNum', true);
                if ($expectedNumber != $actualNumber) {
                    $this->setSequenceNumber($post->ID, $expectedNumber);
                }
                $expectedNumber++;
            }
        }
    }

    /**
     * Wird aufgerufen, sobald ein Einsatzbericht veröffentlicht wird
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param WP_Post $post Das Post-Objekt des Einsatzberichts
     */
    public function onPublish($postId, $post)
    {
        $report = new IncidentReport($post);

        // Laufende Nummern aktualisieren
        if (true === $this->assignSequenceNumbers) {
            $date = $report->getTimeOfAlerting();
            $this->updateSequenceNumbers($date->format('Y'));
        }

        // Kategoriezugehörigkeit aktualisieren
        $category = $this->options->getEinsatzberichteCategory();
        if ($category != -1) {
            if (!($this->options->isOnlySpecialInLoop()) || $report->isSpecial()) {
                $report->addToCategory($category);
            } else {
                Utilities::removePostFromCategory($postId, $category);
            }
        }
    }

    /**
     * @param string $newStatus
     * @param string $oldStatus
     * @param WP_Post $post
     */
    public function onTransitionPostStatus($newStatus, $oldStatus, WP_Post $post)
    {
        if (get_post_type($post) !== Report::SLUG) {
            return;
        }

        if ($newStatus === 'publish' && $oldStatus !== 'publish') {
            $this->adjustPostDate($post);
        }
    }

    /**
     * Set the publish date of the report to the time of alerting
     *
     * @param WP_Post $post
     */
    private function adjustPostDate(WP_Post $post)
    {
        $tempTimeOfAlerting = get_post_meta($post->ID, '_einsatz_timeofalerting', true);
        if (empty($tempTimeOfAlerting)) {
            return;
        }

        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $tempTimeOfAlerting);
        $updateArgs = array(
            'ID' => $post->ID,
            'post_date' => date_format($dateTime, 'Y-m-d H:i:s')
        );
        $updateArgs['post_date_gmt'] = get_gmt_from_date($updateArgs['post_date']);
        $updateResult = wp_update_post($updateArgs);
        if (is_wp_error($updateResult)) {
            error_log($updateResult->get_error_message());
        }

        // Zwischenspeicher wird nur in der Entwurfsphase benötigt
        delete_post_meta($post->ID, '_einsatz_timeofalerting');
    }

    /**
     * Wird aufgerufen, sobald ein Einsatzbericht in den Papierkorb verschoben wird
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param WP_Post $post Das Post-Objekt des Einsatzberichts
     */
    public function onTrash($postId, $post)
    {
        // Laufende Nummern aktualisieren
        if (true === $this->assignSequenceNumbers) {
            $date = date_create($post->post_date);
            $this->updateSequenceNumbers(date_format($date, 'Y'));
        }
        delete_post_meta($postId, 'einsatz_seqNum');

        // Kategoriezugehörigkeit aktualisieren
        $category = $this->options->getEinsatzberichteCategory();
        if ($category != -1) {
            Utilities::removePostFromCategory($postId, $category);
        }
    }

    public function pauseAutoSequenceNumbers()
    {
        $this->assignSequenceNumbers = false;
    }

    public function resumeAutoSequenceNumbers()
    {
        $this->assignSequenceNumbers = true;
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
}
