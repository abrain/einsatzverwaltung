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
        $this->assertEquals(ReportListParameters::DEFAULT_COLUMNS, ReportListParameters::sanitizeColumns(''));
        $this->assertEquals(ReportListParameters::DEFAULT_COLUMNS, ReportListParameters::sanitizeColumns(','));
        $this->assertEquals(ReportListParameters::DEFAULT_COLUMNS, ReportListParameters::sanitizeColumns('bla'));
        $this->assertEquals(ReportListParameters::DEFAULT_COLUMNS, ReportListParameters::sanitizeColumns('bla,invalid'));
        $this->assertEquals('title', ReportListParameters::sanitizeColumns('title'));
        $this->assertEquals('title', ReportListParameters::sanitizeColumns('title,invalid'));
        $this->assertEquals('title,date', ReportListParameters::sanitizeColumns('title,invalid,date'));
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
        $this->assertEquals(explode(',', ReportListParameters::DEFAULT_COLUMNS), $parameters->getColumns());
        $this->assertEquals(array('title'), $parameters->getColumnsLinkingReport());
    }

    public function testConstructInvalidColumns()
    {
        update_option('einsatzvw_list_columns', 'invalid,bla');
        $parameters = new ReportListParameters();
        $this->assertEquals(explode(',', ReportListParameters::DEFAULT_COLUMNS), $parameters->getColumns());
    }

    public function testConstructMixedColumns()
    {
        update_option('einsatzvw_list_columns', 'invalid,title,bla,date');
        $parameters = new ReportListParameters();
        $this->assertEquals(array('title', 'date'), $parameters->getColumns());
    }

    public function testIsSplitMonths()
    {
        $parameters = new ReportListParameters();
        $this->assertFalse($parameters->isSplitMonths());
        $parameters->setSplitMonths(true);
        $this->assertTrue($parameters->isSplitMonths());
        $parameters->compact = true;
        $this->assertFalse($parameters->isSplitMonths());
    }

    public function testSanitizeColumnsArrayNoDefault()
    {
        $this->assertEquals(array(), ReportListParameters::sanitizeColumnsArrayNoDefault(array()));
        $this->assertEquals(array(), ReportListParameters::sanitizeColumnsArrayNoDefault(array('bla', 'inexistent')));
        $this->assertEquals(array('title', 'vehicles'), ReportListParameters::sanitizeColumnsArrayNoDefault(array('title', 'invalid', 'vehicles')));
        $this->assertEquals(array('title'), ReportListParameters::sanitizeColumnsArrayNoDefault(array('title', 9)));
        $this->assertEquals(array(), ReportListParameters::sanitizeColumnsArrayNoDefault(array(12)));
    }

    public function testSanitizeColumnsArray()
    {
        $defaultColumns = explode(',', ReportListParameters::DEFAULT_COLUMNS);
        $this->assertEquals($defaultColumns, ReportListParameters::sanitizeColumnsArray(array()));
        $this->assertEquals($defaultColumns, ReportListParameters::sanitizeColumnsArray(array('bla', 'inexistent')));
        $this->assertEquals(array('title', 'vehicles'), ReportListParameters::sanitizeColumnsArray(array('title', 'invalid', 'vehicles')));
        $this->assertEquals(array('title'), ReportListParameters::sanitizeColumnsArray(array('title', 9)));
        $this->assertEquals($defaultColumns, ReportListParameters::sanitizeColumnsArray(array(12)));
    }

    public function testSetColumns()
    {
        $defaultColumns = explode(',', ReportListParameters::DEFAULT_COLUMNS);
        $parameters = new ReportListParameters();
        $parameters->setColumns(array());
        $this->assertEquals($defaultColumns, $parameters->getColumns());
        $parameters->setColumns(array(''));
        $this->assertEquals($defaultColumns, $parameters->getColumns());
        $parameters->setColumns(array('invalid'));
        $this->assertEquals($defaultColumns, $parameters->getColumns());
        $parameters->setColumns(array('invalid', 'title'));
        $this->assertEquals(array('title'), $parameters->getColumns());
        $parameters->setColumns(array('title'));
        $this->assertEquals(array('title'), $parameters->getColumns());
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
