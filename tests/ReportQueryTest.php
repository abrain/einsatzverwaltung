<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;
use WP_UnitTestCase;

class ReportQueryTest extends WP_UnitTestCase
{
    /**
     * @var int
     */
    private $incidentType1Id;

    /**
     * @var int
     */
    private $incidentType1aId;

    private $postIds;

    public function setUp()
    {
        parent::setUp();

        $parentTerm = wp_insert_term('Type 1', 'einsatzart');
        if (is_wp_error($parentTerm)) {
            self::fail('Could not create parent term');
        }
        $this->incidentType1Id = $parentTerm['term_id'];
        $childTerm = wp_insert_term('Type 1 a', 'einsatzart', array('parent' => $this->incidentType1Id));
        if (is_wp_error($childTerm)) {
            self::fail('Could not create child term');
        }
        $this->incidentType1aId = $childTerm['term_id'];

        $currentYear = date('Y');
        $this->postIds = $this->factory->post->create_many(10, array('post_type' => 'einsatz'));
        wp_update_post(array('ID' => $this->postIds[0], 'post_status' => 'draft'));
        wp_update_post(array('ID' => $this->postIds[1], 'post_status' => 'private'));
        wp_update_post(array('ID' => $this->postIds[2],
            'post_date' => date('Y-m-d H:i:s', strtotime('1 January ' . ($currentYear - 1)))
        ));
        wp_update_post(array('ID' => $this->postIds[3],
            'post_date' => date('Y-m-d H:i:s', strtotime('1 January ' . ($currentYear - 2)))
        ));
        wp_update_post(array('ID' => $this->postIds[4],
            'post_date' => date('Y-m-d H:i:s', strtotime('2 January ' . ($currentYear - 2)))
        ));

        for ($i = 5; $i < 10; $i++) {
            $time = strtotime((10 - $i) . " minutes ago");
            wp_update_post(array('ID' => $this->postIds[$i], 'post_date' => date('Y-m-d H:i:s', $time)));
        }

        // Zwei Berichte als besonders markieren
        update_post_meta($this->postIds[3], 'einsatz_special', 1);
        update_post_meta($this->postIds[5], 'einsatz_special', 1);

        // Einsatzarten zuweisen
        wp_set_object_terms($this->postIds[2], array($this->incidentType1Id), 'einsatzart');
        wp_set_object_terms($this->postIds[5], array($this->incidentType1aId), 'einsatzart');
        wp_set_object_terms($this->postIds[8], array($this->incidentType1aId), 'einsatzart');
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

    public function testLastXYears()
    {
        $query = new ReportQuery();
        $query->setYear(-1);
        $reports = $query->getReports();
        $this->assertCount(5, $reports);

        $query->setYear(-2);
        $reports = $query->getReports();
        $this->assertCount(6, $reports);

        $query->setYear(-3);
        $reports = $query->getReports();
        $this->assertCount(8, $reports);
    }

    public function testSpecificYear()
    {
        $currentYear = intval(date('Y'));

        $query = new ReportQuery();
        $query->setYear($currentYear);
        $reports = $query->getReports();
        $this->assertCount(5, $reports);

        $query->setYear($currentYear - 1);
        $reports = $query->getReports();
        $this->assertCount(1, $reports);

        $query->setYear($currentYear - 2);
        $reports = $query->getReports();
        $this->assertCount(2, $reports);
    }

    public function testExcludePosts()
    {
        $query = new ReportQuery();
        $query->setExcludePostIds(array($this->postIds[4], $this->postIds[7]));
        $reports = $query->getReports();
        $this->assertCount(6, $reports);
        $queriedPostIds = array_map(function (IncidentReport $report) {
            return $report->getPostId();
        }, $reports);
        $expectedPostIds = array_diff_key($this->postIds, array_flip(array(0,1,4,7)));
        self::assertEmpty(array_diff($queriedPostIds, $expectedPostIds));
    }

    public function testFilterIncidentTypeChild()
    {
        $query = new ReportQuery();
        $query->setIncidentTypeId($this->incidentType1aId);
        $reports = $query->getReports();
        $this->assertCount(2, $reports);
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $typeOfIncident = $report->getTypeOfIncident();
            $this->assertNotNull($typeOfIncident);
            $this->assertEquals($this->incidentType1aId, $typeOfIncident->term_id);
        }
    }

    public function testFilterIncidentTypeParent()
    {
        $query = new ReportQuery();
        $query->setIncidentTypeId($this->incidentType1Id);
        $reports = $query->getReports();
        $this->assertCount(3, $reports);
        /** @var IncidentReport $report */
        foreach ($reports as $report) {
            $typeOfIncident = $report->getTypeOfIncident();
            $this->assertNotNull($typeOfIncident);
            $this->assertTrue(in_array(
                $typeOfIncident->term_id,
                array($this->incidentType1Id, $this->incidentType1aId)
            ));
        }
    }
}
