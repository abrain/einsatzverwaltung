<?php
namespace abrain\Einsatzverwaltung\Jobs;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function array_key_exists;
use function Brain\Monkey\Functions\expect;
use function in_array;

/**
 * @package abrain\Einsatzverwaltung\Jobs
 * @covers \abrain\Einsatzverwaltung\Jobs\MigrateUnitsJob
 * @uses \abrain\Einsatzverwaltung\Types\Report
 * @uses \abrain\Einsatzverwaltung\Types\Unit
 */
class MigrateUnitsJobTest extends UnitTestCase
{
    /**
     * @throws ExpectationArgsRequired
     */
    public function testFullMigration()
    {
        global $wpdb;

        $unit1 = Mockery::mock('\WP_Term');
        $unit1->term_id = 162378;
        $unit2 = Mockery::mock('\WP_Term');
        $unit2->term_id = 239842;

        expect('get_terms')->once()->with(Mockery::on(function ($args) {
            return array_key_exists('taxonomy', $args) && $args['taxonomy'] === 'evw_unit' &&
                array_key_exists('hide_empty', $args) && $args['hide_empty'] === false;
        }))->andReturn([$unit1, $unit2]);

        expect('get_term_meta')->once()->with(162378, 'old_unit_id', true)->andReturn(1786235);
        expect('get_term_meta')->once()->with(239842, 'old_unit_id', true)->andReturn(7894651);

        expect('get_posts')->once()->with(Mockery::on(function ($args) {
            return array_key_exists('fields', $args) && $args['fields'] === 'ids' &&
                array_key_exists('post_type', $args) && $args['post_type'] === 'einsatz' &&
                array_key_exists('post_status', $args) && $args['post_status'] === ['publish', 'private'] &&
                array_key_exists('meta_query', $args) && $args['meta_query'] === [['key' => '_evw_unit', 'compare' => 'IN', 'value' => [1786235, 7894651]]];
        }))->andReturn([56354, 78498]);

        expect('wp_schedule_single_event')->once()->with(Mockery::type('int'), 'einsatzverwaltung_migrate_units');

        // Check assignment of new Units to existing Reports
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->postmeta = 'testpostmeta';

        expect('get_post_meta')->once()->with(56354, '_evw_unit')->andReturn(['1786235', '7894651']);
        expect('wp_set_post_terms')->once()->with(56354, Mockery::on(function ($arg) {
            return in_array(162378, $arg, true) && in_array(239842, $arg, true) && count($arg) === 2;
        }), 'evw_unit');
        $wpdb->expects('prepare')->once()->with('UPDATE testpostmeta SET meta_key = %s WHERE meta_key = %s AND post_id = %d', '_evw_legacy_unit', '_evw_unit', 56354)->andReturn('query1');
        $wpdb->expects('query')->once()->with('query1');

        expect('get_post_meta')->once()->with(78498, '_evw_unit')->andReturn(['7894651']);
        expect('wp_set_post_terms')->once()->with(78498, Mockery::on(function ($arg) {
            return $arg[0] === 239842 && count($arg) === 1;
        }), 'evw_unit');
        $wpdb->expects('prepare')->once()->with('UPDATE testpostmeta SET meta_key = %s WHERE meta_key = %s AND post_id = %d', '_evw_legacy_unit', '_evw_unit', 78498)->andReturn('query2');
        $wpdb->expects('query')->once()->with('query2');

        (new MigrateUnitsJob())->run();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testNoUnits()
    {
        // Pretend that there are no Units
        expect('get_terms')->once()->with(Mockery::type('array'))->andReturn([]);

        // Check that the job will not continue and not be rescheduled
        expect('get_term_meta')->never();
        expect('wp_schedule_single_event')->never();

        (new MigrateUnitsJob())->run();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUnitsWithOldId()
    {
        // Pretend the only Units in the system were not migrated from the CPT Units
        $unit1 = Mockery::mock('\WP_Term');
        $unit1->term_id = 512;
        $unit2 = Mockery::mock('\WP_Term');
        $unit2->term_id = 956;
        expect('get_terms')->once()->with(Mockery::type('array'))->andReturn([$unit1, $unit2]);
        expect('get_term_meta')->twice()->andReturn('');

        // Check that the job will not continue and not be rescheduled
        expect('get_posts')->never();
        expect('wp_schedule_single_event')->never();

        (new MigrateUnitsJob())->run();
    }

    public function testNoReportsWithOldUnits()
    {
        // Mock Units that have been migrated
        $unit1 = Mockery::mock('\WP_Term');
        $unit1->term_id = 156;
        $unit2 = Mockery::mock('\WP_Term');
        $unit2->term_id = 1351;
        expect('get_terms')->once()->with(Mockery::type('array'))->andReturn([$unit1, $unit2]);
        expect('get_term_meta')->once()->with(156, 'old_unit_id', true)->andReturn(8946);
        expect('get_term_meta')->once()->with(1351, 'old_unit_id', true)->andReturn(64564);

        // Pretend there are no Reports that were associated with the old Units
        expect('get_posts')->once()->with(Mockery::type('array'))->andReturn([]);

        // Check that the job will not continue and not be rescheduled
        expect('wp_schedule_single_event')->never();

        (new MigrateUnitsJob())->run();
    }
}
