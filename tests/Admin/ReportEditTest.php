<?php
namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportFactory;
use DateTime;
use DateTimeZone;
use Exception;
use WP_Post;
use WP_UnitTestCase;
use WP_User;
use function get_option;

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
            'einsatz_einsatzende' => '',
            'einsatz_einsatzort' => '',
            'einsatz_einsatzleiter' => '',
            'einsatz_mannschaft' => ''
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
            'einsatz_einsatzende' => '',
            'einsatz_einsatzort' => '',
            'einsatz_einsatzleiter' => '',
            'einsatz_mannschaft' => ''
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
            'einsatz_einsatzende' => '',
            'einsatz_einsatzort' => '',
            'einsatz_einsatzleiter' => '',
            'einsatz_mannschaft' => ''
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

    public function testSanitizeTimeOfEnd()
    {
        $reportFactory = new ReportFactory();

        /** @var WP_Post $post */
        $post = $reportFactory->create_and_get(array(
            'meta_input' => array(
                'einsatz_einsatzende' => ''
            )
        ));
        $this->assertEmpty(get_post_meta($post->ID, 'einsatz_einsatzende', true));

        $post = $reportFactory->create_and_get(array(
            'meta_input' => array(
                'einsatz_einsatzende' => 'invaliddate'
            )
        ));
        $this->assertEmpty(get_post_meta($post->ID, 'einsatz_einsatzende', true));

        $post = $reportFactory->create_and_get(array(
            'meta_input' => array(
                'einsatz_einsatzende' => '2018-06-24 13:46'
            )
        ));
        $this->assertEquals('2018-06-24 13:46', get_post_meta($post->ID, 'einsatz_einsatzende', true));
    }

    public function testPublishFuture()
    {
        $reportFactory = new ReportFactory();

        /** @var WP_User $userEditor */
        $userEditor = $this->factory->user->create_and_get();
        $userEditor->add_cap('edit_einsatzberichte');
        $userEditor->add_cap('edit_others_einsatzberichte');
        $userEditor->add_cap('edit_published_einsatzberichte');
        $userEditor->add_cap('publish_einsatzberichte');
        wp_set_current_user($userEditor->ID);

        try {
            $dateTimeZone = new DateTimeZone(get_option('timezone_string'));
            $reportDateTime = new DateTime('1 hour ago', $dateTimeZone);
            $reportDate = $reportDateTime->format('Y-m-d H:i:s');

            $publishDateTime = new DateTime('+ 1 hour', $dateTimeZone);
            $futureDate= $publishDateTime->format('Y-m-d H:i:s');

            $newPublishDateTime = new DateTime('5 seconds ago', $dateTimeZone);
            $newPublishDate= $newPublishDateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->fail('Could not generate dates');
            return;
        }
        $_POST = array(
            'einsatzverwaltung_nonce' => wp_create_nonce('save_einsatz_details'),
            'einsatzverwaltung_alarmzeit' => $reportDate,
        );
        /** @var WP_Post $post */
        $post = $reportFactory->create_and_get(array(
            'post_status' => 'future',
            'post_date' => $futureDate
        ));

        $this->assertEquals('future', get_post_status($post));
        $this->assertEquals($futureDate, $post->post_date);
        $this->assertEquals($reportDate, get_post_meta($post->ID, '_einsatz_timeofalerting', true));

        // publish the report
        $result = wp_update_post(array(
            'ID' => $post->ID,
            'post_date' => $newPublishDate,
            'post_date_gmt' => get_gmt_from_date($newPublishDate),
            'post_status' => 'publish'
        ), true);
        if (is_wp_error($result)) {
            $this->fail('Could not update post: ' . $result->get_error_message());
            return;
        }

        // Refresh post data
        $post = get_post($post->ID);

        $this->assertEquals('publish', get_post_status($post));
        $this->assertEquals($reportDate, $post->post_date);
        $this->assertEquals('', get_post_meta($post->ID, '_einsatz_timeofalerting', true));
    }
}
