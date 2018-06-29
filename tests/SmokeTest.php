<?php
namespace abrain\Einsatzverwaltung\Tests;

/**
 * Enthält Tests, die die größten Showstopper abklopfen
 * @package abrain\Einsatzverwaltung\Tests
 */
class SmokeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prüft, ob die in der README als Minimum angegebene PHP-Version mit den tatsächlichen Anforderungen übereinstimmt.
     */
    public function testPhpCompatibility()
    {
        $file = dirname(einsatzverwaltung_plugin_file()) . '/readme.txt';
        $fileData = get_file_data($file, array('PHPmin' => 'Requires PHP'));
        $phpHeader = $fileData['PHPmin'];

        $buildDir = getenv('TRAVIS_BUILD_DIR');
        $prefix = $buildDir === false ? '.' : '$TRAVIS_BUILD_DIR';

        $lastLine = exec($prefix . '/vendor/bin/phpcompatinfo --no-ansi analyser:run src/');
        $this->assertEquals(1, preg_match('/Requires PHP (\d\.\d\.\d) \(min\)/', $lastLine, $matches));
        $actualRequirement = $matches[1];

        $this->assertTrue(
            version_compare($actualRequirement, $phpHeader, '='),
            "Der Header 'Requires PHP' ($phpHeader) spiegelt nicht die tatsächliche Mindestanforderung ($actualRequirement) wider."
        );
    }
}
