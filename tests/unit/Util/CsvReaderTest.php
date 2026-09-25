<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Exceptions\FileReadException;
use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\tearDown;

/**
 * Class CsvReaderTest
 * @covers \abrain\Einsatzverwaltung\Util\CsvReader
 * @package abrain\Einsatzverwaltung\Util
 */
class CsvReaderTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $wp_filesystem;
        $wp_filesystem = Mockery::mock('\WP_Filesystem');
    }

    protected function tearDown(): void
    {
        // Explicitly reset the global, Mockery does not do that. Also, it's a singleton, which would not be recreated.
        global $wp_filesystem;
        $wp_filesystem = null;

        Mockery::close();
        tearDown(); // Reset Brain\Monkey
        parent::tearDown();
    }

    /**
     * @param bool $exists
     * @param bool $readable
     * @param string|false $overrideContent
     * @return void
     */
    private function setUpMock(bool $exists = true, bool $readable = true, $overrideContent = ''): void
    {
        global $wp_filesystem;
        $wp_filesystem->expects('exists')->once()->andReturn($exists);
        if (!$exists) {
            // Stop setting up expectations
            return;
        }

        $wp_filesystem->expects('is_readable')->once()->andReturn($readable);
        if (!$readable) {
            // Stop setting up expectations
            return;
        }

        if ($overrideContent === '') {
            $wp_filesystem->expects('get_contents')->once()->andReturnUsing('file_get_contents');
        } else {
            $wp_filesystem->expects('get_contents')->once()->andReturn($overrideContent);
        }
    }

    public function testThrowsWhenFileDoesNotExist()
    {
        $this->setUpMock(false);
        $this->expectException(FileReadException::class);
        $csvReader = new CsvReader(__DIR__ . '/no-such-file.csv', ';', '"');

        $csvReader->getLines(1);
    }

    public function testThrowsWhenFileIsNotReadable()
    {
        $this->setUpMock(true, false);
        $this->expectException(FileReadException::class);
        $csvReader = new CsvReader(__DIR__ . '/unreadable-file.csv', ';', '"');

        $csvReader->getLines(1);
    }

    public function testThrowsWhenFileReadFails()
    {
        $this->setUpMock(true, true, false);
        $this->expectException(FileReadException::class);
        $csvReader = new CsvReader(__DIR__ . '/erroneous-file.csv', ';', '"');

        $csvReader->getLines(1);
    }

    /**
     * @throws FileReadException
     */
    public function testCanReadCertainNumberOfLines()
    {
        $this->setUpMock();
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
        $this->setUpMock();
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(0);
        $this->assertCount(11, $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testCanSkipLines()
    {
        $this->setUpMock();
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
        $this->setUpMock();
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
        $this->setUpMock();
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(2, [1,3], 8);
        $this->assertEquals([
            ['Ru18STzsnj', ''],
            ['9f0NPAB0HU', '']
        ], $lines);
    }

    public function testThrowsWhenReadingTooFewLines()
    {
        $this->setUpMock();
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
        $this->setUpMock();
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
        $this->setUpMock();
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
    public function testDoesNotIgnoreLineWithEmptyValues()
    {
        $this->setUpMock();
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1, [], 10);
        $this->assertEquals([
            ['', '', '', ''],
        ], $lines);
    }

    /**
     * @throws FileReadException
     */
    public function testCanReturnIndexedArray()
    {
        $this->setUpMock();
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1);
        $this->assertEquals([0, 1, 2, 3], array_keys($lines[0]));
    }

    /**
     * @throws FileReadException
     */
    public function testCanReturnAssociativeArray()
    {
        $this->setUpMock();
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1, [], 0, ['first_name', 'last_name', 'address', 'email']);
        $this->assertEquals(['first_name', 'last_name', 'address', 'email'], array_keys($lines[0]));
    }

    /**
     * @throws FileReadException
     */
    public function testCanReturnAssociativeArrayForSelectedColumns()
    {
        $this->setUpMock();
        $csvReader = new CsvReader(__DIR__ . '/strings.csv', ';', '"');
        $lines = $csvReader->getLines(1, [1,3], 0, ['first_name', 'last_name', 'address', 'email']);
        $this->assertEquals(['last_name', 'email'], array_keys($lines[0]));
    }
}
