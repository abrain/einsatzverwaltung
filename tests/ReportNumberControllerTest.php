<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Class ReportNumberControllerTest
 * @package abrain\Einsatzverwaltung
 */
class ReportNumberControllerTest extends WP_UnitTestCase
{
    public function testFormatEinsatznummer()
    {
        $controller = new ReportNumberController();
        update_option('einsatzvw_einsatznummer_stellen', '3');
        update_option('einsatzvw_einsatznummer_lfdvorne', '0');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '4');
        $this->assertEquals('20170012', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '2');
        $this->assertEquals('201712', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '1');
        $this->assertEquals('201712', $controller->formatEinsatznummer('2017', 12));

        update_option('einsatzvw_einsatznummer_stellen', '3');
        update_option('einsatzvw_einsatznummer_lfdvorne', '1');
        $this->assertEquals('0122017', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '4');
        $this->assertEquals('00122017', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '2');
        $this->assertEquals('122017', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '1');
        $this->assertEquals('122017', $controller->formatEinsatznummer('2017', 12));

        // invalid optoins
        update_option('einsatzvw_einsatznummer_stellen', '0');
        update_option('einsatzvw_einsatznummer_lfdvorne', 'test');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '-2');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', '');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_stellen', 'bla');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        delete_option('einsatzvw_einsatznummer_stellen');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        update_option('einsatzvw_einsatznummer_lfdvorne', '2');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
        delete_option('einsatzvw_einsatznummer_lfdvorne');
        $this->assertEquals('2017012', $controller->formatEinsatznummer('2017', 12));
    }

    public function testSanitizeEinsatznummerStellen()
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
