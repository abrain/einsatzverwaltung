<?php
namespace abrain\Einsatzverwaltung\Import\Sources;

use abrain\Einsatzverwaltung\UnitTestCase;

/**
 * Class CsvTest
 * @covers \abrain\Einsatzverwaltung\Import\Sources\AbstractSource
 * @covers \abrain\Einsatzverwaltung\Import\Sources\Csv
 * @package abrain\Einsatzverwaltung\Import\Sources
 * @uses \abrain\Einsatzverwaltung\Import\Step
 */
class CsvTest extends UnitTestCase
{
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
}
