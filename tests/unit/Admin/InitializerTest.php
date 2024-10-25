<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * @covers \abrain\Einsatzverwaltung\Admin\Initializer
 */
class InitializerTest extends UnitTestCase
{
    private $initializer;

    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wptest_';

        $data = Mockery::mock('abrain\Einsatzverwaltung\Data');
        $options = Mockery::mock('abrain\Einsatzverwaltung\Options');
        $utilities = Mockery::mock('abrain\Einsatzverwaltung\Utilities');
        $permalinkController = Mockery::mock('abrain\Einsatzverwaltung\PermalinkController');
        $this->initializer = new Initializer($data, $options, $utilities, $permalinkController);
    }

    public function testAddsHooks()
    {
        expect('add_action')->atLeast()->once();
        expect('add_filter')->atLeast()->once();
        $this->initializer->addHooks();
    }

    public function testEnqueueDefaultScripts()
    {
        expect('wp_enqueue_style')->atLeast()->once()->withAnyArgs();
        expect('wp_enqueue_script')->atLeast()->once()->withAnyArgs();

        $this->initializer->enqueueEditScripts('something-something');
    }

    public function testEnqueuePostEditScripts()
    {
        when('admin_url')->alias(function ($path) {
            return sprintf('https://example.com/wp-admin/%s/', $path);
        });
        when('wp_create_nonce')->justReturn('a-great-nonce');

        expect('wp_enqueue_script')->once()->with('einsatzverwaltung-admin-script', Mockery::type('string'), Mockery::type('array'), Mockery::type('string'));
        expect('wp_enqueue_style')->atLeast()->once()->withAnyArgs();
        expect('wp_localize_script')->atLeast()->once()->withAnyArgs();
        expect('wp_set_script_translations')->atLeast()->once()->withAnyArgs();

        $this->initializer->enqueueEditScripts('post.php');
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     * @throws ExpectationArgsRequired
     */
    public function testEnqueueReportListTableScripts()
    {
        expect('wp_enqueue_script')->once()->with('einsatzverwaltung-report-list-table', Mockery::type('string'), false, null, true);
        expect('wp_enqueue_style')->atLeast()->once()->withAnyArgs();

        $screen = Mockery::mock('\WP_Screen');
        $screen->post_type = 'einsatz';
        when('get_current_screen')->justReturn($screen);

        $this->initializer->enqueueEditScripts('edit.php');
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     * @throws ExpectationArgsRequired
     */
    public function testDoNotEnqueueReportListTableScriptsForOtherPostTypes()
    {
        expect('wp_enqueue_script')->never()->with('einsatzverwaltung-report-list-table', Mockery::type('string'), false, null, true);
        expect('wp_enqueue_style')->atLeast()->once()->withAnyArgs();

        $screen = Mockery::mock('\WP_Screen');
        $screen->post_type = 'post';
        when('get_current_screen')->justReturn($screen);

        $this->initializer->enqueueEditScripts('edit.php');
    }

    public function testEnqueueSettingsScripts()
    {
        expect('wp_enqueue_script')->once()->with('einsatzverwaltung-settings-script', Mockery::type('string'), Mockery::type('array'), Mockery::type('string'));
        expect('wp_enqueue_style')->atLeast()->once()->withAnyArgs();

        $this->initializer->enqueueEditScripts('settings_page_einsatzvw-settings');
    }
}
