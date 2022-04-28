<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\UnitTestCase;
use function Brain\Monkey\Functions\when;

/**
 * Class ReportArchivesTest
 * @covers \abrain\Einsatzverwaltung\Shortcodes\AbstractShortcode
 * @covers \abrain\Einsatzverwaltung\Shortcodes\ReportArchives
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
class ReportArchivesTest extends UnitTestCase
{
    const FAKE_URL = 'https://URL';

    /**
     * @var ReportArchives
     */
    private $reportArchives;

    /**
     * The current year.
     *
     * @var int
     */
    private $thisYear;

    public function setUp(): void
    {
        parent::setUp();
        $permalinkController = $this->createMock('\abrain\Einsatzverwaltung\PermalinkController');
        $permalinkController->method('getYearArchiveLink')->willReturn(self::FAKE_URL);
        $data = $this->createMock('\abrain\Einsatzverwaltung\Data');
        $data->method('getYearsWithReports')->willReturn(array(2013, 2014, 2016, 2017));
        $this->reportArchives = new ReportArchives($data, $permalinkController);
        $this->thisYear = intval(date('Y'));
    }

    /**
     * Generates the expected markup for a certain year
     *
     * @param int $year
     * @param bool $currentYear
     *
     * @return string
     */
    private function getAnchor($year, $currentYear = false)
    {
        $format = $currentYear ? '<a href="%s" aria-current="page"><strong>%d</strong></a>' : '<a href="%s">%d</a>';
        return sprintf($format, self::FAKE_URL, $year);
    }

    /**
     * @param int[] $years
     * @param int $queriedYear
     *
     * @return string
     */
    private function getMarkup($years, $queriedYear = 0)
    {
        $anchors = array();
        foreach ($years as $year) {
            $anchors[] = $this->getAnchor($year, $year === $queriedYear);
        }
        return join(' | ', $anchors);
    }

    public function testNoAttributes()
    {
        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'DESC',
        ));
        $result = $this->reportArchives->render(array());
        $this->assertEquals($this->getMarkup(array($this->thisYear, 2017, 2016, 2014, 2013), $this->thisYear), $result);
    }

    public function testSortAsc()
    {
        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'ASC',
        ));
        $result = $this->reportArchives->render(array('sort' => 'ASC'));
        $this->assertEquals($this->getMarkup(array(2013, 2014, 2016, 2017, $this->thisYear), $this->thisYear), $result);
    }

    public function testSortDescAndLimit()
    {
        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 3,
            'sort' => 'DESC',
        ));
        $result = $this->reportArchives->render(array('sort' => 'DESC', 'limit' => 3));
        $this->assertEquals($this->getMarkup(array($this->thisYear, 2017, 2016), $this->thisYear), $result);
    }

    public function testNoQueriedYear()
    {
        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'no',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'DESC',
        ));
        $result = $this->reportArchives->render(array(
            'add_queried_year' => 'no'
        ));
        $this->assertEquals($this->getMarkup(array(2017, 2016, 2014, 2013)), $result);
    }

    public function testQueryOtherYear()
    {
        global $year;
        $year = 2017;

        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'DESC',
        ));
        $result = $this->reportArchives->render(array());
        $this->assertEquals($this->getMarkup(array(2017, 2016, 2014, 2013), 2017), $result);
    }

    public function testQueryEmptyYear()
    {
        global $year;
        $year = 2011;

        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'yes',
            'force_current_year' => 'no',
            'limit' => 0,
            'sort' => 'DESC',
        ));
        $result = $this->reportArchives->render(array());
        $this->assertEquals($this->getMarkup(array(2017, 2016, 2014, 2013, 2011), 2011), $result);
    }

    public function testForceCurrentYear()
    {
        global $year;
        $year = $this->thisYear;

        when('shortcode_atts')->justReturn(array(
            'add_queried_year' => 'no',
            'force_current_year' => 'yes',
            'limit' => 0,
            'sort' => 'DESC',
        ));
        $result = $this->reportArchives->render(array(
            'add_queried_year' => 'no',
            'force_current_year' => 'yes'
        ));
        $this->assertEquals($this->getMarkup(array($this->thisYear, 2017, 2016, 2014, 2013), $this->thisYear), $result);
    }
}
