<?php
namespace abrain\Einsatzverwaltung\Frontend\ReportList;

use abrain\Einsatzverwaltung\UnitTestCase;

/**
 * Class ColumnRepositoryTest
 * @covers \abrain\Einsatzverwaltung\Frontend\ReportList\ColumnRepository
 * @package abrain\Einsatzverwaltung\Frontend\ReportList
 * @uses \abrain\Einsatzverwaltung\Frontend\ReportList\Column
 */
class ColumnRepositoryTest extends UnitTestCase
{
    public function testSanitizeColumnsArray()
    {
        $defaultColumns = explode(',', ColumnRepository::DEFAULT_COLUMNS);
        $this->assertEquals($defaultColumns, ColumnRepository::sanitizeColumnsArray(array()));
        $this->assertEquals($defaultColumns, ColumnRepository::sanitizeColumnsArray(array('bla', 'inexistent')));
        $this->assertEquals(array('title', 'vehicles'), ColumnRepository::sanitizeColumnsArray(array('title', 'invalid', 'vehicles')));
        $this->assertEquals(array('title'), ColumnRepository::sanitizeColumnsArray(array('title', 9)));
        $this->assertEquals($defaultColumns, ColumnRepository::sanitizeColumnsArray(array(12)));
    }

    public function testSanitizeColumnsArrayNoDefault()
    {
        $this->assertEquals(array(), ColumnRepository::sanitizeColumnsArrayNoDefault(array()));
        $this->assertEquals(array(), ColumnRepository::sanitizeColumnsArrayNoDefault(array('bla', 'inexistent')));
        $this->assertEquals(array('title', 'vehicles'), ColumnRepository::sanitizeColumnsArrayNoDefault(array('title', 'invalid', 'vehicles')));
        $this->assertEquals(array('title'), ColumnRepository::sanitizeColumnsArrayNoDefault(array('title', 9)));
        $this->assertEquals(array(), ColumnRepository::sanitizeColumnsArrayNoDefault(array(12)));
    }

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
}
