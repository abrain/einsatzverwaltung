<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * Class ReportListSettingsTest
 * @covers \abrain\Einsatzverwaltung\Frontend\ReportList\Settings
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class SettingsTest extends UnitTestCase
{
    const DEFAULT_NTHCHILD = 'even';
    const DEFAULT_ZEBRACOLOR = '#eee';

    public function testGetZebraNthChildArg()
    {
        $reportListSettings = new Settings();
        expect('get_option')->once()->with('einsatzvw_list_zebra_nth', Mockery::any())->andReturnUsing(function ($name, $default = false) {
            return $default;
        });
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        expect('get_option')->times(5)->with('einsatzvw_list_zebra_nth', Mockery::any())->andReturn('', 'invalid', 5, 'odd', 'even');
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        $this->assertEquals('odd', $reportListSettings->getZebraNthChildArg());
        $this->assertEquals('even', $reportListSettings->getZebraNthChildArg());
    }

    public function testSanitizeZebraNthChildArg()
    {
        $reportListSettings = new Settings();
        $this->assertEquals('odd', $reportListSettings->sanitizeZebraNthChildArg('odd'));
        $this->assertEquals('even', $reportListSettings->sanitizeZebraNthChildArg('even'));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg(''));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg('invalid'));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg(2));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg(null));
    }

    /**
     * @dataProvider zebraColorData
     *
     * @param string $optionValue
     * @param string|null $sanitizedColor
     * @param string $expected
     */
    public function testGetZebraColor($optionValue, $sanitizedColor, $expected)
    {
        $reportListSettings = new Settings();
        expect('get_option')->once()->with('einsatzvw_list_zebracolor', Mockery::any())->andReturn($optionValue);
        expect('sanitize_hex_color')->once()->with($optionValue)->andReturn($sanitizedColor);
        $this->assertEquals($expected, $reportListSettings->getZebraColor());
    }

    /**
     * Test data for testGetZebraColor
     *
     * @return array
     */
    public function zebraColorData()
    {
        return array(
            array('some_option_value', null, self::DEFAULT_ZEBRACOLOR),
            array('', null, self::DEFAULT_ZEBRACOLOR),
            array('#ddd', '#ddd', '#ddd'),
            array('#d0d0d0', '#d0d0d0', '#d0d0d0')
        );
    }

    public function testIsZebraTable()
    {
        $reportListSettings = new Settings();
        expect('get_option')->once()->with('einsatzvw_list_zebra', Mockery::any())->andReturnUsing(function ($name, $default = false) {
            return $default;
        });
        $this->assertTrue($reportListSettings->isZebraTable());
        expect('get_option')->times(4)->with('einsatzvw_list_zebra', Mockery::any())->andReturn('0', '1', 'invalid', 0);
        $this->assertFalse($reportListSettings->isZebraTable());
        $this->assertTrue($reportListSettings->isZebraTable());
        $this->assertTrue($reportListSettings->isZebraTable());
        $this->assertTrue($reportListSettings->isZebraTable());
    }
}
