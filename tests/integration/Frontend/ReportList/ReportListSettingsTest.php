<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

use WP_UnitTestCase;

/**
 * Class ReportListSettingsTest
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class ReportListSettingsTest extends WP_UnitTestCase
{
    const DEFAULT_NTHCHILD = 'even';
    const DEFAULT_ZEBRACOLOR = '#eee';

    public function testGetZebraColor()
    {
        $reportListSettings = new Settings();
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
}
