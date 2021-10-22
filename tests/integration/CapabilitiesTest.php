<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;
use function user_can;

/**
 * Class CapabilitiesTest
 * @package abrain\Einsatzverwaltung
 */
class CapabilitiesTest extends WP_UnitTestCase
{
    private $capabilities = [
        'edit_einsatzberichte',
        'edit_private_einsatzberichte',
        'edit_published_einsatzberichte',
        'edit_others_einsatzberichte',
        'publish_einsatzberichte',
        'read_private_einsatzberichte',
        'delete_einsatzberichte',
        'delete_private_einsatzberichte',
        'delete_published_einsatzberichte',
        'delete_others_einsatzberichte'
    ];

    public function testUserWithoutRoleHasNoCaps()
    {
        $user = self::factory()->user->create_and_get();

        foreach ($this->capabilities as $capability) {
            $this->assertFalse(user_can($user, $capability));
        }
    }

    public function testAdministratorsCanDoAnything()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('administrator');

        foreach ($this->capabilities as $capability) {
            $this->assertTrue(user_can($user, $capability));
        }
    }

    public function testDefaultEditorHasNoCaps()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('editor');

        foreach ($this->capabilities as $capability) {
            $this->assertFalse(user_can($user, $capability));
        }
    }

    public function testReportEditorCanDoAnything()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_editor');

        foreach ($this->capabilities as $capability) {
            $this->assertTrue(user_can($user, $capability));
        }
    }

    public function testReportAuthorCanEditAndPublish()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_author');

        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get(['post_author' => $user->ID, 'post_status' => 'draft']);

        $this->assertTrue(user_can($user, 'edit_einsatzbericht', $report->ID));
        $this->assertTrue(user_can($user, 'publish_einsatzberichte'));
    }

    public function testReportAuthorCannotEditOthers()
    {
        $otherUser = self::factory()->user->create_and_get();
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_author');

        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get(['post_author' => $otherUser->ID, 'post_status' => 'draft']);

        $this->assertFalse(user_can($user, 'edit_einsatzbericht', $report->ID));
    }

    public function testReportAuthorCanEditPublished()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_author');

        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get(['post_author' => $user->ID, 'post_status' => 'publish']);

        $this->assertTrue(user_can($user, 'edit_einsatzbericht', $report->ID));
    }

    public function testReportContributorCannotPublish()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_contributor');

        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get(['post_author' => $user->ID, 'post_status' => 'draft']);

        $this->assertTrue(user_can($user, 'edit_einsatzbericht', $report->ID));
        $this->assertFalse(user_can($user, 'publish_einsatzberichte'));
    }

    public function testReportContributorCannotEditOthers()
    {
        $otherUser = self::factory()->user->create_and_get();
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_contributor');

        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get(['post_author' => $otherUser->ID, 'post_status' => 'draft']);

        $this->assertFalse(user_can($user, 'edit_einsatzbericht', $report->ID));
    }

    public function testReportContributorCannotEditPublished()
    {
        $user = self::factory()->user->create_and_get();
        $user->add_role('einsatzverwaltung_reports_contributor');

        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get(['post_author' => $user->ID, 'post_status' => 'publish']);

        $this->assertFalse(user_can($user, 'edit_einsatzbericht', $report->ID));
    }
}
