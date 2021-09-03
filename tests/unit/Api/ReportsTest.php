<?php
namespace abrain\Einsatzverwaltung\Api;

use abrain\Einsatzverwaltung\Model\ReportInsertObject;
use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use DateTimeImmutable;
use Mockery;
use function array_key_exists;
use function Brain\Monkey\Functions\expect;
use function is_array;
use function is_callable;

/**
 * @covers \abrain\Einsatzverwaltung\Api\Reports
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
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

    /**
     * @throws ExpectationArgsRequired
     */
    public function testCreatePermissionCheckPass()
    {
        $request = Mockery::mock('WP_REST_Request');
        expect('current_user_can')->once()->with(Mockery::type('string'))->andReturn(true);
        $this->assertTrue((new Reports())->create_item_permissions_check($request));
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testCreatePermissionCheckFail()
    {
        $request = Mockery::mock('WP_REST_Request');
        expect('current_user_can')->once()->with(Mockery::type('string'))->andReturn(false);
        $this->assertFalse((new Reports())->create_item_permissions_check($request));
    }

    public function testValidateDateTime()
    {
        $request = Mockery::mock('WP_REST_Request');
        $reports = new Reports();
        $this->assertFalse($reports->validate_date_time('2021-08-29 21:35:27', $request, 'some_key'));
        $this->assertTrue($reports->validate_date_time('2021-08-29T21:35:27+0200', $request, 'some_key'));
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testCreateItemMinimalData()
    {
        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->once()->andReturn([
            'reason' => 'A reason',
            'date_start' => '2021-08-29T21:47:59+0200'
        ]);

        // Create an overload mock, as the object gets created inside the tested function
        $importObject = Mockery::mock('overload:abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('__construct')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof DateTimeImmutable && $arg->getTimestamp() === 1630266479;
        }), 'A reason');

        $reportInserter = Mockery::mock('overload:abrain\Einsatzverwaltung\DataAccess\ReportInserter');
        $reportInserter->expects('__construct')->once()->with(false);
        $reportInserter->expects('insertReport')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof ReportInsertObject;
        }))->andReturn(614);

        // Create an overload mock, as the object gets created inside the tested function
        $response = Mockery::mock('overload:WP_REST_Response');
        $response->expects('__construct')->once()->with(['id' => 614]);
        $response->expects('set_status')->once()->with(201);

        $reportsApi = new Reports();
        $restResponse = $reportsApi->create_item($request);
        $this->assertInstanceOf('WP_REST_Response', $restResponse);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testCreateItemComplete()
    {
        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->once()->andReturn([
            'reason' => 'A reason',
            'date_start' => '2021-08-29T21:47:59+0200',
            'date_end' => '2021-08-29T22:41:16+0200',
            'content' => 'This is the content',
            'location' => 'It happened here',
            'publish' => true
        ]);

        // Create an overload mock, as the object gets created inside the tested function
        $importObject = Mockery::mock('overload:abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('__construct')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof DateTimeImmutable && $arg->getTimestamp() === 1630266479;
        }), 'A reason');
        $importObject->expects('setContent')->once()->with('This is the content');
        $importObject->expects('setEndDateTime')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof DateTimeImmutable && $arg->getTimestamp() === 1630269676;
        }));
        $importObject->expects('setLocation')->once()->with('It happened here');

        $reportInserter = Mockery::mock('overload:abrain\Einsatzverwaltung\DataAccess\ReportInserter');
        $reportInserter->expects('__construct')->once()->with(true);
        $reportInserter->expects('insertReport')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof ReportInsertObject;
        }))->andReturn(532);

        // Create an overload mock, as the object gets created inside the tested function
        $response = Mockery::mock('overload:WP_REST_Response');
        $response->expects('__construct')->once()->with(['id' => 532]);
        $response->expects('set_status')->once()->with(201);

        $reportsApi = new Reports();
        $reportsApi->create_item($request);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testCreateItemError()
    {
        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->once()->andReturn([
            'reason' => 'A reason',
            'date_start' => '2021-08-29T21:47:59+0200'
        ]);

        $wpError = Mockery::mock('WP_Error');

        // Create an overload mock, as the object gets created inside the tested function
        $importObject = Mockery::mock('overload:abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('__construct')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof DateTimeImmutable && $arg->getTimestamp() === 1630266479;
        }), 'A reason');

        $reportInserter = Mockery::mock('overload:abrain\Einsatzverwaltung\DataAccess\ReportInserter');
        $reportInserter->expects('__construct')->once()->with(false);
        $reportInserter->expects('insertReport')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof ReportInsertObject;
        }))->andReturn($wpError);


        // Create an overload mock, as the object gets created inside the tested function
        $response = Mockery::mock('overload:WP_REST_Response');
        $response->expects('__construct')->once()->with(['id' => 614]);
        $response->expects('set_status')->once()->with(201);

        $reportsApi = new Reports();
        $this->assertEquals($wpError, $reportsApi->create_item($request));
    }
}
