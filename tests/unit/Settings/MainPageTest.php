<?php

namespace abrain\Einsatzverwaltung\Settings;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Settings\MainPage
 */
class MainPageTest extends UnitTestCase
{
    /**
     * @throws ExpectationArgsRequired
     */
    public function testAddsHooks()
    {
        $options = Mockery::mock('abrain\Einsatzverwaltung\Options');
        $permalinkController = Mockery::mock('abrain\Einsatzverwaltung\PermalinkController');
        $page = new MainPage($options, $permalinkController);

        expect('add_action')->once()->with('admin_menu', [$page, 'addToSettingsMenu']);
        expect('add_action')->once()->with('admin_init', [$page, 'registerSettings']);

        $this->assertIsCallable([$page, 'addToSettingsMenu']);
        $this->assertIsCallable([$page, 'registerSettings']);

        $page->addHooks();
    }
}
