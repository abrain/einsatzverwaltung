<?php

namespace abrain\Einsatzverwaltung\Export;

use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Export\Page
 * @uses \abrain\Einsatzverwaltung\AdminPage
 */
class PageTest extends UnitTestCase
{
    public function testAddsHooks()
    {
        $page = new Page();

        expect('add_action')->once()->with('admin_menu', [$page, 'registerAsToolPage']);
        expect('add_action')->once()->with('init', [$page, 'startExport'], Mockery::on(function ($priority) {
            return is_integer($priority) && $priority > 10;
        }));
        expect('add_action')->once()->with('admin_enqueue_scripts', [$page, 'enqueueAdminScripts']);

        $this->assertIsCallable([$page, 'registerAsToolPage']);
        $this->assertIsCallable([$page, 'startExport']);
        $this->assertIsCallable([$page, 'enqueueAdminScripts']);

        $page->addHooks();
    }
}
