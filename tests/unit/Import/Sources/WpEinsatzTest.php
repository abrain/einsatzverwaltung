<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Exceptions\ImportException;
use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use const ARRAY_A;

/**
 * Class WpEinsatzTest
 * @covers \abrain\Einsatzverwaltung\Import\Sources\AbstractSource
 * @covers \abrain\Einsatzverwaltung\Import\Sources\WpEinsatz
 * @package abrain\Einsatzverwaltung\Import\Sources
 */
class WpEinsatzTest extends UnitTestCase
{
    public function testHasAnIdentifier()
    {
        $source = new WpEinsatz();
        $identifier = $source->getIdentifier();
        $this->assertIsString($identifier);
        $this->assertNotEmpty($identifier);
    }

    public function testHasAName()
    {
        $source = new WpEinsatz();
        $name = $source->getName();
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    public function testHasADescription()
    {
        $source = new WpEinsatz();
        $description = $source->getDescription();
        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    public function testCheckShouldFailWhenTableDoesNotExist()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;
        $wpdb->expects()->get_var(Mockery::type('string'))->andReturn('');

        $this->expectException(ImportCheckException::class);
        $source = new WpEinsatz();
        $source->checkPreconditions();
    }

    public function testCheckShouldFailWhenFieldsContainSpecialCharacters()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Pretend that the table exists and return some bad column names
        $wpdb->expects()->get_var(Mockery::type('string'))->andReturn('wpunit_einsaetze');
        $wpdb->expects()->get_col("DESCRIBE 'wpunit_einsaetze'", 0)->andReturn(['Datum', 'Örtlichkeit', 'Einsatz#']);

        $this->expectException(ImportCheckException::class);
        $source = new WpEinsatz();
        $source->checkPreconditions();
    }

    public function testCheckShouldPassIfConditionsAreMet()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Pretend that the table exists and return some good column names
        $wpdb->expects()->get_var("SHOW TABLES LIKE 'wpunit_einsaetze'")->andReturn('wpunit_einsaetze');
        $wpdb->expects()->get_col("DESCRIBE 'wpunit_einsaetze'", 0)->andReturn(['Datum', 'Ort', 'Art', 'Einsatztext']);

        $source = new WpEinsatz();
        try {
            $source->checkPreconditions();
        } catch (ImportCheckException $e) {
            $this->fail("Check for preconditions failed when it shouldn't");
        }
    }

    public function testCanGetFieldNames()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Return some column names
        $wpdb->expects()->get_col("DESCRIBE 'wpunit_einsaetze'", 0)->andReturn(['ID', 'Nr_Jahr', 'Nr_Monat', 'Datum', 'Ort']);

        $source = new WpEinsatz();
        $this->assertEqualSets(['Datum', 'Ort'], $source->getFields());
    }

    public function testFieldsGetCached()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Return some column names
        $wpdb->expects()->get_col("DESCRIBE 'wpunit_einsaetze'", 0)->andReturn(['ID', 'Nr_Jahr', 'Nr_Monat', 'Datum', 'Ort']);

        $source = new WpEinsatz();
        $this->assertEqualSets(['Datum', 'Ort'], $source->getFields());

        // Just ask a second time for the fields, the database should not be hit again
        $this->assertEqualSets(['Datum', 'Ort'], $source->getFields());
    }

    public function testGetsEntriesForAllFields()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Return some entries
        $entries = [
            ['ID' => 1, 'colA' => 'value1', 'colB' => 'value2', 'colC' => 'value3'],
            ['ID' => 2, 'colA' => 'value4', 'colB' => 'value5', 'colC' => 'value6'],
            ['ID' => 3, 'colA' => 'value7', 'colB' => 'value8', 'colC' => 'value9']
        ];
        $wpdb->expects()->get_results("SELECT * FROM 'wpunit_einsaetze' ORDER BY Datum", ARRAY_A)->andReturn($entries);

        $source = new WpEinsatz();
        try {
            $this->assertEqualSets($entries, $source->getEntries());
        } catch (ImportException $e) {
            $this->fail();
        }
    }

    public function testGetsEntriesForCertainFields()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Return some entries
        $entries = [
            ['ID' => 1, 'colA' => 'value1', 'colC' => 'value2'],
            ['ID' => 2, 'colA' => 'value3', 'colC' => 'value4'],
            ['ID' => 3, 'colA' => 'value5', 'colC' => 'value6']
        ];
        $wpdb->expects()
            ->get_results("SELECT ID,colA,colC FROM 'wpunit_einsaetze' ORDER BY Datum", ARRAY_A)
            ->andReturn($entries);

        $source = new WpEinsatz();
        try {
            $this->assertEqualSets($entries, $source->getEntries(['colA', 'colC']));
        } catch (ImportException $e) {
            $this->fail();
        }
    }

    public function testThrowsWhenEntriesCannotBeQueried()
    {
        /** @var Mockery\Mock $wpdb */
        global $wpdb;

        // Return some null for failure
        $wpdb->expects()->get_results(Mockery::type('string'), ARRAY_A)->andReturn(null);

        $this->expectException(ImportException::class);
        $source = new WpEinsatz();
        $source->getEntries();
    }

    public function testReturnsCorrectDateFormat()
    {
        $source = new WpEinsatz();
        $dateFormat = $source->getDateFormat();
        $this->assertEquals('Y-m-d', $dateFormat);
    }

    public function testReturnsCorrectTimeFormat()
    {
        $source = new WpEinsatz();
        $timeFormat = $source->getTimeFormat();
        $this->assertEquals('H:i:s', $timeFormat);
    }
}
