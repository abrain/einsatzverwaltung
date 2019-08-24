<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Class DataTest
 * @package abrain\Einsatzverwaltung
 */
class DataTest extends WP_UnitTestCase
{
    public function testGetJahreMitEinsatz()
    {
        $this->assertEquals(array(), Data::getJahreMitEinsatz());

        $reportFactory = new ReportFactory();
        $reportFactory->generateManyForYear('2014', 2);
        $reportFactory->generateManyForYear('2015', 2);
        $reportFactory->generateManyForYear('2017', 2);
        $jahreMitEinsatz = Data::getJahreMitEinsatz();
        $this->assertEqualSets(array(2014, 2015, 2017), $jahreMitEinsatz);
        foreach ($jahreMitEinsatz as $jahr) {
            $this->assertInternalType('string', $jahr);
        }
    }

    /**
     * @group unittests
     */
    public function testGetYearsWithReports()
    {
        $options = $this->createMock('abrain\Einsatzverwaltung\Options');
        $data = new Data($options);
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
