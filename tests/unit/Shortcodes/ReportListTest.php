<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters;
use abrain\Einsatzverwaltung\Frontend\ReportList\Renderer;
use abrain\Einsatzverwaltung\Frontend\ReportList\SplitType;
use abrain\Einsatzverwaltung\Model\IncidentReport;
use abrain\Einsatzverwaltung\ReportQuery;
use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function date;
use function in_array;
use function is_array;
use const ARRAY_A;

/**
 * Class ReportListTest
 * @package abrain\Einsatzverwaltung\Shortcodes
 * @covers \abrain\Einsatzverwaltung\Shortcodes\AbstractShortcode
 * @covers \abrain\Einsatzverwaltung\Shortcodes\ReportList
 * @uses \abrain\Einsatzverwaltung\Types\Unit
 */
class ReportListTest extends UnitTestCase
{
    public function testGetReportsDefaults()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(date('Y'));

        $reports = [Mockery::mock(IncidentReport::class), Mockery::mock(IncidentReport::class)];
        $reportQuery->expects('getReports')->once()->andReturn($reports);

        $parameters->expects('setSplitType')->once()->with(SplitType::NONE);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && count($value) === 1 && $value[0] === 'title';
        }));

        $reportListRenderer->expects('getList')->once()->with($reports, $parameters)->andReturn('the list');

        $this->assertEquals('the list', $reportList->render([]));
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testShowsSpecialReportsOfLast3YearsCompact()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setOnlySpecialReports')->once()->with(true);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(-3);
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::NONE);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && count($value) === 1 && $value[0] === 'title';
        }));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['jahr' => '-3', 'options' => 'special,compact']);
        $this->assertEquals(true, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testSupportsSortingAndLimiting()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setLimit')->once()->with(4);
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(true);
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::NONE);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && count($value) === 1 && $value[0] === 'title';
        }));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['sort' => 'auf', 'limit' => '4']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testFiltersByOldAndNewUnitIds()
    {
        // Fake the IDs for Unit ID backwards compatibility
        global $wpdb;
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->termmeta = 'tm';
        $wpdb->term_taxonomy = 'tt';
        $wpdb->expects('prepare')->once()->andReturn('query461');
        $wpdb->expects('get_results')->once()->with('query461', ARRAY_A)->andReturn([
            ['term_id' => 222, 'meta_value' => 23542],
            ['term_id' => 555, 'meta_value' => 489615],
            ['term_id' => 777, 'meta_value' => 9823]
        ]);

        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setUnits')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && count($value) === 3 &&
                in_array(222, $value) && in_array(777, $value) && in_array(411, $value);
        }));
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::NONE);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && count($value) === 1 && $value[0] === 'title';
        }));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['units' => '23542,9823,411']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testAllowsToChangeLinks()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::NONE);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && count($value) === 3 &&
                in_array('some', $value) && in_array('column', $value) && in_array('names', $value);
        }));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['link' => 'some,column, names', 'options' => 'noLinkWithoutContent']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(false, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testRemoveHeadingAndSplitQuarterly()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::QUARTERLY);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::type('array'));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['split' => 'quarterly', 'options' => 'noHeading']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(false, $parameters->showHeading);
    }

    public function testFilterByIncidentTypesAndSplitMonthly()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setIncidentTypeIds')->once()->with([684513, 12867]);
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::MONTHLY);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::type('array'));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['icategories' => '684513,12867', 'split' => 'monthly']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testRemoveLinks()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::NONE);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::on(function ($value) {
            return is_array($value) && empty($value);
        }));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['link' => 'none']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }

    public function testRespectsLegacyOptions()
    {
        $reportQuery = Mockery::mock(ReportQuery::class);
        $reportListRenderer = Mockery::mock(Renderer::class);
        $parameters = Mockery::mock(Parameters::class);
        $reportList = new ReportList($reportQuery, $reportListRenderer, $parameters);

        $reportQuery->expects('resetQueryVars')->once();
        $reportQuery->expects('setIncidentTypeIds')->once()->with([46851]);
        $reportQuery->expects('setOnlySpecialReports')->once()->with(false);
        $reportQuery->expects('setOrderAsc')->once()->with(false);
        $reportQuery->expects('setYear')->once()->with(date('Y'));
        $reportQuery->expects('getReports')->once()->andReturn([]);

        $parameters->expects('setSplitType')->once()->with(SplitType::MONTHLY);
        $parameters->expects('setColumnsLinkingReport')->once()->with(Mockery::type('array'));

        $reportListRenderer->expects('getList')->once()->andReturn('');

        $reportList->render(['monatetrennen' => 'ja', 'einsatzart' => '46851']);
        $this->assertEquals(false, $parameters->compact);
        $this->assertEquals(true, $parameters->linkEmptyReports);
        $this->assertEquals(true, $parameters->showHeading);
    }
}
