<?php

namespace abrain\Einsatzverwaltung;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\AdminPage
 */
class AdminPageTest extends UnitTestCase
{
    /**
     * @throws ExpectationArgsRequired
     */
    public function testAddsHooks()
    {
        $page = $this->getMockForAbstractClass(AdminPage::class, ['Title', 'slug']);
        expect('add_action')->once()->with('admin_menu', [$page, 'registerAsToolPage']);
        $page->addHooks();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testRegistersPage()
    {
        $page = $this->getMockForAbstractClass(AdminPage::class, ['Title of the page', 'menu-slug']);
        expect('add_management_page')->once()->with('Title of the page', 'Title of the page', 'manage_options', 'menu-slug', array($page, 'render'));
        $page->registerAsToolPage();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testRegistersPageWithCustomCapability()
    {
        $page = $this->getMockForAbstractClass(AdminPage::class, ['Title of the page', 'menu-slug', 'needed-capability']);
        expect('add_management_page')->once()->with('Title of the page', 'Title of the page', 'needed-capability', 'menu-slug', array($page, 'render'));
        $page->registerAsToolPage();
    }

    public function testRenderPage()
    {
        $page = $this->getMockForAbstractClass(AdminPage::class, ['Page Title', 'test-page']);
        $page->expects($this->any())
            ->method('echoPageContent')
            ->will($this->returnCallback(function () {
                echo '<p>Some content</p>';
            }));
        $this->expectOutputString('<div class="wrap"><h1>Page Title</h1><p>Some content</p></div>');
        $page->render();
    }
}
