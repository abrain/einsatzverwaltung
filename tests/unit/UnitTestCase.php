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
        Monkey\Functions\stubTranslationFunctions();
        Monkey\Functions\stubEscapeFunctions();

        Monkey\Functions\when('shortcode_atts')->alias(function ($defaults, $attributes) {
            $attributes = (array) $attributes;
            $result = [];
            foreach ($defaults as $name => $default) {
                if (array_key_exists($name, $attributes)) {
                    $result[$name] = $attributes[$name];
                } else {
                    $result[$name] = $default;
                }
            }
            return $result;
        });
    }

    protected function tearDown()
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Copied from WP_UnitTestCase
     *
     * @param array $expected
     * @param array $actual
     */
    protected function assertEqualSets(array $expected, array $actual)
    {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }
}
