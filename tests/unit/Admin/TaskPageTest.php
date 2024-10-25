<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Admin\TasksPage
 */
class TaskPageTest extends UnitTestCase
{
    public function testAddsHooks()
    {
        $data = Mockery::mock('abrain\Einsatzverwaltung\Data');
        $utilities = Mockery::mock('abrain\Einsatzverwaltung\Utilities');
        $page = new TasksPage($utilities, $data);

        expect('add_action')->once()->with('admin_menu', [$page, 'registerPage']);
        expect('add_action')->once()->with('admin_menu', [$page, 'hidePage'], 999);

        $this->assertIsCallable([$page, 'registerPage']);
        $this->assertIsCallable([$page, 'hidePage']);

        $page->addHooks();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testRegistersPage()
    {
        $data = Mockery::mock('abrain\Einsatzverwaltung\Data');
        $utilities = Mockery::mock('abrain\Einsatzverwaltung\Utilities');
        $page = new TasksPage($utilities, $data);

        expect('add_management_page')->once()->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('string'), Mockery::type('string'), [$page, 'renderPage']);
        $this->assertIsCallable([$page, 'renderPage']);

        $page->registerPage();
    }

    public function testHidesPageFromSubmenu()
    {
        $data = Mockery::mock('abrain\Einsatzverwaltung\Data');
        $utilities = Mockery::mock('abrain\Einsatzverwaltung\Utilities');
        $page = new TasksPage($utilities, $data);

        expect('remove_submenu_page')->once();

        $page->hidePage();
    }
}
