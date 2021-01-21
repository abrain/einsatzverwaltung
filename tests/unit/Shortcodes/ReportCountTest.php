<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function date;
use function intval;
use const ARRAY_A;

/**
 * Class ReportCountTest
 * @package abrain\Einsatzverwaltung\Shortcodes
 * @covers \abrain\Einsatzverwaltung\Shortcodes\AbstractShortcode
 * @covers \abrain\Einsatzverwaltung\Shortcodes\ReportCount
 * @uses \abrain\Einsatzverwaltung\Types\Unit
 */
class ReportCountTest extends UnitTestCase
{
    public function testHandlesEmptyStringInsteadOfArray()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setYear')->never();
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $reportCount = new ReportCount($reportQuery);
        $this->assertEquals('0', $reportCount->render(''));
    }

    public function testReturnsNumberOfReports()
    {
        $report1 = Mockery::mock(IncidentReport::class);
        $report1->expects('getWeight')->once()->andReturn(1);
        $report2 = Mockery::mock(IncidentReport::class);
        $report2->expects('getWeight')->once()->andReturn(1);

        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setYear')->never();
        $reportQuery->expects('getReports')->once()->andReturn([$report1, $report2]);

        $reportCount = new ReportCount($reportQuery);
        $this->assertEquals('2', $reportCount->render([]));
    }

    public function testReturnsNumberOfReportsAccountingForWeight()
    {
        $report1 = Mockery::mock(IncidentReport::class);
        $report1->expects('getWeight')->once()->andReturn(3);
        $report2 = Mockery::mock(IncidentReport::class);
        $report2->expects('getWeight')->once()->andReturn(5);

        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setYear')->never();
        $reportQuery->expects('getReports')->once()->andReturn([$report1, $report2]);

        $reportCount = new ReportCount($reportQuery);
        $this->assertEquals('8', $reportCount->render([]));
    }

    public function testConfiguresCurrentYear()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setYear')->once()->with(intval(date('Y')));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $reportCount = new ReportCount($reportQuery);
        $reportCount->render(['year' => 'current']);
    }

    public function testConfiguresGivenYear()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setYear')->once()->with(2015);
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $reportCount = new ReportCount($reportQuery);
        $reportCount->render(['year' => '2015']);
    }

    public function testConfiguresNegativeYear()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setYear')->once()->with(intval(date('Y')) - 3);
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $reportCount = new ReportCount($reportQuery);
        $reportCount->render(['year' => '-3']);
    }

    public function testConfiguresUnits()
    {
        global $wpdb;
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->termmeta = 'tm';
        $wpdb->term_taxonomy = 'tt';
        $wpdb->expects('prepare')->once()->andReturn('query9');
        $wpdb->expects('get_results')->once()->with('query9', ARRAY_A)->andReturn([
            ['term_id' => 333, 'meta_value' => 13254],
            ['term_id' => 444, 'meta_value' => 1685],
            ['term_id' => 999, 'meta_value' => 152]
        ]);

        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setUnits')->once()->with([999, 2351, 333]);
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $reportCount = new ReportCount($reportQuery);
        $reportCount->render(['units' => '152,2351,13254']);
    }

    public function testConfiguresIncidentType()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setIncidentTypeId')->once()->with(5122);
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $reportCount = new ReportCount($reportQuery);
        $reportCount->render(['einsatzart' => '5122']);
    }
}
