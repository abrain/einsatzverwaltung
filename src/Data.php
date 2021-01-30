<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Types\Report;
use DateTime;
use WP_Post;
use wpdb;
use function absint;
use function array_key_exists;
use function current_user_can;
use function defined;
use function delete_post_meta;
use function error_log;
use function filter_input;
use function get_post_meta;
use function get_post_type;
use function update_post_meta;
use function wp_verify_nonce;
use const FILTER_SANITIZE_NUMBER_INT;
use const FILTER_SANITIZE_STRING;
use const INPUT_POST;

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
    }

    /**
     * Returns the years that contain reports
     *
     * @return int[]
     */
    public function getYearsWithReports()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $yearStrings = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT YEAR(post_date) AS years FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s;",
            array('einsatz', 'publish')
        ));

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
        // Schreibrechte prüfen
        if (!current_user_can('edit_einsatzbericht', $postId)) {
            return;
        }

        // Automatische Speicherungen sollen nicht berücksichtigt werden
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Fängt Speichervorgänge per QuickEdit ab
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if ($this->isBulkEdit()) {
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
        } elseif (in_array($post->post_status, array('publish', 'private'))) {
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

        // Save weight
        $weight = filter_input(INPUT_POST, 'einsatz_weight', FILTER_SANITIZE_NUMBER_INT);
        $weight = absint($weight);
        $updateArgs['meta_input']['einsatz_weight'] = empty($weight) ? '1' : $weight;

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
            $years = self::getYearsWithReports();
        }

        if (!is_array($yearToUpdate) && is_string($yearToUpdate) && is_numeric($yearToUpdate)) {
            $years = array($yearToUpdate);
        }

        if (empty($years) || !is_array($years)) {
            return;
        }

        foreach ($years as $year) {
            $reportQuery = new ReportQuery();
            $reportQuery->setOrderAsc(true);
            $reportQuery->setIncludePrivateReports(true);
            $reportQuery->setYear($year);
            $reports = $reportQuery->getReports();

            $expectedNumber = 1;
            foreach ($reports as $report) {
                $actualNumber = $report->getSequentialNumber();
                if ($expectedNumber != $actualNumber) {
                    $this->setSequenceNumber($report->getPostId(), $expectedNumber);
                }
                $expectedNumber += $report->getWeight();
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
        if (get_post_type($post) !== Report::getSlug()) {
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

    /**
     * @return bool
     */
    private function isBulkEdit()
    {
        if (isset($_REQUEST['filter_action']) && ! empty($_REQUEST['filter_action'])) {
            return false;
        }

        if ((isset($_REQUEST['action']) && -1 != $_REQUEST['action'] && 'edit' === $_REQUEST['action']) ||
            (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'] && 'edit' === $_REQUEST['action2'])
        ) {
            return isset($_REQUEST['bulk_edit']);
        }

        return false;
    }
}
