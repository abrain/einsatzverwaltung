<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;
use function date;
use function strtotime;

/**
 * Class SequenceNumberTest
 * @package abrain\Einsatzverwaltung\Tests
 *
 * Stellt sicher, dass die laufenden Nummern immer korrekt vergeben und aktualisiert werden
 */
class SequenceNumberTest extends WP_UnitTestCase
{
    /**
     * @var ReportFactory
     */
    private $reportFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->reportFactory = new ReportFactory();
    }

    /**
     * @param $expected
     * @param $reportId
     */
    private function assertSequenceNumber($expected, $reportId)
    {
        self::assertEquals($expected, get_post_meta($reportId, 'einsatz_seqNum', true));
    }

    /**
     * Data Provider für die Testmethoden
     *
     * @return array
     */
    public function yearsToTest()
    {
        return array(
            array(date('Y')),
            array(date('Y') - 1)
        );
    }

    /**
     * @dataProvider yearsToTest
     * @param int $year Das zu testende Jahr
     */
    public function testInsertReportsCurrentYear($year)
    {
        $oldReportIds = $this->reportFactory->generateManyForYear($year, 3);
        foreach ($oldReportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 1, $reportId);
        }

        // Bericht vor allen anderen einfügen
        $newReportId = $this->reportFactory->create(array('post_date' => $year.'-01-01 01:00:00'));
        $this->assertSequenceNumber(1, $newReportId);
        foreach ($oldReportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 2, $reportId);
        }

        // Als privat markierten Bericht nach dem ersten Bericht einfügen
        $newPrivateReportId = $this->reportFactory->create(array(
            'post_date' => $year.'-01-01 01:20:00',
            'post_status' => 'private'
        ));
        $this->assertSequenceNumber(1, $newReportId);
        $this->assertSequenceNumber(2, $newPrivateReportId);
        foreach ($oldReportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 3, $reportId);
        }

        // Bericht mit aktueller Zeit einfügen
        $args = array();
        if ($year != date('Y')) {
            // Bei vergangenen Jahren ganz am Ende einfügen
            $args['post_date'] = $year.'-12-31 23:30:00';
        }
        $newestReportId = $this->reportFactory->create($args);
        $this->assertSequenceNumber(1, $newReportId);
        $this->assertSequenceNumber(2, $newPrivateReportId);
        foreach ($oldReportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 3, $reportId);
        }
        $this->assertSequenceNumber(6, $newestReportId);
    }

    /**
     * @dataProvider yearsToTest
     * @param int $year Das zu testende Jahr
     */
    public function testUpdateReportCurrentYear($year)
    {
        $reportIds = $this->reportFactory->generateManyForYear($year, 5);

        // Datum austauschen
        $date1 = get_post_field('post_date', $reportIds[2]);
        $date2 = get_post_field('post_date', $reportIds[3]);
        wp_update_post(array('ID' => $reportIds[2], 'post_date' => $date2));
        wp_update_post(array('ID' => $reportIds[3], 'post_date' => $date1));
        $this->assertSequenceNumber(1, $reportIds[0]);
        $this->assertSequenceNumber(2, $reportIds[1]);
        $this->assertSequenceNumber(4, $reportIds[2]);
        $this->assertSequenceNumber(3, $reportIds[3]);
        $this->assertSequenceNumber(5, $reportIds[4]);

        // Älteren Bericht zum neuesten Bericht machen
        $lastDate = strtotime($year == date('Y') ? '3 minutes ago' : '31 December ' . $year . ' 22:30:00');
        wp_update_post(array('ID' => $reportIds[1], 'post_date' => date('Y-m-d H:i:s', $lastDate)));
        $this->assertSequenceNumber(1, $reportIds[0]);
        $this->assertSequenceNumber(2, $reportIds[3]);
        $this->assertSequenceNumber(3, $reportIds[2]);
        $this->assertSequenceNumber(4, $reportIds[4]);
        $this->assertSequenceNumber(5, $reportIds[1]);

        // Älteren Bericht zum ältesten Bericht machen
        $firstDate = strtotime('1 January ' . $year . ' 01:30:00');
        wp_update_post(array('ID' => $reportIds[2], 'post_date' => date('Y-m-d H:i:s', $firstDate)));
        $this->assertSequenceNumber(1, $reportIds[2]);
        $this->assertSequenceNumber(2, $reportIds[0]);
        $this->assertSequenceNumber(3, $reportIds[3]);
        $this->assertSequenceNumber(4, $reportIds[4]);
        $this->assertSequenceNumber(5, $reportIds[1]);
    }

    /**
     * @dataProvider yearsToTest
     * @param int $year Das zu testende Jahr
     */
    public function testTrashReportCurrentYear($year)
    {
        $reportIds = $this->reportFactory->generateManyForYear($year, 5);

        // Ersten Bericht löschen
        $reportToDelete = array_shift($reportIds);
        self::assertCount(4, $reportIds);
        wp_trash_post($reportToDelete);
        foreach ($reportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 1, $reportId);
        }

        // Bericht in der Mitte löschen
        $reportsToDelete = array_splice($reportIds, 2, 1);
        self::assertCount(3, $reportIds);
        wp_trash_post($reportsToDelete[0]);
        foreach ($reportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 1, $reportId);
        }

        // Bericht am Ende löschen
        $reportToDelete = array_pop($reportIds);
        self::assertCount(2, $reportIds);
        wp_trash_post($reportToDelete);
        foreach ($reportIds as $index => $reportId) {
            $this->assertSequenceNumber($index + 1, $reportId);
        }
    }

    public function testSkipNumbersAccordingToWeight()
    {
        $reportIds = array();
        $reportIds[] = $this->reportFactory->create(array('post_date' => date('Y-m-d H:i:s', strtotime('1 hour ago'))));
        $reportIds[] = $this->reportFactory->create(array('post_date' => date('Y-m-d H:i:s', strtotime('30 minutes ago')), 'meta_input' => array('einsatz_weight' => 3)));
        $reportIds[] = $this->reportFactory->create(array('post_date' => date('Y-m-d H:i:s', strtotime('10 minutes ago'))));

        // Make sure the weight has been taken into account
        $this->assertSequenceNumber(1, $reportIds[0]);
        $this->assertSequenceNumber(2, $reportIds[1]);
        $this->assertSequenceNumber(5, $reportIds[2]);
    }
}
