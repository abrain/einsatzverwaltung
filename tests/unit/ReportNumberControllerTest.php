<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
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
        $this->assertEquals(2, ReportNumberController::sanitizeNumberOfDigits(2));
        $this->assertEquals(5, ReportNumberController::sanitizeNumberOfDigits('5'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeNumberOfDigits(0));
        $this->assertEquals($fallback, ReportNumberController::sanitizeNumberOfDigits(-1));
        $this->assertEquals($fallback, ReportNumberController::sanitizeNumberOfDigits('-2'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeNumberOfDigits('string'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeNumberOfDigits(''));
        $this->assertEquals($fallback, ReportNumberController::sanitizeNumberOfDigits(false));
    }

    public function testSanitizeSeparator()
    {
        $fallback = ReportNumberController::DEFAULT_SEPARATOR;
        self::assertEquals('none', ReportNumberController::sanitizeSeparator('none'));
        self::assertEquals('slash', ReportNumberController::sanitizeSeparator('slash'));
        self::assertEquals('dash', ReportNumberController::sanitizeSeparator('dash'));
        self::assertEquals($fallback, ReportNumberController::sanitizeSeparator(''));
        self::assertEquals($fallback, ReportNumberController::sanitizeSeparator('something'));
    }


    /**
     * @throws ExpectationArgsRequired
     */
    public function testChangeOfSequenceNumber()
    {
        $data = Mockery::mock('\abrain\Einsatzverwaltung\Data');
        $controller = new ReportNumberController($data);
        $postId = 158;
        expect('get_post_type')->once()->with($postId)->andReturn('einsatz');
        expect('get_option')->once()->with('einsatzverwaltung_incidentnumbers_auto', '0')->andReturn('1');
        expect('get_post_field')->once()->with('post_date', $postId)->andReturn('2018-03-24 00:00:00');
        expect('get_option')->once()->with('einsatzvw_einsatznummer_stellen')->andReturn('4');
        expect('get_option')->once()->with('einsatzvw_einsatznummer_lfdvorne', false)->andReturn('0');
        expect('get_option')->once()->with('einsatzvw_numbers_separator', 'none')->andReturn('none');
        expect('update_post_meta')->once()->with($postId, 'einsatz_incidentNumber', '20180017');
        $controller->onPostMetaChanged(0, $postId, 'einsatz_seqNum', '17');
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testChangeOfForeignPostMeta()
    {
        $data = Mockery::mock('\abrain\Einsatzverwaltung\Data');
        $controller = new ReportNumberController($data);
        $postId = 4652;
        expect('get_post_type')->atMost()->once()->with($postId)->andReturn('einsatz');
        expect('update_post_meta')->never();
        $controller->onPostMetaChanged(0, $postId, 'some_other_key', '941');
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testChangeOfPostMetaOfForeignPostType()
    {
        $data = Mockery::mock('\abrain\Einsatzverwaltung\Data');
        $controller = new ReportNumberController($data);
        $postId = 176;
        expect('get_post_type')->once()->with($postId)->andReturn('unkown_cpt');
        expect('update_post_meta')->never();
        $controller->onPostMetaChanged(0, $postId, 'einsatz_seqNum', '89');
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testFormatEinsatznummer()
    {
        $data = Mockery::mock(Data::class);
        $controller = new ReportNumberController($data);

        expect('get_option')->once()->with('einsatzvw_einsatznummer_stellen')->andReturn('3');
        expect('get_option')->once()->with('einsatzvw_einsatznummer_lfdvorne', false)->andReturn('1');
        expect('get_option')->once()->with('einsatzvw_numbers_separator', 'none')->andReturn('slash');
        self::assertEquals('012/2020', $controller->formatEinsatznummer('2020', 12));

        expect('get_option')->once()->with('einsatzvw_einsatznummer_stellen')->andReturn('2');
        expect('get_option')->once()->with('einsatzvw_einsatznummer_lfdvorne', false)->andReturn('0');
        expect('get_option')->once()->with('einsatzvw_numbers_separator', 'none')->andReturn('dash');
        self::assertEquals('2019-623', $controller->formatEinsatznummer('2019', 623));
    }
}
