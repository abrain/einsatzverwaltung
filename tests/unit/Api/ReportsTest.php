<?php
namespace abrain\Einsatzverwaltung\Api;

use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;
use function array_key_exists;
use function Brain\Monkey\Functions\expect;
use function is_array;
use function is_callable;

/**
 * @covers \abrain\Einsatzverwaltung\Api\Reports
 */
class ReportsTest extends UnitTestCase
{
    protected function setUp()
    {
        parent::setUp();
        Mockery::mock('WP_REST_Controller');
        Mockery::namedMock('WP_REST_Server', 'abrain\Einsatzverwaltung\Stubs\WP_REST_Server_Stub');
    }

    public function testRegisterRoutes()
    {
        expect('register_rest_route')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::on(function ($arg) {
                if (!is_array($arg)) {
                    return false;
                }

                foreach ($arg as $routeOptions) {
                    if (!is_array($routeOptions)) {
                        return false;
                    }

                    foreach ($routeOptions['args'] as $routeOptionsArgs) {
                        // Check for essential properties
                        if (!array_key_exists('description', $routeOptionsArgs) ||
                            !array_key_exists('type', $routeOptionsArgs) ||
                            !array_key_exists('validate_callback', $routeOptionsArgs) ||
                            !is_callable($routeOptionsArgs['validate_callback']) ||
                            !array_key_exists('required', $routeOptionsArgs)
                        ) {
                            return false;
                        }

                        // If there is a sanitize_callback, it has to be a callable
                        if (array_key_exists('sanitize_callback', $routeOptionsArgs) &&
                            !is_callable($routeOptionsArgs['sanitize_callback'])
                        ) {
                            return false;
                        }
                    }
                }

                return true;
            }));
        (new Reports())->register_routes();
    }

    public function testCreatePermissionCheckPass()
    {
        $request = Mockery::mock('WP_REST_Request');
        expect('current_user_can')->once()->with(Mockery::type('string'))->andReturn(true);
        $this->assertTrue((new Reports())->create_item_permissions_check($request));
    }

    public function testCreatePermissionCheckFail()
    {
        $request = Mockery::mock('WP_REST_Request');
        expect('current_user_can')->once()->with(Mockery::type('string'))->andReturn(false);
        $this->assertFalse((new Reports())->create_item_permissions_check($request));
    }
}
