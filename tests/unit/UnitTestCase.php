<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey;
use PHPUnit_Framework_TestCase;

/**
 * Base class for unit testing, takes care of mocking many WordPress functions
 * @package abrain\Einsatzverwaltung
 */
class UnitTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown()
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
