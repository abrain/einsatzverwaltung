<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\ReportNumberController;
use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use DateTime;
use Mockery;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Class FormatterTest
 * @covers \abrain\Einsatzverwaltung\Util\Formatter
 * @package abrain\Einsatzverwaltung\Util
 */
class FormatterTest extends UnitTestCase
{
    public function testGetDurationString()
    {
        $this->assertEquals('0 minutes', Formatter::getDurationString(0));
        $this->assertEquals('1 minute', Formatter::getDurationString(1));
        $this->assertEquals('59 minutes', Formatter::getDurationString(59));
        $this->assertEquals('1 hour', Formatter::getDurationString(60));
        $this->assertEquals('1 hour 1 minute', Formatter::getDurationString(61));
        $this->assertEquals('2 hours 2 minutes', Formatter::getDurationString(122));

        $this->assertEquals('0 min', Formatter::getDurationString(0, true));
        $this->assertEquals('1 min', Formatter::getDurationString(1, true));
        $this->assertEquals('59 min', Formatter::getDurationString(59, true));
        $this->assertEquals('1 h', Formatter::getDurationString(60, true));
        $this->assertEquals('1 h 1 min', Formatter::getDurationString(61, true));
        $this->assertEquals('2 h 2 min', Formatter::getDurationString(122, true));

        $this->assertEquals('', Formatter::getDurationString(-1));
        $this->assertEquals('9 minutes', Formatter::getDurationString('9'));
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Frontend\AnnotationIconBar
     * @uses \abrain\Einsatzverwaltung\ReportNumberController::isAutoIncidentNumbers
     * @throws ExpectationArgsRequired
     */
    public function testReturnsNumberRangeForMultiIncidentReport()
    {
        when('is_admin')->justReturn(false);
        expect('get_option')->atMost()->once()->with('einsatzvw_list_annotations_color_off', Mockery::type('string'))->andReturn('#bbb');
        when('sanitize_hex_color')->returnArg();
        $options = $this->createStub(Options::class);
        $permalinkController = $this->createStub(PermalinkController::class);
        $reportNumberController = $this->createMock(ReportNumberController::class);
        $formatter = new Formatter($options, $permalinkController, $reportNumberController);

        $report = $this->createMock(IncidentReport::class);
        $report->method('getTimeOfAlerting')->willReturn(DateTime::createFromFormat('Y-m-d', '2019-03-15'));
        $report->method('getSequentialNumber')->willReturn('41');
        $report->method('getWeight')->willReturn(3);

        expect('get_option')->once()->with('einsatzverwaltung_incidentnumbers_auto', '0')->andReturn('1');

        $reportNumberController->expects($this->once())->method('formatNumberRange')->with(2019, 41, 3)->willReturn('2019/41 – 43');
        $this->assertEquals('2019/41 – 43', $formatter->getReportNumberRange($report));
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Frontend\AnnotationIconBar
     * @throws ExpectationArgsRequired
     */
    public function testReturnsSimpleNumberForSingleReport()
    {
        when('is_admin')->justReturn(false);
        expect('get_option')->atMost()->once()->with('einsatzvw_list_annotations_color_off', Mockery::type('string'))->andReturn('#bbb');
        when('sanitize_hex_color')->returnArg();
        $options = $this->createStub(Options::class);
        $permalinkController = $this->createStub(PermalinkController::class);
        $reportNumberController = $this->createMock(ReportNumberController::class);
        $formatter = new Formatter($options, $permalinkController, $reportNumberController);

        $report = $this->createMock(IncidentReport::class);
        $report->method('getWeight')->willReturn(1);
        $report->method('getNumber')->willReturn('2020/185');

        $reportNumberController->expects($this->never())->method('formatNumberRange');
        $this->assertEquals('2020/185', $formatter->getReportNumberRange($report));
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Frontend\AnnotationIconBar
     * @uses \abrain\Einsatzverwaltung\ReportNumberController::isAutoIncidentNumbers
     * @throws ExpectationArgsRequired
     */
    public function testReturnsSimpleNumberForManualNumbers()
    {
        when('is_admin')->justReturn(false);
        expect('get_option')->atMost()->once()->with('einsatzvw_list_annotations_color_off', Mockery::type('string'))->andReturn('#bbb');
        when('sanitize_hex_color')->returnArg();
        $options = $this->createStub(Options::class);
        $permalinkController = $this->createStub(PermalinkController::class);
        $reportNumberController = $this->createMock(ReportNumberController::class);
        $formatter = new Formatter($options, $permalinkController, $reportNumberController);

        $report = $this->createMock(IncidentReport::class);
        $report->method('getNumber')->willReturn('2017/062');

        expect('get_option')->once()->with('einsatzverwaltung_incidentnumbers_auto', '0')->andReturn('0');

        $reportNumberController->expects($this->never())->method('formatNumberRange');
        $this->assertEquals('2017/062', $formatter->getReportNumberRange($report));
    }
}
