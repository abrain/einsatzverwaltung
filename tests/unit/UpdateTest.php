<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use function array_key_exists;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * @package abrain\Einsatzverwaltung
 * @covers \abrain\Einsatzverwaltung\Update
 */
class UpdateTest extends UnitTestCase
{
    /**
     * Check that existing Units are migrated from the custom post type to the custom taxonomy
     * @throws ExpectationArgsRequired
     */
    public function testUpgrade180MigrateUnits()
    {
        global $wpdb;
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->posts = 'testposts';
        $wpdb->expects()->prepare('UPDATE testposts SET post_type = %s WHERE post_type = %s', 'evw_legacy_unit', 'evw_unit')->andReturn('prepared_query');
        $wpdb->expects()->query('prepared_query');

        // Mock CPT Units
        $unit1 = Mockery::mock('\WP_Post');
        $unit1->ID = 1685;
        $unit1->post_title = 'Unit 1';
        $unit2 = Mockery::mock('\WP_Post');
        $unit2->ID = 874;
        $unit2->post_title = 'Unit 2';

        // Return CPT mocks
        expect('get_posts')->once()->with(Mockery::on(function ($args) {
            return array_key_exists('nopaging', $args) && $args['nopaging'] === true &&
                array_key_exists('post_type', $args) && $args['post_type'] === 'evw_legacy_unit' &&
                array_key_exists('post_status', $args) && $args['post_status'] === ['publish', 'private'];
        }))->andReturn([$unit1, $unit2]);

        // Fake post meta
        expect('get_post_meta')->once()->with(1685, 'unit_exturl', true)->andReturn('https://example.org');
        expect('get_post_meta')->once()->with(1685, 'unit_pid', true)->andReturn('198');
        expect('get_post_meta')->once()->with(874, 'unit_exturl', true)->andReturn('');
        expect('get_post_meta')->once()->with(874, 'unit_pid', true)->andReturn('321');

        // Check creation of new Units as taxonomy terms
        expect('wp_insert_term')->once()->with('Unit 1', 'evw_unit')->andReturn(['term_id' => 5236211]);
        expect('add_term_meta')->once()->with(5236211, 'old_unit_id', 1685, true);
        expect('add_term_meta')->once()->with(5236211, 'unit_exturl', 'https://example.org', true);
        expect('add_term_meta')->once()->with(5236211, 'unit_pid', '198', true);
        expect('wp_insert_term')->once()->with('Unit 2', 'evw_unit')->andReturn(['term_id' => 1563174]);
        expect('add_term_meta')->once()->with(1563174, 'old_unit_id', 874, true);
        expect('add_term_meta')->once()->with(1563174, 'unit_exturl', '', true);
        expect('add_term_meta')->once()->with(1563174, 'unit_pid', '321', true);

        expect('wp_schedule_single_event')->once()->with(Mockery::type('int'), 'einsatzverwaltung_migrate_units');

        expect('get_option')->twice()->andReturn([]);

        expect('update_option')->once()->with('einsatzvw_db_version', 60);

        (new Update())->upgrade180();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUpgrade180UpdatesWidgetConfig()
    {
        global $wpdb;
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->posts = 'testposts';
        $wpdb->expects('prepare')->once();
        $wpdb->expects('query')->once();

        when('get_post_meta')->justReturn('');
        when('add_term_meta')->justReturn();
        when('wp_schedule_single_event')->justReturn();

        // Mock CPT Units
        $unit1 = Mockery::mock('\WP_Post');
        $unit1->ID = 96345;
        $unit1->post_title = 'Unit 1';
        $unit2 = Mockery::mock('\WP_Post');
        $unit2->ID = 62312;
        $unit2->post_title = 'Unit 2';
        expect('get_posts')->once()->andReturn([$unit1, $unit2]);

        expect('wp_insert_term')->once()->with('Unit 1', 'evw_unit')->andReturn(['term_id' => 8977641]);
        expect('wp_insert_term')->once()->with('Unit 2', 'evw_unit')->andReturn(['term_id' => 1598613]);

        // If no widgets exist, nothing should be updated
        expect('get_option')->once()->with('widget_recent-incidents-formatted')->andReturn([]);
        expect('update_option')->never()->with('widget_recent-incidents-formatted', Mockery::any());

        // The unit IDs in the existing widgets should be rewritten to the new IDs
        $before = [
            ['title' => 'Some Title', 'anzahl' => 4, 'units' => [96345]],
            ['title' => '', 'anzahl' => 2, 'units' => []],
            ['title' => 'Another Title', 'anzahl' => 3, 'units' => [96345, 62312]]
        ];
        $before['_multiwidget'] = 1;
        expect('get_option')->once()->with('widget_einsatzverwaltung_widget')->andReturn($before);
        expect('update_option')->once()->with('widget_einsatzverwaltung_widget', Mockery::on(function ($arg) {
            $expected = [
                ['title' => 'Some Title', 'anzahl' => 4, 'units' => [8977641]],
                ['title' => '', 'anzahl' => 2, 'units' => []],
                ['title' => 'Another Title', 'anzahl' => 3, 'units' => [8977641, 1598613]]
            ];
            $expected['_multiwidget'] = 1;

            $factory = new Factory();
            $comparator = $factory->getComparatorFor($expected, $arg);
            try {
                $comparator->assertEquals($expected, $arg);
            } catch (ComparisonFailure $failure) {
                self::fail($failure->getDiff());
                return false;
            }
            return true;
        }))->andReturn($before);

        (new Update())->upgrade180();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUpgrade180NothingToMigrate()
    {
        global $wpdb;
        $wpdb = Mockery::mock('\wpdb');
        $wpdb->posts = 'testposts';
        $wpdb->expects('prepare')->once();
        $wpdb->expects('query')->once();

        // Return no CPTs
        expect('get_posts')->once()->andReturn([]);

        expect('get_post_meta')->never();
        expect('wp_insert_term')->never();
        expect('add_term_meta')->never();

        expect('update_option')->once()->with('einsatzvw_db_version', 60);

        (new Update())->upgrade180();
    }
}
