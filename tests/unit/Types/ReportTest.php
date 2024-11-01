<?php

namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * @covers \abrain\Einsatzverwaltung\Types\Report
 */
class ReportTest extends UnitTestCase
{
    public function testRegistrationArgumentsLookGood()
    {
        expect('get_option')->once()->with('einsatzvw_rewrite_slug', Mockery::type('string'))->andReturn('REWRITE-SLUG');
        when('sanitize_title')->returnArg();

        $registrationArgs = (new Report())->getRegistrationArgs();
        $this->assertIsArray($registrationArgs);
        $this->assertIsArray($registrationArgs['capabilities']);
        $this->assertIsArray($registrationArgs['labels']);
        $this->assertIsArray($registrationArgs['rewrite']);
        $this->assertIsArray($registrationArgs['supports']);
        $this->assertIsArray($registrationArgs['taxonomies']);
        $this->assertEquals('REWRITE-SLUG', $registrationArgs['rewrite']['slug']);
    }

    public function testFallbackRewriteSlug()
    {
        expect('get_option')->once()->with('einsatzvw_rewrite_slug', Mockery::type('string'))->andReturnUsing(function ($option, $default) {
            return $default;
        });
        when('sanitize_title')->returnArg();

        $registrationArgs = (new Report())->getRegistrationArgs();
        $this->assertEquals('einsatzberichte', $registrationArgs['rewrite']['slug']);
    }

    public function testDefaultCoreFeatureSupport()
    {
        expect('get_option')->once()->with('einsatzvw_rewrite_slug', Mockery::type('string'))->andReturn('REWRITE-SLUG');
        when('sanitize_title')->returnArg();

        $registrationArgs = (new Report())->getRegistrationArgs();
        $this->assertContainsEquals('title', $registrationArgs['supports']);
        $this->assertContainsEquals('author', $registrationArgs['supports']);
        $this->assertNotContainsEquals('comments', $registrationArgs['supports']);
        $this->assertNotContainsEquals('excerpt', $registrationArgs['supports']);
    }

    public function testOptionalCommentSupport()
    {
        expect('get_option')->once()->with('einsatzvw_rewrite_slug', Mockery::type('string'))->andReturn('REWRITE-SLUG');
        expect('get_option')->once()->with('einsatz_support_comments', Mockery::type('string'))->andReturn('1');
        when('sanitize_title')->returnArg();

        $registrationArgs = (new Report())->getRegistrationArgs();
        $this->assertContainsEquals('comments', $registrationArgs['supports']);
    }

    public function testOptionalExcerptSupport()
    {
        expect('get_option')->once()->with('einsatzvw_rewrite_slug', Mockery::type('string'))->andReturn('REWRITE-SLUG');
        expect('get_option')->once()->with('einsatz_support_excerpt', Mockery::type('string'))->andReturn('1');
        when('sanitize_title')->returnArg();

        $registrationArgs = (new Report())->getRegistrationArgs();
        $this->assertContainsEquals('excerpt', $registrationArgs['supports']);
    }
}
