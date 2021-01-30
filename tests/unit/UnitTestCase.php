<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Base class for unit testing, takes care of mocking many WordPress functions
 * @package abrain\Einsatzverwaltung
 */
class UnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        parent::setUp();
        Monkey\setUp();
        Monkey\Functions\when('__')->returnArg(1);
        Monkey\Functions\when('_e')->echoArg(1);
        Monkey\Functions\when('_n')->alias(function ($single, $plural, $number) {
            return $number === 1 ? $single : $plural;
        });
        Monkey\Functions\when('esc_url')->returnArg(1);
    }

    protected function tearDown()
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Copied from WP_UnitTestCase
     * @param array $expected
     * @param array $actual
     */
    protected function assertEqualSets($expected, $actual)
    {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }
}
