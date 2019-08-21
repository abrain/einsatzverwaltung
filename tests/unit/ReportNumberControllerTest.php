<?php
namespace abrain\Einsatzverwaltung;

use function Brain\Monkey\Functions\expect;

/**
 * Class ReportNumberControllerTest
 * @covers \abrain\Einsatzverwaltung\ReportNumberController
 * @package abrain\Einsatzverwaltung
 */
class ReportNumberControllerTest extends UnitTestCase
{
    public function testSanitizeReportNumberDigits()
    {
        $fallback = ReportNumberController::DEFAULT_SEQNUM_DIGITS;
        $this->assertEquals(2, ReportNumberController::sanitizeEinsatznummerStellen(2));
        $this->assertEquals(5, ReportNumberController::sanitizeEinsatznummerStellen('5'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(0));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(-1));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen('-2'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen('string'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(''));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(false));
    }

    public function testChangeOfSequenceNumber()
    {
        $controller = new ReportNumberController();
        $postId = 158;
        expect('get_post_type')->once()->with($postId)->andReturn('einsatz');
        expect('get_option')->once()->with('einsatzverwaltung_incidentnumbers_auto', '0')->andReturn('1');
        expect('get_post_field')->once()->with('post_date', $postId)->andReturn('2018-03-24 00:00:00');
        expect('get_option')->once()->with('einsatzvw_einsatznummer_stellen')->andReturn('4');
        expect('get_option')->once()->with('einsatzvw_einsatznummer_lfdvorne', false)->andReturn('0');
        expect('update_post_meta')->once()->with($postId, 'einsatz_incidentNumber', '20180017');
        $controller->onPostMetaChanged(0, $postId, 'einsatz_seqNum', '17');
    }

    public function testChangeOfForeignPostMeta()
    {
        $controller = new ReportNumberController();
        $postId = 4652;
        expect('get_post_type')->atMost()->once()->with($postId)->andReturn('einsatz');
        expect('update_post_meta')->never();
        $controller->onPostMetaChanged(0, $postId, 'some_other_key', '941');
    }

    public function testChangeOfPostMetaOfForeignPostType()
    {
        $controller = new ReportNumberController();
        $postId = 176;
        expect('get_post_type')->once()->with($postId)->andReturn('unkown_cpt');
        expect('update_post_meta')->never();
        $controller->onPostMetaChanged(0, $postId, 'einsatz_seqNum', '89');
    }
}
