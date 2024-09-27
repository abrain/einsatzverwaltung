<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use abrain\Einsatzverwaltung\UnitTestCase;
use function Brain\Monkey\Functions\when;

/**
 * Class CsvReaderTest
 * @covers \abrain\Einsatzverwaltung\Import\CsvReader
 * @package abrain\Einsatzverwaltung\Import
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

    public function testCanReadCertainNumberOfLines()
    {
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');
        $lines = $csvReader->getLines(3);
        $this->assertEquals([
            ['First', 'Second', 'Third', 'Last column'],
            ['OJM19KwAeh', 'I3vSoJFB9M', 'Y161hkjINb', 'FMy5jUPI9Y'],
            ['ID2ftXEztI', 'FKKNOoOKiK m5RJBo4HjD', '135wtpYq0I', 'YmDXv1t4HB']
        ], $lines);
    }

    public function testCanReadEntireFile()
    {
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');
        $lines = $csvReader->getLines(0);
        $this->assertCount(11, $lines);
    }

    public function testCanSkipLines()
    {
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');
        $lines = $csvReader->getLines(2, [], 3);
        $this->assertEquals([
            ['4XJOXyaahT', 'II1G1rIC3R', 'N9xLHxULuu iTb24Cr0W2', 'ekwgQCyBBs'],
            ['o2T2kmvnEw', 'aKM7zt7H9M', 'fjHlHxUTU8', 'SvcKLU7Smc']
        ], $lines);
    }

    public function testReturnsOnlyRequestedColumns()
    {
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');
        $lines = $csvReader->getLines(3, [0,3]);
        $this->assertEquals([
            ['First', 'Last column'],
            ['OJM19KwAeh', 'FMy5jUPI9Y'],
            ['ID2ftXEztI', 'YmDXv1t4HB']
        ], $lines);
    }

    public function testFillsNotExistingColumns()
    {
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');
        $lines = $csvReader->getLines(2, [1,3], 8);
        $this->assertEquals([
            ['Ru18STzsnj', ''],
            ['9f0NPAB0HU', '']
        ], $lines);
    }

    public function testThrowsWhenReadingTooFewLines()
    {
        $this->expectException(FileReadException::class);
        $this->expectExceptionMessage('2 lines');
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');

        $counter = 0;
        when('fgetcsv')->alias(function () use (&$counter) {
            if ($counter++ > 1) {
                return false;
            }
            return ['lorem', 'ipsum'];
        });
        when('feof')->justReturn(false);

        // Suppress the warning, otherwise PHPUnit would convert it to an exception
        @$csvReader->getLines(3);
    }

    public function testThrowsWhenStoppingBeforeEndOfFile()
    {
        $this->expectException(FileReadException::class);
        $this->expectExceptionMessage('1 line');
        $csvReader = new CsvReader(__DIR__ . '/reports.csv', ';', '"');

        $counter = 0;
        when('fgetcsv')->alias(function () use (&$counter) {
            if ($counter++ > 0) {
                return false;
            }
            return ['lorem', 'ipsum'];
        });
        when('feof')->justReturn(false);

        // Suppress the warning, otherwise PHPUnit would convert it to an exception
        @$csvReader->getLines(0);
    }
}
