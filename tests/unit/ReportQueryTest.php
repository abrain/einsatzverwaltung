<?php
namespace abrain\Einsatzverwaltung;

use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * Class ReportQueryTest
 * @covers \abrain\Einsatzverwaltung\ReportQuery
 * @package abrain\Einsatzverwaltung
 * @uses \abrain\Einsatzverwaltung\Model\IncidentReport
 * @uses \abrain\Einsatzverwaltung\Types\Unit
 */
class ReportQueryTest extends UnitTestCase
{
    public function testGetReports()
    {
        $reportQuery = new ReportQuery();
        $post1 = Mockery::mock('\WP_Post');
        $post1->ID = 45;
        $post2 = Mockery::mock('\WP_Post');
        $post2->ID = 56;
        expect('get_posts')->once()->with(Mockery::type('array'))->andReturn(array($post1, $post2));
        expect('get_post_type')->twice()->andReturn('einsatz');
        expect('get_post')->twice()->andReturnFirstArg();
        $incidentReports = $reportQuery->getReports();

        $this->assertCount(2, $incidentReports);
        foreach ($incidentReports as $incidentReport) {
            $this->assertInstanceOf('\abrain\Einsatzverwaltung\Model\IncidentReport', $incidentReport);
        }
        $this->assertEquals($post1->ID, $incidentReports[0]->getPostId());
        $this->assertEquals($post2->ID, $incidentReports[1]->getPostId());
    }

    public function testSetOrderAsc()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOrderAsc(true);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && $array['order'] == 'ASC';
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetExcludePostIds()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setExcludePostIds(array('5', '13', '26'));
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && $array['post__not_in'] == array('5', '13', '26');
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetYear()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setYear(2022);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && $array['date_query']['year'] == 2022;
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetYearToZero()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setYear(0);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && is_array($array['date_query']) && empty($array['date_query']);
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetYearToNegative()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setYear(-3);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            $currentYear = intval(date('Y'));

            return is_array($array) && !array_key_exists('year', $array['date_query']) &&
                count($array['date_query']) === 4 &&
                $array['date_query']['relation'] === 'OR' &&
                in_array(array('year' => $currentYear), $array['date_query']) &&
                in_array(array('year' => $currentYear - 1), $array['date_query']) &&
                in_array(array('year' => $currentYear - 2), $array['date_query']);
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetUnits()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setUnits(array(146,7544));
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && array_key_exists('tax_query', $array) && is_array($array['tax_query']) &&
                (!array_key_exists('relation', $array['tax_query']) || $array['tax_query']['relation'] === 'AND') &&
                in_array(array('taxonomy' => 'evw_unit', 'terms' => [146, 7544]), $array['tax_query']);
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetIncidentTypeId()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setIncidentTypeId(951);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && array_key_exists('tax_query', $array) && is_array($array['tax_query']) &&
                (!array_key_exists('relation', $array['tax_query']) || $array['tax_query']['relation'] === 'AND') &&
                in_array(array('taxonomy' => 'einsatzart', 'terms' => array(951)), $array['tax_query']);
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetIncludePrivateReports()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setIncludePrivateReports(true);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && in_array('publish', $array['post_status']) &&
                in_array('private', $array['post_status']);
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetOnlySpecialReports()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setOnlySpecialReports(true);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && array_key_exists('meta_query', $array) && is_array($array['meta_query']) &&
                (!array_key_exists('relation', $array['meta_query']) || $array['meta_query']['relation'] === 'AND') &&
                in_array(array('key' => 'einsatz_special', 'value' => '1'), $array['meta_query']);
        }))->andReturn(array());
        $reportQuery->getReports();
    }

    public function testSetLimit()
    {
        $reportQuery = new ReportQuery();
        $reportQuery->setLimit(63);
        expect('get_posts')->once()->with(Mockery::on(function ($array) {
            return is_array($array) && (
                (array_key_exists('numberposts', $array) && $array['numberposts'] === 63) ||
                (array_key_exists('posts_per_page', $array) && $array['posts_per_page'] === 63)
            );
        }))->andReturn(array());
        $reportQuery->getReports();
    }
}
