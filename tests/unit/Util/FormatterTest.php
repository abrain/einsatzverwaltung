<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\UnitTestCase;

/**
 * Class FormatterTest
 * @covers \abrain\Einsatzverwaltung\Util\Formatter
 * @package abrain\Einsatzverwaltung\Util
 */
class FormatterTest extends UnitTestCase
{
    public function testGetDurationString()
    {
        $this->assertEquals('0 minutes', Formatter::getDurationString(0));
        $this->assertEquals('1 minute', Formatter::getDurationString(1));
        $this->assertEquals('59 minutes', Formatter::getDurationString(59));
        $this->assertEquals('1 hour', Formatter::getDurationString(60));
        $this->assertEquals('1 hour 1 minute', Formatter::getDurationString(61));
        $this->assertEquals('2 hours 2 minutes', Formatter::getDurationString(122));

        $this->assertEquals('0 min', Formatter::getDurationString(0, true));
        $this->assertEquals('1 min', Formatter::getDurationString(1, true));
        $this->assertEquals('59 min', Formatter::getDurationString(59, true));
        $this->assertEquals('1 h', Formatter::getDurationString(60, true));
        $this->assertEquals('1 h 1 min', Formatter::getDurationString(61, true));
        $this->assertEquals('2 h 2 min', Formatter::getDurationString(122, true));

        $this->assertEquals('', Formatter::getDurationString(-1));
        $this->assertEquals('9 minutes', Formatter::getDurationString('9'));
    }
}
