<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use abrain\Einsatzverwaltung\UnitTestCase;
use function Brain\Monkey\Functions\when;

/**
 * Class CsvReaderTest
 * @covers \abrain\Einsatzverwaltung\Util\CsvReader
 * @package abrain\Einsatzverwaltung\Util
 */
class CsvReaderTest extends UnitTestCase
{
    public function testThrowsWhenFileIsNotReadable()
    {
        $this->expectException(FileReadException::class);
        $csvReader = new CsvReader(__DIR__ . '/no-such-file.csv', ';', '"');

        // Suppress the warning, otherwise PHPUnit would convert it to an exception
        @$csvReader->getLines(1);
    }

    /**
     * @throws FileReadException
     */
    public function testCanReadCertainNumberOfLines()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(3);
        $this->assertEquals([
            ['First', 'Second', 'Third', 'Last column'],
            ['OJM19KwAeh', 'I3vSoJFB9M', 'Y161hkjINb', 'FMy5jUPI9Y'],
            ['ID2ftXEztI', 'FKKNOoOKiK m5RJBo4HjD', '135wtpYq0I', 'YmDXv1t4HB']
        ], $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testCanReadEntireFile()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(0);
        $this->assertCount(11, $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testCanSkipLines()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(2, [], 3);
        $this->assertEquals([
            ['4XJOXyaahT', 'II1G1rIC3R', 'N9xLHxULuu iTb24Cr0W2', 'ekwgQCyBBs'],
            ['o2T2kmvnEw', 'aKM7zt7H9M', 'fjHlHxUTU8', 'SvcKLU7Smc']
        ], $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testReturnsOnlyRequestedColumns()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(3, [0,3]);
        $this->assertEquals([
            ['First', 'Last column'],
            ['OJM19KwAeh', 'FMy5jUPI9Y'],
            ['ID2ftXEztI', 'YmDXv1t4HB']
        ], $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testFillsNotExistingColumns()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(2, [1,3], 8);
        $this->assertEquals([
            ['Ru18STzsnj', ''],
            ['9f0NPAB0HU', '']
        ], $lines);
    }

    public function testThrowsWhenReadingTooFewLines()
    {
        $this->expectException(FileReadException::class);
        $this->expectExceptionMessage('Reading was aborted after 2 lines');
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');

        $counter = 0;
        when('fgetcsv')->alias(function () use (&$counter) {
            if ($counter++ > 1) {
                return false;
            }
            return ['lorem', 'ipsum'];
        });
        when('feof')->justReturn(false);

        $csvReader->getLines(3);
    }

    public function testThrowsWhenStoppingBeforeEndOfFile()
    {
        $this->expectException(FileReadException::class);
        $this->expectExceptionMessage('Reading was aborted after 1 line');
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');

        $counter = 0;
        when('fgetcsv')->alias(function () use (&$counter) {
            if ($counter++ > 0) {
                return false;
            }
            return ['lorem', 'ipsum'];
        });
        when('feof')->justReturn(false);

        $csvReader->getLines(0);
    }

    /**
     * Reads 3 lines, which should only produce 2 lines, because one of them is empty.
     *
     * @throws FileReadException
     */
    public function testIgnoresEmptyLines()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(3, [], 5);
        $this->assertEquals([
            ['TweXDv0mph', 'SFwrH1TJsJ', '39OcbnuXdR', 'vXxMrsiVyE'],
            ['cdt48vLr4n', 'EWh4XUKUK8', 'mTBF74hEuX', 'dextkGI2U9']
        ], $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testCanReturnIndexedArray()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1);
        $this->assertEquals([0, 1, 2, 3], array_keys($lines[0]));
    }

    /**
     * @throws FileReadException
     */
    public function testCanReturnAssociativeArray()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1, [], 0, ['first_name', 'last_name', 'address', 'email']);
        $this->assertEquals(['first_name', 'last_name', 'address', 'email'], array_keys($lines[0]));
    }

    /**
     * @throws FileReadException
     */
    public function testCanReturnAssociativeArrayForSelectedColumns()
    {
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1, [1,3], 0, ['first_name', 'last_name', 'address', 'email']);
        $this->assertEquals(['last_name', 'email'], array_keys($lines[0]));
    }
}
