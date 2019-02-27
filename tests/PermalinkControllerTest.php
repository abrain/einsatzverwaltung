<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Class PermalinkControllerTest
 * @package abrain\Einsatzverwaltung
 */
class PermalinkControllerTest extends WP_UnitTestCase
{
    public function testBuildSelector()
    {
        $reportFactory = new ReportFactory();
        $report = $reportFactory->create_and_get();

        $controller = new PermalinkController();
        $this->assertEquals($report->post_name, $controller->buildSelector($report, '%postname%'));
        $this->assertEquals($report->ID, $controller->buildSelector($report, '%post_id%'));
        $this->assertEquals($report->ID . '-bla', $controller->buildSelector($report, '%post_id%-bla'));
        $this->assertEquals($report->post_name . '-bla', $controller->buildSelector($report, '%postname%-bla'));
        $this->assertEquals("$report->ID/bla/$report->post_name", $controller->buildSelector($report, '%post_id%/bla/%postname%'));
        $this->assertEquals("$report->post_name/bla/$report->ID", $controller->buildSelector($report, '%postname%/bla/%post_id%'));
        $this->assertEquals("prefix/$report->post_name/suffix", $controller->buildSelector($report, 'prefix/%postname%/suffix'));
    }

    /**
     * @group unittests
     * It's not completely a unit test, but that way this test is skipped for the old PHP/PHPUnit version which doesn't
     * support createMock().
     */
    public function testGetPermalink()
    {
        $controller = new PermalinkController();
        $report = $this->createMock('abrain\Einsatzverwaltung\Types\Report');
        $report->rewriteSlug = 'customrewriteslug';
        $controller->addRewriteRules($report);

        if (getenv('WP_TESTS_PERMALINK') === 'PRETTY') {
            $this->assertEquals(
                'http://example.org/customrewriteslug/some-unique-selector/',
                $controller->getPermalink('some-unique-selector')
            );
        } elseif (getenv('WP_TESTS_PERMALINK') === 'PATHINFO') {
            $this->assertEquals(
                'http://example.org/index.php/customrewriteslug/some-unique-selector/',
                $controller->getPermalink('some-unique-selector')
            );
        }
    }

    /**
     * @group unittests
     */
    public function testGetRewriteBase()
    {
        $controller = new PermalinkController();
        $report = $this->createMock('abrain\Einsatzverwaltung\Types\Report');
        $report->rewriteSlug = 'customrewriteslug';
        $controller->addRewriteRules($report);

        if (getenv('WP_TESTS_PERMALINK') === 'PRETTY') {
            $this->assertEquals('customrewriteslug', $controller->getRewriteBase());
        } elseif (getenv('WP_TESTS_PERMALINK') === 'PATHINFO') {
            $this->assertEquals('index.php/customrewriteslug', $controller->getRewriteBase());
        } else {
            $this->assertEquals('', $controller->getRewriteBase());
        }
    }

    /**
     * @dataProvider queryVarTests
     * @param string $input
     * @param string $permalinkStructure
     * @param string $expectedIdentifier
     */
    public function testModifyQueryVars($input, $permalinkStructure, $expectedIdentifier)
    {
        $queryVars = array(
            'page' => '',
            'einsatz' => $input,
            'post_type' => 'einsatz',
            'name' => $input
        );
        $controller = new PermalinkController();
        $result = $controller->modifyQueryVars($queryVars, $permalinkStructure);
        if (strpos($permalinkStructure, '%post_id%') !== false) {
            $this->assertArrayHasKey('post_type', $result);
            $this->assertEquals('einsatz', $result['post_type']);
            $this->assertArrayNotHasKey('einsatz', $result);
            $this->assertArrayNotHasKey('name', $result);
            $this->assertArrayHasKey('p', $result);
            $this->assertEquals($expectedIdentifier, $result['p']);
        } elseif (strpos($permalinkStructure, '%postname%') !== false) {
            if ($permalinkStructure === '%postname%') {
                $this->assertEquals($queryVars, $result);
                return;
            }

            $this->assertArrayHasKey('post_type', $result);
            $this->assertEquals('einsatz', $result['post_type']);
            $this->assertArrayNotHasKey('einsatz', $result);
            $this->assertArrayNotHasKey('p', $result);
            $this->assertArrayHasKey('name', $result);
            $this->assertEquals($expectedIdentifier, $result['name']);
        } else {
            $this->fail('Permalink structure contained no unique identifier');
        }
    }

    /**
     * @return array
     */
    public function queryVarTests()
    {
        return array(
            array('somerandomstring', '%postname%', 'not-used'),
            array('123', '%post_id%', '123'),
            array('456-sanitized-title-without-number', '%post_id%-%postname_nosuffix%', '456'),
            array('sanitized-title-without-number-456', '%postname_nosuffix%-%post_id%', '456'),
            array('938457-suffix', '%post_id%-suffix', '938457'),
            array('prefix-938457', 'prefix-%post_id%', '938457'),
            array('prefix-938457-suffix', 'prefix-%post_id%-suffix', '938457'),
            array('24987/SEO-title', '%post_id%/SEO-title', '24987'),
            array('23749/bla/doesntmatter', '%post_id%/bla/%postname%', '23749'),
            array('doesntmatter/bla/12367', '%postname%/bla/%post_id%', '12367'),
            array('prefix/hello-again', 'prefix/%postname%', 'hello-again'),
            array('prefix/the-name/suffix', 'prefix/%postname%/suffix', 'the-name')
        );
    }

    /**
     * Tests queryvar array which must not or cannot be handled
     */
    public function testModifyQueryVarsBail()
    {
        $controller = new PermalinkController();

        $queryVarsNoEinsatz = array(
            'page' => '',
            'post_type' => 'einsatz',
            'name' => 'some-name'
        );
        $this->assertEquals($queryVarsNoEinsatz, $controller->modifyQueryVars($queryVarsNoEinsatz, 'doesntmatter'));

        $queryVarsWrongType = array(
            'page' => '',
            'einsatz' => 'some-name',
            'post_type' => 'differenttype',
            'name' => 'some-name'
        );
        $this->assertEquals($queryVarsWrongType, $controller->modifyQueryVars($queryVarsWrongType, 'doesntmatter'));

        $queryVarsWrongFormat = array(
            'page' => '',
            'einsatz' => 'some-name',
            'post_type' => 'einsatz',
            'name' => 'some-name'
        );
        $this->assertEquals($queryVarsWrongFormat, $controller->modifyQueryVars($queryVarsWrongFormat, 'wontmatch'));
    }

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
