<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Types\Report;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Class PermalinkControllerTest
 * @package abrain\Einsatzverwaltung
 */
class PermalinkControllerTest extends UnitTestCase
{
    /**
     * @covers \abrain\Einsatzverwaltung\PermalinkController::getPermalink
     * @covers \abrain\Einsatzverwaltung\PermalinkController::getRewriteBase
     * @uses \abrain\Einsatzverwaltung\PermalinkController::addRewriteRules
     * @throws ExpectationArgsRequired
     */
    public function testGetPrettyPermalink()
    {
        global $wp_rewrite;
        $wp_rewrite = Mockery::mock('\WP_Rewrite');
        $wp_rewrite->expects('using_permalinks')->once()->andReturnTrue();
        $wp_rewrite->front = '/';

        $controller = new PermalinkController();
        $report = $this->createMock(Report::class);
        $report->method('getRewriteSlug')->willReturn('customrewriteslug');
        expect('get_option')->once()->with('einsatz_permalink', Mockery::type('string'))->andReturn('');
        when('add_rewrite_rule')->justReturn();
        when('add_rewrite_tag')->justReturn();
        $controller->addRewriteRules($report);

        expect('home_url')->once()->with('customrewriteslug/some-unique-selector/')->andReturn('url184');
        $this->assertEquals('url184', $controller->getPermalink('some-unique-selector'));
    }

    /**
     * @covers \abrain\Einsatzverwaltung\PermalinkController::getPermalink
     * @covers \abrain\Einsatzverwaltung\PermalinkController::getRewriteBase
     * @uses \abrain\Einsatzverwaltung\PermalinkController::addRewriteRules
     * @throws ExpectationArgsRequired
     */
    public function testGetPathinfoPermalink()
    {
        global $wp_rewrite;
        $wp_rewrite = Mockery::mock('\WP_Rewrite');
        $wp_rewrite->expects('using_permalinks')->once()->andReturnTrue();
        $wp_rewrite->front = '/index.php/';

        $controller = new PermalinkController();
        $report = $this->createMock(Report::class);
        $report->method('getRewriteSlug')->willReturn('another-rewriteslug');
        expect('get_option')->once()->with('einsatz_permalink', Mockery::type('string'))->andReturn('');
        when('add_rewrite_rule')->justReturn();
        when('add_rewrite_tag')->justReturn();
        $controller->addRewriteRules($report);

        expect('home_url')->once()->with('index.php/another-rewriteslug/random-selector/')->andReturn('url4613');
        $this->assertEquals('url4613', $controller->getPermalink('random-selector'));
    }

    /**
     * @covers \abrain\Einsatzverwaltung\PermalinkController::sanitizePermalink
     */
    public function testSanitizePermalink()
    {
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink('%postname%'));
        $this->assertEquals('%post_id%-%postname_nosuffix%', PermalinkController::sanitizePermalink('%post_id%-%postname_nosuffix%'));
        $this->assertEquals('%postname_nosuffix%-%post_id%', PermalinkController::sanitizePermalink('%postname_nosuffix%-%post_id%'));
        $this->assertEquals('%post_id%-%postname%', PermalinkController::sanitizePermalink('%post_id%-%postname%'));

        // invalid permalinks should return the default permalink
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink('%post_id%_%postname_nosuffix%'));
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink('%post_id%/%postname_nosuffix%'));
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink('%post_id%--%postname_nosuffix%'));
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink(''));
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink('something'));

        // permalinks that do not contain a unique identifier should return the default permalink
        $this->assertEquals('%postname%', PermalinkController::sanitizePermalink('%postname_nosuffix%'));
    }
}
