<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

use abrain\Einsatzverwaltung\UnitTestCase;
use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters as ReportListParameters;
use function Brain\Monkey\Functions\when;

/**
 * Class ReportListParametersTest
 * @covers \abrain\Einsatzverwaltung\Frontend\ReportList\Parameters
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 * @uses \abrain\Einsatzverwaltung\Frontend\ReportList\Column
 * @uses \abrain\Einsatzverwaltung\Frontend\ReportList\ColumnRepository
 */
class ReportListParametersTest extends UnitTestCase
{
    public function testConstructDefaults()
    {
        when('get_option')->alias(function ($name, $default = false) {
            return $default;
        });
        $parameters = new ReportListParameters();
        $this->assertFalse($parameters->compact);
        $this->assertTrue($parameters->showHeading);
        $this->assertTrue($parameters->linkEmptyReports);
        $this->assertFalse($parameters->linkAdditionalForces);
        $this->assertFalse($parameters->linkVehicles);
        $this->assertColumnsById(explode(',', ColumnRepository::DEFAULT_COLUMNS), $parameters->getColumns());
        $this->assertEquals(array('title'), $parameters->getColumnsLinkingReport());
    }

    public function testConstructInvalidColumns()
    {
        when('get_option')->alias(function ($name, $default = false) {
            return $name === 'einsatzvw_list_columns' ? 'invalid,bla' : $default;
        });
        $parameters = new ReportListParameters();
        $this->assertColumnsById(explode(',', ColumnRepository::DEFAULT_COLUMNS), $parameters->getColumns());
    }

    public function testConstructMixedColumns()
    {
        when('get_option')->alias(function ($name, $default = false) {
            return $name === 'einsatzvw_list_columns' ? 'invalid,title,bla,date' : $default;
        });
        $parameters = new ReportListParameters();
        $this->assertColumnsById(array('title', 'date'), $parameters->getColumns());
    }

    /**
     * @param Column[] $expected
     * @param mixed $actual
     */
    private function assertColumnsById($expected, $actual)
    {
        $this->assertNotEmpty($actual);
        $columnIds = array();
        foreach ($actual as $column) {
            $this->assertInstanceOf('abrain\Einsatzverwaltung\Frontend\ReportList\Column', $column);
            $columnIds[] = $column->getIdentifier();
        }
        $this->assertEqualSets($expected, $columnIds);
    }

    public function testIsSplitMonths()
    {
        when('get_option')->alias(function ($name, $default = false) {
            return $default;
        });
        $parameters = new ReportListParameters();
        $this->assertFalse($parameters->isSplitMonths());
        $parameters->setSplitType(SplitType::MONTHLY);
        $this->assertTrue($parameters->isSplitMonths());
        $parameters->compact = true;
        $this->assertFalse($parameters->isSplitMonths());
    }

    public function testGetColumnsLinkingReport()
    {
        when('get_option')->alias(function ($name, $default = false) {
            return $default;
        });
        $parameters = new ReportListParameters();
        $this->assertEquals(array('title'), $parameters->getColumnsLinkingReport());

        $parameters->setColumnsLinkingReport(array());
        $this->assertEquals(array(), $parameters->getColumnsLinkingReport());

        $parameters->setColumnsLinkingReport(array('invalid'));
        $this->assertEquals(array(), $parameters->getColumnsLinkingReport());

        $parameters->setColumnsLinkingReport(array('date', 'invalid', 'number'));
        $this->assertEquals(array('date', 'number'), $parameters->getColumnsLinkingReport());
    }
}
