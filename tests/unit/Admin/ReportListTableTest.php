<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\UnitTestCase;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Admin\ReportListTable
 */
class ReportListTableTest extends UnitTestCase
{
    public function testAddsHooks()
    {
        $reportListTable = new ReportListTable();
        expect('add_action')->once()->with('manage_einsatz_posts_custom_column', [$reportListTable, 'filterColumnContentEinsatz'], 10, 2);
        expect('add_action')->once()->with('quick_edit_custom_box', [$reportListTable, 'quickEditCustomBox'], 10, 3);
        expect('add_action')->once()->with('bulk_edit_custom_box', [$reportListTable, 'bulkEditCustomBox'], 10, 2);
        expect('add_action')->once()->with('add_inline_data', [$reportListTable, 'addInlineData'], 10, 2);
        expect('add_filter')->once()->with('manage_edit-einsatz_columns', [$reportListTable, 'filterColumnsEinsatz']);

        $this->assertIsCallable([$reportListTable, 'filterColumnContentEinsatz']);
        $this->assertIsCallable([$reportListTable, 'quickEditCustomBox']);
        $this->assertIsCallable([$reportListTable, 'bulkEditCustomBox']);
        $this->assertIsCallable([$reportListTable, 'addInlineData']);
        $this->assertIsCallable([$reportListTable, 'filterColumnsEinsatz']);

        $reportListTable->addHooks();
    }
}
