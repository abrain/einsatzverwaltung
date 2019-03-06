<?php
namespace abrain\Einsatzverwaltung\Frontend;

use WP_UnitTestCase;

class ReportListSettingsTest extends WP_UnitTestCase
{
    const DEFAULT_NTHCHILD = 'even';
    const DEFAULT_ZEBRACOLOR = '#eee';

    public function testGetZebraNthChildArg()
    {
        $reportListSettings = new ReportListSettings();
        delete_option('einsatzvw_list_zebra_nth');
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        update_option('einsatzvw_list_zebra_nth', '');
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        update_option('einsatzvw_list_zebra_nth', 'invalid');
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        update_option('einsatzvw_list_zebra_nth', 5);
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->getZebraNthChildArg());
        update_option('einsatzvw_list_zebra_nth', 'odd');
        $this->assertEquals('odd', $reportListSettings->getZebraNthChildArg());
        update_option('einsatzvw_list_zebra_nth', 'even');
        $this->assertEquals('even', $reportListSettings->getZebraNthChildArg());
    }

    public function testSanitizeZebraNthChildArg()
    {
        $reportListSettings = new ReportListSettings();
        $this->assertEquals('odd', $reportListSettings->sanitizeZebraNthChildArg('odd'));
        $this->assertEquals('even', $reportListSettings->sanitizeZebraNthChildArg('even'));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg(''));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg('invalid'));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg(2));
        $this->assertEquals(self::DEFAULT_NTHCHILD, $reportListSettings->sanitizeZebraNthChildArg(null));
    }

    public function testGetZebraColor()
    {
        $reportListSettings = new ReportListSettings();
        delete_option('einsatzvw_list_zebracolor');
        $this->assertEquals(self::DEFAULT_ZEBRACOLOR, $reportListSettings->getZebraColor());
        update_option('einsatzvw_list_zebracolor', '');
        $this->assertEquals(self::DEFAULT_ZEBRACOLOR, $reportListSettings->getZebraColor());
        update_option('einsatzvw_list_zebracolor', '#ddd');
        $this->assertEquals('#ddd', $reportListSettings->getZebraColor());
        update_option('einsatzvw_list_zebracolor', '#d0d0d0');
        $this->assertEquals('#d0d0d0', $reportListSettings->getZebraColor());
        update_option('einsatzvw_list_zebracolor', '#zzz');
        $this->assertEquals(self::DEFAULT_ZEBRACOLOR, $reportListSettings->getZebraColor());
        update_option('einsatzvw_list_zebracolor', 'abc');
        $this->assertEquals(self::DEFAULT_ZEBRACOLOR, $reportListSettings->getZebraColor());
        update_option('einsatzvw_list_zebracolor', 3);
        $this->assertEquals(self::DEFAULT_ZEBRACOLOR, $reportListSettings->getZebraColor());
    }

    public function testIsZebraTable()
    {
        $reportListSettings = new ReportListSettings();
        delete_option('einsatzvw_list_zebra');
        $this->assertTrue($reportListSettings->isZebraTable());
        update_option('einsatzvw_list_zebra', '0');
        $this->assertFalse($reportListSettings->isZebraTable());
        update_option('einsatzvw_list_zebra', '1');
        $this->assertTrue($reportListSettings->isZebraTable());
        update_option('einsatzvw_list_zebra', 'invalid');
        $this->assertTrue($reportListSettings->isZebraTable());
        update_option('einsatzvw_list_zebra', 0);
        $this->assertTrue($reportListSettings->isZebraTable());
    }
}
