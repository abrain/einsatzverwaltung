<?php

namespace abrain\Einsatzverwaltung\Exceptions;

use abrain\Einsatzverwaltung\UnitTestCase;
use Exception;

/**
 * @covers \abrain\Einsatzverwaltung\Exceptions\ImportException
 */
class ImportExceptionTest extends UnitTestCase
{
    public function testDefaultsToEmptyValues()
    {
        $importException = new ImportException();
        $this->assertEquals('', $importException->getMessage());
        $this->assertEquals([], $importException->getDetails());
        $this->assertEquals(0, $importException->getCode());
        $this->assertEquals(null, $importException->getPrevious());
    }

    public function testAcceptsCustomValues()
    {
        $previous = new Exception('the previous error');
        $details = ['this happened', 'and this also went wrong'];
        $importException = new ImportException('lorem ipsum', $details, 8493, $previous);
        $this->assertEquals('lorem ipsum', $importException->getMessage());
        $this->assertEquals($details, $importException->getDetails());
        $this->assertEquals(8493, $importException->getCode());
        $this->assertEquals($previous, $importException->getPrevious());
    }
}
