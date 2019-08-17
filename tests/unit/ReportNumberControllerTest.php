<?php
namespace abrain\Einsatzverwaltung;

/**
 * Class ReportNumberControllerTest
 * @covers \abrain\Einsatzverwaltung\ReportNumberController
 * @package abrain\Einsatzverwaltung
 */
class ReportNumberControllerTest extends UnitTestCase
{
    public function testSanitizeReportNumberDigits()
    {
        $fallback = 3;
        $this->assertEquals(2, ReportNumberController::sanitizeEinsatznummerStellen(2));
        $this->assertEquals(5, ReportNumberController::sanitizeEinsatznummerStellen('5'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(0));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(-1));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen('-2'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen('string'));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(''));
        $this->assertEquals($fallback, ReportNumberController::sanitizeEinsatznummerStellen(false));
    }
}
