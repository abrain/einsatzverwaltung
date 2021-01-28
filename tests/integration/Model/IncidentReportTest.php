<?php
namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\ReportFactory;
use WP_UnitTestCase;

/**
 * Class IncidentReportTest
 * @package abrain\Einsatzverwaltung\Model
 */
class IncidentReportTest extends WP_UnitTestCase
{
    public function testGetDuration()
    {
        $reportFactory = new ReportFactory();

        // Beginning and end are outside of DST
        $reportId = $reportFactory->create(array(
            'post_date' => '2020-02-03 05:22:00',
            'meta_input' => array('einsatz_einsatzende' => '2020-02-03 08:04:00')
        ));
        $report = new IncidentReport($reportId);
        $this->assertEquals(162, $report->getDuration());
    }

    /**
     * The switch to DST happens between beginning and end
     */
    public function testGetDurationSwitchToDST()
    {
        $reportFactory = new ReportFactory();
        $reportdId = $reportFactory->create(array(
            'post_date' => '2020-03-29 01:49:00',
            'meta_input' => array('einsatz_einsatzende' => '2020-03-29 03:15:00')
        ));
        $report = new IncidentReport($reportdId);
        $this->assertEquals(26, $report->getDuration());
    }

    /**
     * Beginning and end are during DST
     */
    public function testGetDurationDuringDST()
    {
        $reportFactory = new ReportFactory();
        $reportdId = $reportFactory->create(array(
            'post_date' => '2020-03-29 11:03:00',
            'meta_input' => array('einsatz_einsatzende' => '2020-03-29 11:55:00')
        ));
        $report = new IncidentReport($reportdId);
        $this->assertEquals(52, $report->getDuration());
    }

    /**
     * The switch from DST happens between beginning and end
     */
    public function testGetDurationSwitchFromDST()
    {
        $reportFactory = new ReportFactory();
        $reportdId = $reportFactory->create(array(
            'post_date' => '2019-10-27 01:54:00',
            'meta_input' => array('einsatz_einsatzende' => '2019-10-27 03:07:00')
        ));
        $report = new IncidentReport($reportdId);
        $this->assertEquals(133, $report->getDuration());
    }
}
