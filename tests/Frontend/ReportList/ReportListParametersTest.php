<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

use WP_UnitTestCase;
use abrain\Einsatzverwaltung\Frontend\ReportList\Parameters as ReportListParameters;

/**
 * Class ReportListParametersTest
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 */
class ReportListParametersTest extends WP_UnitTestCase
{
    public function testSanitizeColumns()
    {
        $this->assertEquals(ColumnRepository::DEFAULT_COLUMNS, ColumnRepository::sanitizeColumns(''));
        $this->assertEquals(ColumnRepository::DEFAULT_COLUMNS, ColumnRepository::sanitizeColumns(','));
        $this->assertEquals(ColumnRepository::DEFAULT_COLUMNS, ColumnRepository::sanitizeColumns('bla'));
        $this->assertEquals(ColumnRepository::DEFAULT_COLUMNS, ColumnRepository::sanitizeColumns('bla,invalid'));
        $this->assertEquals('title', ColumnRepository::sanitizeColumns('title'));
        $this->assertEquals('title', ColumnRepository::sanitizeColumns('title,invalid'));
        $this->assertEquals('title,date', ColumnRepository::sanitizeColumns('title,invalid,date'));
    }

    public function testConstructDefaults()
    {
        delete_option('einsatzvw_list_columns');
        delete_option('einsatzvw_list_ext_link');
        delete_option('einsatzvw_list_fahrzeuge_link');
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
        update_option('einsatzvw_list_columns', 'invalid,bla');
        $parameters = new ReportListParameters();
        $this->assertColumnsById(explode(',', ColumnRepository::DEFAULT_COLUMNS), $parameters->getColumns());
    }

    public function testConstructMixedColumns()
    {
        update_option('einsatzvw_list_columns', 'invalid,title,bla,date');
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
        $parameters = new ReportListParameters();
        $this->assertFalse($parameters->isSplitMonths());
        $parameters->setSplitType(SplitType::MONTHLY);
        $this->assertTrue($parameters->isSplitMonths());
        $parameters->compact = true;
        $this->assertFalse($parameters->isSplitMonths());
    }

    public function testSanitizeColumnsArrayNoDefault()
    {
        $this->assertEquals(array(), ColumnRepository::sanitizeColumnsArrayNoDefault(array()));
        $this->assertEquals(array(), ColumnRepository::sanitizeColumnsArrayNoDefault(array('bla', 'inexistent')));
        $this->assertEquals(array('title', 'vehicles'), ColumnRepository::sanitizeColumnsArrayNoDefault(array('title', 'invalid', 'vehicles')));
        $this->assertEquals(array('title'), ColumnRepository::sanitizeColumnsArrayNoDefault(array('title', 9)));
        $this->assertEquals(array(), ColumnRepository::sanitizeColumnsArrayNoDefault(array(12)));
    }

    public function testSanitizeColumnsArray()
    {
        $defaultColumns = explode(',', ColumnRepository::DEFAULT_COLUMNS);
        $this->assertEquals($defaultColumns, ColumnRepository::sanitizeColumnsArray(array()));
        $this->assertEquals($defaultColumns, ColumnRepository::sanitizeColumnsArray(array('bla', 'inexistent')));
        $this->assertEquals(array('title', 'vehicles'), ColumnRepository::sanitizeColumnsArray(array('title', 'invalid', 'vehicles')));
        $this->assertEquals(array('title'), ColumnRepository::sanitizeColumnsArray(array('title', 9)));
        $this->assertEquals($defaultColumns, ColumnRepository::sanitizeColumnsArray(array(12)));
    }

    public function testGetColumnsLinkingReport()
    {
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
