<?php
namespace abrain\Einsatzverwaltung\Shortcodes;

use abrain\Einsatzverwaltung\Frontend\ReportList\SplitType;
use abrain\Einsatzverwaltung\UnitTestCase;

/**
 * Class ReportListTest
 * @covers \abrain\Einsatzverwaltung\Shortcodes\AbstractShortcode
 * @covers \abrain\Einsatzverwaltung\Shortcodes\ReportList
 * @package abrain\Einsatzverwaltung\Shortcodes
 */
class ReportListTest extends UnitTestCase
{
    /**
     * Arbitrary URL used instead of asking WordPress for an actual URL.
     */
    const FAKE_URL = 'https://URL';

    /**
     * @var ReportList
     */
    private $reportList;

    /**
     * The current year.
     *
     * @var int
     */
    private $thisYear;

    public function setUp()
    {
        parent::setUp();
        $reportListRenderer = $this->createMock('\abrain\Einsatzverwaltung\Frontend\ReportList\Renderer');
        $this->reportList = new ReportList($reportListRenderer);
        $this->thisYear = intval(date('Y'));
    }

    public function testExtractOptions()
    {
        $this->assertEquals(array(), $this->reportList->extractOptions(array('options' => '')));
        $this->assertEquals(array(), $this->reportList->extractOptions(array('options' => 'invalidOption')));
        $this->assertEquals(
            array('special', 'noLinkWithoutContent', 'noHeading', 'compact'),
            $this->reportList->extractOptions(array('options' => 'special,noLinkWithoutContent,noHeading,compact'))
        );
        $this->assertEqualSets(
            array('special', 'noHeading'),
            $this->reportList->extractOptions(array('options' => ' noHeading,nonsense , special,,'))
        );
    }

    public function testGetReportsDefaults()
    {
        $reportQuery = $this->createMock('\abrain\Einsatzverwaltung\ReportQuery');
        $thisYear = date('Y');
        $reportQuery->expects($this->never())->method('setLimit');
        $reportQuery->expects($this->once())->method('setOnlySpecialReports')->with($this->isFalse());
        $reportQuery->expects($this->once())->method('setOrderAsc')->with($this->isFalse());
        $reportQuery->expects($this->once())->method('setYear')->with($this->equalTo($thisYear));
        $this->reportList->configureReportQuery(
            $reportQuery,
            array('limit' => -1, 'sort' => 'ab', 'jahr' => $thisYear),
            array()
        );
    }

    public function testGetReportsInvalidData()
    {
        $reportQuery = $this->createMock('\abrain\Einsatzverwaltung\ReportQuery');
        $reportQuery->expects($this->never())->method('setLimit');
        $reportQuery->expects($this->once())->method('setOnlySpecialReports')->with($this->isFalse());
        $reportQuery->expects($this->once())->method('setOrderAsc')->with($this->isFalse());
        $reportQuery->expects($this->once())->method('setYear')->with(self::equalTo(1));
        $this->reportList->configureReportQuery(
            $reportQuery,
            array('limit' => 'abc', 'sort' => 'maybe', 'jahr' => '1.5'),
            array('unknownOption')
        );
    }

    public function testGetReports()
    {
        $reportQuery = $this->createMock('\abrain\Einsatzverwaltung\ReportQuery');
        $reportQuery->expects($this->once())->method('setLimit')->with($this->equalTo(4));
        $reportQuery->expects($this->once())->method('setOnlySpecialReports')->with($this->isTrue());
        $reportQuery->expects($this->once())->method('setOrderAsc')->with($this->isTrue());
        $reportQuery->expects($this->once())->method('setYear')->with($this->equalTo('2017'));
        $this->reportList->configureReportQuery(
            $reportQuery,
            array('limit' => 4, 'sort' => 'auf', 'jahr' => '2017'),
            array('special')
        );
    }

    public function testGetReportsNegativeYear()
    {
        $reportQuery = $this->createMock('\abrain\Einsatzverwaltung\ReportQuery');
        $reportQuery->expects($this->once())->method('setLimit')->with($this->equalTo(2));
        $reportQuery->expects($this->once())->method('setOnlySpecialReports')->with($this->isFalse());
        $reportQuery->expects($this->once())->method('setOrderAsc')->with($this->isTrue());
        $reportQuery->expects($this->once())->method('setYear')->with($this->equalTo('-3'));
        $this->reportList->configureReportQuery(
            $reportQuery,
            array('limit' => '2', 'sort' => 'auf', 'jahr' => '-3'),
            array()
        );
    }

    public function testConfigureListParameters()
    {
        $parameters = $this->createMock('\abrain\Einsatzverwaltung\Frontend\ReportList\Parameters');
        $parameters->expects($this->once())->method('setColumnsLinkingReport')->with(array('title'));
        $parameters->expects($this->once())->method('setSplitType')->with(SplitType::NONE);
        // TODO test that public fields have been set
        $this->reportList->configureListParameters($parameters, array(
            'split' => 'no',
            'link' => 'title'
        ), array('special', 'noLinkWithoutContent', 'noHeading', 'compact'));
    }

    public function testConfigureListParametersNoLinks()
    {
        $parameters = $this->createMock('\abrain\Einsatzverwaltung\Frontend\ReportList\Parameters');
        $parameters->expects($this->once())->method('setColumnsLinkingReport')->with(array());
        $this->reportList->configureListParameters($parameters, array(
            'split' => 'no',
            'link' => 'title,none'
        ), array());
    }

    public function testConfigureListParametersSplitMonthly()
    {
        $parameters = $this->createMock('\abrain\Einsatzverwaltung\Frontend\ReportList\Parameters');
        $parameters->expects($this->once())->method('setColumnsLinkingReport')->with(array('title'));
        $parameters->expects($this->once())->method('setSplitType')->with(SplitType::MONTHLY);
        $this->reportList->configureListParameters($parameters, array(
            'split' => 'monthly',
            'link' => 'title'
        ), array());
    }

    public function testConfigureListParametersSplitQuarterly()
    {
        $parameters = $this->createMock('\abrain\Einsatzverwaltung\Frontend\ReportList\Parameters');
        $parameters->expects($this->once())->method('setColumnsLinkingReport')->with(array('title'));
        $parameters->expects($this->once())->method('setSplitType')->with(SplitType::QUARTERLY);
        $this->reportList->configureListParameters($parameters, array(
            'split' => 'quarterly',
            'link' => 'title'
        ), array());
    }
}
