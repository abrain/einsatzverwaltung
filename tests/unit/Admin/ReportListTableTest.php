<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

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

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     * @throws ExpectationArgsRequired
     */
    public function testAddsInlineDataToReports()
    {
        $reportListTable = new ReportListTable();
        $post = Mockery::mock('\WP_Post');
        $post->ID = 1397;
        $postType = Mockery::mock('\WP_Post_Type');
        $postType->name = 'einsatz';

        expect('get_post_meta')->once()->with(1397, 'einsatz_incidentNumber', true)->andReturn('2024/625');
        $this->expectOutputString('<div id="report_number_1397" class="meta_input">2024/625</div>');
        $reportListTable->addInlineData($post, $postType);
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     * @throws ExpectationArgsRequired
     */
    public function testAddsInlineDataToReportsIfEmpty()
    {
        $reportListTable = new ReportListTable();
        $post = Mockery::mock('\WP_Post');
        $post->ID = 243;
        $postType = Mockery::mock('\WP_Post_Type');
        $postType->name = 'einsatz';

        expect('get_post_meta')->once()->with(243, 'einsatz_incidentNumber', true)->andReturn('');
        $this->expectOutputString('<div id="report_number_243" class="meta_input"></div>');
        $reportListTable->addInlineData($post, $postType);
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     * @throws ExpectationArgsRequired
     */
    public function testAddsInlineDataToReportsOnMetaError()
    {
        $reportListTable = new ReportListTable();
        $post = Mockery::mock('\WP_Post');
        $post->ID = 62523;
        $postType = Mockery::mock('\WP_Post_Type');
        $postType->name = 'einsatz';

        expect('get_post_meta')->once()->with(62523, 'einsatz_incidentNumber', true)->andReturn(false);
        $this->expectOutputString('<div id="report_number_62523" class="meta_input"></div>');
        $reportListTable->addInlineData($post, $postType);
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Report::getSlug()
     */
    public function testDoesNotAddInlineDataToOtherPostTypes()
    {
        $reportListTable = new ReportListTable();
        $post = Mockery::mock('\WP_Post');
        $postType = Mockery::mock('\WP_Post_Type');
        $postType->name = 'post';

        $this->expectOutputString('');
        expect('get_post_meta')->never();
        $reportListTable->addInlineData($post, $postType);
    }
}
