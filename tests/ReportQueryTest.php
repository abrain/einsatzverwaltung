<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use WP_UnitTestCase;

class ReportQueryTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        $postIds = $this->factory->post->create_many(10, array('post_type' => 'einsatz'));
        wp_update_post(array('ID' => $postIds[0], 'post_status' => 'draft'));
        wp_update_post(array('ID' => $postIds[1], 'post_status' => 'private'));
        for ($i = 2; $i < 10; $i++) {
            $time = strtotime((10 - $i) . " minutes ago");
            wp_update_post(array('ID' => $postIds[$i], 'post_date' => date('Y-m-d H:i:s', $time)));
        }

        // Zwei Berichte als besonders markeiren
        update_post_meta($postIds[3], 'einsatz_special', 1);
        update_post_meta($postIds[5], 'einsatz_special', 1);
    }

    public function testGetAllPublishedReports()
    {
        $query = new ReportQuery();
        $query->setIncludePrivateReports(true);
        $reports = $query->getReports();
        $this->assertCount(9, $reports);
        foreach ($reports as $report) {
            $this->assertInstanceOf('abrain\Einsatzverwaltung\Model\IncidentReport', $report);
        }
    }

    public function testGetAllPublicReports()
    {
        $query = new ReportQuery();
        $reports = $query->getReports();
        $this->assertCount(8, $reports);
        foreach ($reports as $report) {
            $this->assertInstanceOf('abrain\Einsatzverwaltung\Model\IncidentReport', $report);
        }
    }

    public function testGetCertainNumberOfReports()
    {
        $query = new ReportQuery();
        $query->setLimit(3);
        $reports = $query->getReports();
        $this->assertCount(3, $reports);
    }

    public function testOrderAsc()
    {
        $query = new ReportQuery();
        $reports = $query->getReports();
        $lastTimestamp = 0;
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $timeOfAlerting = $report->getTimeOfAlerting();
            $timestamp = $timeOfAlerting->getTimestamp();
            $this->assertGreaterThan($lastTimestamp, $timestamp);
            $lastTimestamp = $timestamp;
        }
    }

    public function testOrderDesc()
    {
        $query = new ReportQuery();
        $query->setOrderAsc(false);
        $reports = $query->getReports();
        $lastTimestamp = PHP_INT_MAX;
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $timeOfAlerting = $report->getTimeOfAlerting();
            $timestamp = $timeOfAlerting->getTimestamp();
            $this->assertLessThan($lastTimestamp, $timestamp);
            $lastTimestamp = $timestamp;
        }
    }

    public function testOnlySpecialReports()
    {
        $query = new ReportQuery();
        $query->setOnlySpecialReports(true);
        $reports = $query->getReports();
        $this->assertCount(2, $reports);
    }
}
