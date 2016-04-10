<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportFactory;
use DateTime;
use WP_UnitTestCase;
use WP_User;

/**
 * Testet die Erstellung und Bearbeitung von Einsatzberichten
 *
 * @package abrain\Einsatzverwaltung\Admin
 * @author Andreas Brain
 */
class ReportEditTest extends WP_UnitTestCase
{
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        wp_set_current_user(0);
    }

    public function testTimeOfAlertingSurvivesDraftState()
    {
        /** @var WP_User $userAuthor */
        $userAuthor = $this->factory->user->create_and_get();
        $userAuthor->add_cap('edit_einsatzberichte');

        // Einsatzbericht anlegen
        wp_set_current_user($userAuthor->ID);
        $reportFactory = new ReportFactory();
        $post = $reportFactory->create_and_get(array(
            'post_status' => 'auto-draft'
        ));
        $initialReport = new IncidentReport($post);
        $this->assertEquals('auto-draft', get_post_status($initialReport->getPostId()));

        $initialPostDate = $post->post_date;
        /** @var DateTime $dateCreate */
        $dateCreate = date_create($initialPostDate);
        $dateCreate->modify('-5 minutes');
        $timeOfAlerting = $dateCreate->format('Y-m-d H:i:s');

        // Einsatzbericht als Entwurf speichern
        $_POST = array(
            'einsatzverwaltung_nonce' => wp_create_nonce('save_einsatz_details'),
            'einsatzverwaltung_nummer' => '',
            'einsatzverwaltung_alarmzeit' => $timeOfAlerting,
            'einsatzverwaltung_einsatzende' => '',
            'einsatzverwaltung_einsatzort' => '',
            'einsatzverwaltung_einsatzleiter' => '',
            'einsatzverwaltung_mannschaft' => '',
        );
        wp_update_post(array(
            'ID' => $post->ID,
            'post_status' => 'draft',
        ));

        $report = new IncidentReport($post->ID);
        $this->assertEquals('draft', get_post_status($report->getPostId()));
        $this->assertEquals(
            $timeOfAlerting,
            $report->getTimeOfAlerting()->format('Y-m-d H:i:s'),
            'Alarmzeit wurde nicht im Entwurf gespeichert'
        );
    }

    public function testTimeOfAlertingSurvivesReviewAndPublish()
    {
        /** @var WP_User $userAuthor */
        $userAuthor = $this->factory->user->create_and_get();
        $userAuthor->add_cap('edit_einsatzberichte');

        /** @var WP_User $userEditor */
        $userEditor = $this->factory->user->create_and_get();
        $userEditor->add_cap('edit_einsatzberichte');
        $userEditor->add_cap('edit_others_einsatzberichte');
        $userEditor->add_cap('edit_published_einsatzberichte');
        $userEditor->add_cap('publish_einsatzberichte');

        // Einsatzbericht anlegen
        wp_set_current_user($userAuthor->ID);
        $reportFactory = new ReportFactory();
        $post = $reportFactory->create_and_get(array(
            'post_status' => 'auto-draft'
        ));
        $initialPostDate = $post->post_date;
        /** @var DateTime $dateCreate */
        $dateCreate = date_create($initialPostDate);
        $dateCreate->modify('-5 minutes');
        $timeOfAlerting = $dateCreate->format('Y-m-d H:i:s');

        // Einsatzbericht zur Freigabe vorlegen
        $_POST = array(
            'einsatzverwaltung_nonce' => wp_create_nonce('save_einsatz_details'),
            'einsatzverwaltung_nummer' => '',
            'einsatzverwaltung_alarmzeit' => $timeOfAlerting,
            'einsatzverwaltung_einsatzende' => '',
            'einsatzverwaltung_einsatzort' => '',
            'einsatzverwaltung_einsatzleiter' => '',
            'einsatzverwaltung_mannschaft' => '',
        );
        wp_update_post(array(
            'ID' => $post->ID,
            'post_status' => 'pending',
        ));

        $pendingReport = new IncidentReport($post->ID);
        $this->assertEquals('pending', get_post_status($pendingReport->getPostId()));
        $this->assertEquals(
            $timeOfAlerting,
            $pendingReport->getTimeOfAlerting()->format('Y-m-d H:i:s'),
            'Alarmzeit wurde nicht im Entwurf gespeichert'
        );

        // Einsatzbericht freigeben
        wp_set_current_user($userEditor->ID);
        $_POST = array(
            'einsatzverwaltung_nonce' => wp_create_nonce('save_einsatz_details'),
            'einsatzverwaltung_nummer' => '',
            'einsatzverwaltung_alarmzeit' => $pendingReport->getTimeOfAlerting()->format('Y-m-d H:i:s'),
            'einsatzverwaltung_einsatzende' => '',
            'einsatzverwaltung_einsatzort' => '',
            'einsatzverwaltung_einsatzleiter' => '',
            'einsatzverwaltung_mannschaft' => '',
        );
        wp_update_post(array(
            'ID' => $post->ID,
            'post_status' => 'publish',
        ));

        $report = new IncidentReport($post->ID);
        $this->assertEquals('publish', get_post_status($report->getPostId()));
        $this->assertEquals(
            $timeOfAlerting,
            $report->getTimeOfAlerting()->format('Y-m-d H:i:s'),
            'Alarmzeit wurde nicht im veröffentlichten Bericht gespeichert'
        );
    }
}
