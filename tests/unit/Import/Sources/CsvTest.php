<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * Class CsvTest
 * @covers \abrain\Einsatzverwaltung\Import\Sources\AbstractSource
 * @covers \abrain\Einsatzverwaltung\Import\Sources\Csv
 * @package abrain\Einsatzverwaltung\Import\Sources
 */
class CsvTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        // Explicitly reset the global, Mockery does not do that. Also, it's a singleton, which would not be recreated.
        global $wp_filesystem;
        $wp_filesystem = null;

        Mockery::close();
        parent::tearDown();
    }

    public function testHasAnIdentifier()
    {
        $source = new Csv();
        $identifier = $source->getIdentifier();
        $this->assertIsString($identifier);
        $this->assertNotEmpty($identifier);
    }

    public function testHasAName()
    {
        $source = new Csv();
        $name = $source->getName();
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    public function testHasADescription()
    {
        $source = new Csv();
        $description = $source->getDescription();
        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Utilities::getArrayValueIfKey
     */
    public function testCheckShouldFailWhenUsingInvalidDelimiter()
    {
        $source = new Csv();
        $source->putArg('delimiter', 'a');

        $this->expectException(ImportCheckException::class);
        $source->checkPreconditions();
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Utilities::getArrayValueIfKey
     */
    public function testCheckShouldFailWhenAttachmentIdIsNotGiven()
    {
        $source = new Csv();
        $source->putArg('delimiter', ',');
        $source->putArg('csv_file_id', '');

        $this->expectException(ImportCheckException::class);
        $source->checkPreconditions();
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Utilities::getArrayValueIfKey
     */
    public function testCheckShouldFailWhenAttachmentIdIsNotNumeric()
    {
        $source = new Csv();
        $source->putArg('delimiter', ';');
        $source->putArg('csv_file_id', 'abc');

        $this->expectException(ImportCheckException::class);
        $source->checkPreconditions();
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Utilities::getArrayValueIfKey
     *
     * @throws ExpectationArgsRequired
     */
    public function testCheckShouldFailWhenAttachmentCannotBeFound()
    {
        $source = new Csv();
        $source->putArg('delimiter', ',');
        $source->putArg('csv_file_id', '28374');

        expect('get_attached_file')->once()->with('28374')->andReturn(false);
        $this->expectException(ImportCheckException::class);
        $source->checkPreconditions();
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Utilities::getArrayValueIfKey
     */
    public function testCheckShouldFailWhenFileDoesNotExist()
    {
        $source = new Csv();
        $source->putArg('delimiter', ';');
        $source->putArg('csv_file_id', '23421');

        expect('get_attached_file')->once()->with('23421')->andReturn('/path/to/file');
        expect('file_exists')->once()->with('/path/to/file')->andReturn(false);
        $this->expectException(ImportCheckException::class);
        $source->checkPreconditions();
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Util\CsvReader
     * @uses \abrain\Einsatzverwaltung\Utilities::getArrayValueIfKey
     */
    public function testCheckShouldFailWhenFileCannotBeRead()
    {
        global $wp_filesystem;
        $wp_filesystem = Mockery::mock('\WP_Filesystem');
        $wp_filesystem->expects('exists')->once()->with('/path/to/another/file')->andReturn(false);

        $source = new Csv();
        $source->putArg('delimiter', ';');
        $source->putArg('csv_file_id', '92834');

        expect('get_attached_file')->once()->with('92834')->andReturn('/path/to/another/file');
        expect('file_exists')->once()->with('/path/to/another/file')->andReturn(true);

        $this->expectException(ImportCheckException::class);
        $source->checkPreconditions();
    }
}
