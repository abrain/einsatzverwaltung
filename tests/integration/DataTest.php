<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Class DataTest
 * @package abrain\Einsatzverwaltung
 */
class DataTest extends WP_UnitTestCase
{
    /**
     * @group unittests
     */
    public function testGetYearsWithReports()
    {
        $data = new Data(new Options());
        $this->assertEquals(array(), $data->getYearsWithReports());

        $reportFactory = new ReportFactory();
        $reportFactory->generateManyForYear('2013', 2);
        $reportFactory->generateManyForYear('2016', 2);
        $reportFactory->generateManyForYear('2017', 2);
        $yearsWithReports = $data->getYearsWithReports();
        $this->assertEqualSets(array(2013, 2016, 2017), $yearsWithReports);
        foreach ($yearsWithReports as $year) {
            $this->assertInternalType('int', $year);
        }
    }
}
