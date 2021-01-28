<?php
namespace abrain\Einsatzverwaltung;

use PHPUnit\Framework\TestCase;

/**
 * Prüft bestimmte Dateien auf grundlegende Fehler
 * @package abrain\Einsatzverwaltung
 */
class FileCheck extends TestCase
{
    /**
     * Prüft, ob die in der README als Minimum angegebene PHP-Version mit den tatsächlichen Anforderungen übereinstimmt.
     */
    public function testPhpCompatibility()
    {
        $phpHeader = $this->getReadmeHeader('Requires PHP');
        $this->assertNotEmpty($phpHeader, 'Could not determine required PHP version');

        $buildDir = getenv('TRAVIS_BUILD_DIR');
        $prefix = $buildDir === false ? '.' : '$TRAVIS_BUILD_DIR';

        $lastLine = exec($prefix . '/vendor/bin/phpcompatinfo --no-ansi analyser:run src/');
        $this->assertEquals(1, preg_match('/Requires PHP (\d\.\d\.\d) \(min\)/', $lastLine, $matches));
        $actualRequirement = $matches[1];

        $this->assertTrue(
            version_compare($actualRequirement, $phpHeader, '<='),
            sprintf(
                'Der Header "Requires PHP" (%s) spiegelt nicht die tatsächliche Mindestanforderung (%s) wider.',
                $phpHeader,
                $actualRequirement
            )
        );
    }

    /**
     * Holt den Wert eines Headers aus der readme.txt
     *
     * @param $header
     * @return string
     */
    private function getReadmeHeader($header)
    {
        $file = fopen(__DIR__ . '/../src/readme.txt', 'r');
        $pattern = sprintf('/^%s:(.*)$/', $header);
        while (($line = fgets($file)) !== false) {
            if (preg_match($pattern, $line, $matches) === 1) {
                fclose($file);
                return trim($matches[1]);
            }
        }
        fclose($file);
        return '';
    }
}
