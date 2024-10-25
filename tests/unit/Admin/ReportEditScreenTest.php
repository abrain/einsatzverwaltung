<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\UnitTestCase;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Admin\ReportEditScreen
 */
class ReportEditScreenTest extends UnitTestCase
{
    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     */
    public function testAddsHooks()
    {
        $reportEditScreen = new ReportEditScreen();

        expect('add_action')->once()->with('add_meta_boxes_einsatz', [$reportEditScreen, 'addMetaBoxes']);
        expect('add_filter')->once()->with('default_hidden_meta_boxes', [$reportEditScreen, 'filterDefaultHiddenMetaboxes'], 10, 2);
        expect('add_filter')->once()->with('wp_dropdown_cats', [$reportEditScreen, 'filterIncidentCategoryDropdown'], 10, 2);

        $this->assertIsCallable([$reportEditScreen, 'addMetaBoxes']);
        $this->assertIsCallable([$reportEditScreen, 'filterDefaultHiddenMetaboxes']);
        $this->assertIsCallable([$reportEditScreen, 'filterIncidentCategoryDropdown']);

        $reportEditScreen->addHooks();
    }
}
