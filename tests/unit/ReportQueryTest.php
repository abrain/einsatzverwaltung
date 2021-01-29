<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Brain\Monkey\Expectation\Exception\NotAllowedMethod;
use Mockery;
use function array_key_exists;
use function Brain\Monkey\Functions\expect;
use function in_array;
use function is_array;

/**
 * Class ReportQueryTest
 * @package abrain\Einsatzverwaltung
 * @covers \abrain\Einsatzverwaltung\ReportQuery
 * @uses \abrain\Einsatzverwaltung\Model\IncidentReport
 * @uses \abrain\Einsatzverwaltung\Types\Unit
 */
class ReportQueryTest extends UnitTestCase
{
    /**
     * @throws ExpectationArgsRequired
     * @throws NotAllowedMethod
     */
    public function testGetReports()
    {
        $reportQuery = new ReportQuery();
        $post1 = Mockery::mock('\WP_Post');
        $post1->ID = 45;
        $post2 = Mockery::mock('\WP_Post');
        $post2->ID = 56;
        expect('get_posts')->once()->with(Mockery::type('array'))->andReturn([$post1, $post2]);
        expect('get_post_type')->twice()->andReturn('einsatz');
        expect('get_post')->twice()->andReturnFirstArg();
        $incidentReports = $reportQuery->getReports();

        $this->assertCount(2, $incidentReports);
        foreach ($incidentReports as $incidentReport) {
            $this->assertInstanceOf(IncidentReport::class, $incidentReport);
        }
        $this->assertEquals($post1->ID, $incidentReports[0]->getPostId());
        $this->assertEquals($post2->ID, $incidentReports[1]->getPostId());
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetOrderAsc()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOrderAsc(true);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && $array['order'] == 'ASC';
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetExcludePostIds()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setExcludePostIds(['5', '13', '26']);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && $array['post__not_in'] == ['5', '13', '26'];
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetYear()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setYear(2022);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && $array['date_query']['year'] == 2022;
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetYearToZero()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setYear(0);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && is_array($array['date_query']) && empty($array['date_query']);
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetYearToNegative()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setYear(-3);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            $currentYear = intval(date('Y'));

            return is_array($array) && !array_key_exists('year', $array['date_query']) &&
                count($array['date_query']) === 4 &&
                $array['date_query']['relation'] === 'OR' &&
                in_array(['year' => $currentYear], $array['date_query']) &&
                in_array(['year' => $currentYear - 1], $array['date_query']) &&
                in_array(['year' => $currentYear - 2], $array['date_query']);
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetUnits()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setUnits([146,7544]);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && array_key_exists('tax_query', $array) && is_array($array['tax_query']) &&
                (!array_key_exists('relation', $array['tax_query']) || $array['tax_query']['relation'] === 'AND') &&
                in_array(['taxonomy' => 'evw_unit', 'terms' => [146, 7544]], $array['tax_query']);
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetIncidentTypeIds()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setIncidentTypeIds([951]);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && array_key_exists('tax_query', $array) && is_array($array['tax_query']) &&
                (!array_key_exists('relation', $array['tax_query']) || $array['tax_query']['relation'] === 'AND') &&
                in_array(['taxonomy' => 'einsatzart', 'terms' => [951]], $array['tax_query']);
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetIncludePrivateReports()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setIncludePrivateReports(true);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && in_array('publish', $array['post_status']) &&
                in_array('private', $array['post_status']);
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetOnlySpecialReports()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOnlySpecialReports(true);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && array_key_exists('meta_query', $array) && is_array($array['meta_query']) &&
                (!array_key_exists('relation', $array['meta_query']) || $array['meta_query']['relation'] === 'AND') &&
                in_array(['key' => 'einsatz_special', 'value' => '1'], $array['meta_query']);
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testSetLimit()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setLimit(63);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && (
                (array_key_exists('numberposts', $array) && $array['numberposts'] === 63) ||
                (array_key_exists('posts_per_page', $array) && $array['posts_per_page'] === 63)
            );
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testOnlyReturnFalseAlarms()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOnlyReportStatus([ReportStatus::FALSE_ALARM]);
        expect('get_posts')->once()->with(Mockery::on(function ($args) {
            return is_array($args) && array_key_exists('meta_query', $args) && is_array($args['meta_query']) &&
                (!array_key_exists('relation', $args['meta_query']) || $args['meta_query']['relation'] === 'AND') &&
                $args['meta_query'][0] === ['key' => 'einsatz_fehlalarm', 'value' => '1'] &&
                count($args['meta_query']) === 1;
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testOnlyReturnActualAlarms()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOnlyReportStatus([ReportStatus::ACTUAL]);
        expect('get_posts')->once()->with(Mockery::on(function ($args) {
            return is_array($args) && array_key_exists('meta_query', $args) && is_array($args['meta_query']) &&
                (!array_key_exists('relation', $args['meta_query']) || $args['meta_query']['relation'] === 'AND') &&
                in_array(['key' => 'einsatz_fehlalarm', 'value' => '0'], $args['meta_query'][0]) &&
                in_array(['key' => 'einsatz_fehlalarm', 'compare' => 'NOT EXISTS'], $args['meta_query'][0]) &&
                $args['meta_query'][0]['relation'] === 'OR';
        }))->andReturn([]);
        $reportQuery->getReports();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testOnlyReturnFalseAndActualAlarms()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOnlyReportStatus([ReportStatus::ACTUAL, ReportStatus::FALSE_ALARM]);
        expect('get_posts')->once()->with(Mockery::on(function ($args) {
            return is_array($args) && array_key_exists('meta_query', $args) && is_array($args['meta_query']) &&
                (!array_key_exists('relation', $args['meta_query']) || $args['meta_query']['relation'] === 'AND') &&
                $args['meta_query'][0]['relation'] === 'OR' &&
                in_array(['key' => 'einsatz_fehlalarm', 'value' => '1'], $args['meta_query'][0]) &&
                in_array(['key' => 'einsatz_fehlalarm', 'value' => '0'], $args['meta_query'][0]);
        }))->andReturn([]);
        $reportQuery->getReports();
    }
}
