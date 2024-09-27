<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey;
use DateTime;
use DateTimeZone;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use function Brain\Monkey\Functions\when;
use function gmdate;

/**
 * Base class for unit testing, takes care of mocking many WordPress functions
 * @package abrain\Einsatzverwaltung
 */
class UnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Monkey\Functions\stubTranslationFunctions();
        Monkey\Functions\stubEscapeFunctions();

        // Plurals are not yet covered by Brain Monkey
        Monkey\Functions\when('_n')->alias(function ($single, $plural, $number) {
            return $number === 1 ? $single : $plural;
        });

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

        // Fake the global database object
        global $wpdb;
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->prefix = 'wpunit_';

        when('get_date_from_gmt')->alias(function ($string, $format = 'Y-m-d H:i:s') {
            try {
                $datetime = new DateTime($string, new DateTimeZone('UTC'));
            } catch (Exception $exception) {
                return gmdate($format, 0);
            }

            return $datetime->setTimezone(new DateTimeZone('Europe/Berlin'))->format($format);
        });

        when('sanitize_textarea_field')->returnArg();
        when('sanitize_text_field')->returnArg();
    }

    protected function tearDown(): void
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
