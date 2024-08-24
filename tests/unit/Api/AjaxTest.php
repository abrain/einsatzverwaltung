<?php
namespace abrain\Einsatzverwaltung\Api;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use stdClass;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Api\Ajax
 */
class AjaxTest extends UnitTestCase
{
    public function testAddHooks()
    {
        expect('add_action')->times(1)->with(Mockery::pattern('/^wp_ajax_einsatzverwaltung_/'), Mockery::type('callable'));
        (new Ajax())->addHooks();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUsedLocationsHandler()
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->postmeta = 'pm';

        $obj1 = new stdClass();
        $obj1->meta_value = 'some other location';
        $obj2 = new stdClass();
        $obj2->meta_value = 'a location';

        $wpdb->expects('prepare')->once()->with(Mockery::pattern('/^SELECT DISTINCT meta_value FROM pm WHERE /'), Mockery::type('array'))->andReturn('used_location_query');
        $wpdb->expects('get_results')->once()->with('used_location_query', OBJECT)->andReturn([$obj1, $obj2]);

        expect('check_ajax_referer');
        expect('current_user_can')->once()->with('edit_einsatzberichte')->andReturn(true);
        expect('wp_send_json_success')->once()->with(['a location', 'some other location']);

        (new Ajax())->usedLocationsHandler();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUsedLocationsHandlerRequiresEditCapability()
    {
        expect('check_ajax_referer');
        expect('current_user_can')->once()->with('edit_einsatzberichte')->andReturn(false);
        expect('wp_send_json_error')->once()->with(null, 403);

        (new Ajax())->usedLocationsHandler();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUsedLocationsHandlerCatchesDatabaseError()
    {
        global $wpdb;
        $wpdb = Mockery::mock('wpdb');
        $wpdb->postmeta = 'pm';

        $wpdb->expects('prepare')->once()->andReturn('');
        $wpdb->expects('get_results')->once()->andReturnNull(); // simulate an error

        expect('check_ajax_referer');
        expect('current_user_can')->once()->with('edit_einsatzberichte')->andReturn(true);
        expect('wp_send_json_error')->once()->with(null, 500);

        (new Ajax())->usedLocationsHandler();
    }
}
