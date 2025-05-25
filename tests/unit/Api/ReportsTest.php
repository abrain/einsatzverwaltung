<?php
namespace abrain\Einsatzverwaltung\Api;

use abrain\Einsatzverwaltung\Model\ReportInsertObject;
use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use DateTimeImmutable;
use Mockery;
use WP_REST_Request; // Added for type hinting
use function array_key_exists;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Api\Reports
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ReportsTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mockery::mock('WP_REST_Controller');
        Mockery::namedMock('WP_REST_Server', 'abrain\Einsatzverwaltung\Stubs\WPRESTServerStub');
        // Mock WordPress functions that might be called directly or as callbacks
        expect('__')->atLeast()->zeroTimes()->andReturnUsing(function ($text, $domain) {
            return $text; // Simple passthrough for descriptions
        });
        expect('absint')->atLeast()->zeroTimes()->andReturnUsing(function ($value) {
            return abs((int)$value);
        });
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testRegisterRoutes()
    {
        expect('register_rest_route')->once()->with(
            Mockery::type('string'),
            Mockery::type('string'),
            Mockery::capture($routeArgs)
        );
        (new Reports())->register_routes();

        $this->assertIsArray($routeArgs);

        foreach ($routeArgs as $routeOptions) {
            $this->assertIsArray($routeOptions);

            foreach ($routeOptions['args'] as $argName => $routeOptionsArgs) {
                // Check for essential properties
                $this->assertArrayHasKey('description', $routeOptionsArgs, "Argument $argName has no description");
                $this->assertArrayHasKey('type', $routeOptionsArgs, "Argument $argName has no type");
                $this->assertArrayHasKey('validate_callback', $routeOptionsArgs, "Argument $argName has no validate_callback");
                $this->assertIsCallable($routeOptionsArgs['validate_callback'], "Validate callback for $argName is not callable");
                $this->assertArrayHasKey('required', $routeOptionsArgs, "Argument $argName has no required flag");

                // If there is a sanitize_callback, it has to be a callable
                if (array_key_exists('sanitize_callback', $routeOptionsArgs)) {
                    $this->assertIsCallable($routeOptionsArgs['sanitize_callback'], "Sanitize callback for $argName is not callable");
                }
            }
        }

        // Specifically check image_id argument in the CREATABLE route
        $creatableRouteArgs = null;
        foreach ($routeArgs as $routeOptions) {
            if (isset($routeOptions['methods']) && $routeOptions['methods'] === \WP_REST_Server::CREATABLE) {
                $creatableRouteArgs = $routeOptions['args'];
                break;
            }
        }
        $this->assertNotNull($creatableRouteArgs, "CREATABLE route options not found.");
        $this->assertArrayHasKey('image_id', $creatableRouteArgs, "image_id argument not found in CREATABLE route.");

        $imageIdArgs = $creatableRouteArgs['image_id'];
        $this->assertEquals(__('The ID of an image in the WordPress Media Library to be set as the featured image.', 'einsatzverwaltung'), $imageIdArgs['description']);
        $this->assertEquals('integer', $imageIdArgs['type']);
        $this->assertEquals([new Reports(), 'validateAttachmentId'], $imageIdArgs['validate_callback']);
        $this->assertEquals('absint', $imageIdArgs['sanitize_callback']);
        $this->assertFalse($imageIdArgs['required']);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUserCanEditReports()
    {
        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->once()->andReturn(['publish' => false]);
        expect('current_user_can')->once()->with('edit_einsatzberichte')->andReturn(true);
        $this->assertTrue((new Reports())->create_item_permissions_check($request));
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUserCannotEditReports()
    {
        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->once()->andReturn(['publish' => false]);
        expect('current_user_can')->once()->with('edit_einsatzberichte')->andReturn(false);
        $this->assertFalse((new Reports())->create_item_permissions_check($request));
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUserCannotPublishReports()
    {
        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->once()->andReturn(['publish' => true]);
        expect('current_user_can')->once()->with('edit_einsatzberichte')->andReturn(true);
        expect('current_user_can')->once()->with('publish_einsatzberichte')->andReturn(false);
        $this->assertFalse((new Reports())->create_item_permissions_check($request));
    }

    public function testValidateDateTime()
    {
        $request = Mockery::mock('WP_REST_Request');
        $reports = new Reports();
        $this->assertFalse($reports->validateDateTime('2021-08-29 21:35:27', $request, 'some_key'));
        $this->assertTrue($reports->validateDateTime('2021-08-29T21:35:27+0200', $request, 'some_key'));
    }

    public function testValidateStringNotEmpty()
    {
        $request = Mockery::mock('WP_REST_Request');
        $reports = new Reports();
        $this->assertFalse($reports->validateStringNotEmpty(0, $request, 'some_key'));
        $this->assertFalse($reports->validateStringNotEmpty(null, $request, 'some_key'));
        $this->assertFalse($reports->validateStringNotEmpty([], $request, 'some_key'));
        $this->assertFalse($reports->validateStringNotEmpty('', $request, 'some_key'));
        $this->assertFalse($reports->validateStringNotEmpty(' ', $request, 'some_key'));
        $this->assertTrue($reports->validateStringNotEmpty('0', $request, 'some_key'));
        $this->assertTrue($reports->validateStringNotEmpty('yo', $request, 'some_key'));
    }

    public function testValidateIsString()
    {
        $request = Mockery::mock('WP_REST_Request');
        $reports = new Reports();
        $this->assertFalse($reports->validateIsString(9, $request, 'some_key'));
        $this->assertFalse($reports->validateIsString(null, $request, 'some_key'));
        $this->assertFalse($reports->validateIsString([''], $request, 'some_key'));
        $this->assertTrue($reports->validateIsString('', $request, 'some_key'));
        $this->assertTrue($reports->validateIsString('9', $request, 'some_key'));
        $this->assertTrue($reports->validateIsString('yo', $request, 'some_key'));
    }

    /**
     * @dataProvider provideValidateAttachmentIdCases
     */
    public function testValidateAttachmentId($value, $urlReturnValue, $isImageReturnValue, $expectedResult)
    {
        $request = Mockery::mock(WP_REST_Request::class); // Mock WP_REST_Request
        $reports = new Reports();

        // Reset mocks for WordPress functions for each data set
        Mockery::getContainer()->mockery_close(); // Close any existing Mockery instances from previous tests/data providers
        parent::setUp(); // Re-run setup to re-initialize mocks if needed, or manage mocks more granularly

        if (is_int($value) && $value > 0) { // Only mock for positive integer IDs
            expect('wp_get_attachment_url')
                ->once()
                ->with($value)
                ->andReturn($urlReturnValue);

            if ($urlReturnValue !== false) {
                expect('wp_attachment_is_image')
                    ->once()
                    ->with($value)
                    ->andReturn($isImageReturnValue);
            }
        }

        $this->assertEquals($expectedResult, $reports->validateAttachmentId($value, $request, 'image_id'));
    }

    public function provideValidateAttachmentIdCases(): array
    {
        return [
            'valid image ID' => [123, 'http://example.com/image.jpg', true, true],
            'non-image attachment ID' => [124, 'http://example.com/document.pdf', false, false],
            'non-existent attachment ID' => [125, false, null, false], // wp_attachment_is_image not called
            'non-integer value' => ['abc', null, null, false], // WordPress functions not called
            'zero ID' => [0, null, null, false], // WordPress functions not called
            'negative ID' => [-1, null, null, false], // WordPress functions not called
        ];
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
        $importObject->expects('setImageId')->never(); // Ensure it's not called

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
    public function testCreateItemCompleteWithoutImageId()
    {
        $request = Mockery::mock(WP_REST_Request::class); // Mock WP_REST_Request
        $request->expects('get_params')->once()->andReturn([
            'reason' => 'A reason',
            'date_start' => '2021-08-29T21:47:59+0200',
            'date_end' => '2021-08-29T22:41:16+0200',
            'content' => 'This is the content',
            'keyword' => 'key-word',
            'location' => 'It happened here',
            'publish' => true,
            'resources' => 'resource 1, another resource,and number three'
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
        $importObject->expects('setKeyword')->once()->with('key-word');
        $importObject->expects('setLocation')->once()->with('It happened here');
        $importObject->expects('setResources')->once()->with(['resource 1', 'another resource', 'and number three']);
        $importObject->expects('setImageId')->never(); // Ensure it's not called

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
    public function testCreateItemWithValidImageId()
    {
        $validImageId = 123;
        // sanitize_callback 'absint' is mocked in setUp to return abs((int)$value)
        // So, if $validImageId is '123', absint will make it 123.
        // If it were '-123', absint would make it 123.

        $request = Mockery::mock(WP_REST_Request::class);
        $request->expects('get_params')->once()->andReturn([
            'reason' => 'A reason with image',
            'date_start' => '2021-08-29T21:47:59+0200',
            'image_id' => $validImageId, // Raw value before sanitization
            'publish' => false,
        ]);

        // These mocks are for the check within create_item, not for the validate_callback.
        // The validate_callback is tested separately by testValidateAttachmentId
        // and its correct registration is tested in testRegisterRoutes.
        // For this create_item test, we assume the image_id has passed validation
        // and is now being processed.
        // The `absint` sanitize_callback (mocked in setUp) will have run.
        $sanitizedImageId = abs((int)$validImageId);


        $importObject = Mockery::mock('overload:abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('__construct')->once()->with(
            Mockery::on(function ($arg) {
                return $arg instanceof DateTimeImmutable && $arg->getTimestamp() === 1630266479;
            }),
            'A reason with image'
        );
        // Expect setImageId to be called with the *sanitized* image_id
        $importObject->expects('setImageId')->once()->with($sanitizedImageId);


        $reportInserter = Mockery::mock('overload:abrain\Einsatzverwaltung\DataAccess\ReportInserter');
        $reportInserter->expects('__construct')->once()->with(false); // publish is false
        $reportInserter->expects('insertReport')->once()->with(Mockery::on(function ($arg) use ($sanitizedImageId) {
            // Check that the ReportInsertObject passed to ReportInserter has the correct imageId
            return $arg instanceof ReportInsertObject && $arg->getImageId() === $sanitizedImageId;
        }))->andReturn(789); // New post ID

        $response = Mockery::mock('overload:WP_REST_Response');
        $response->expects('__construct')->once()->with(['id' => 789]);
        $response->expects('set_status')->once()->with(201);

        $reportsApi = new Reports();
        $restResponse = $reportsApi->create_item($request);
        $this->assertInstanceOf('WP_REST_Response', $restResponse);
    }


    /**
     * @throws ExpectationArgsRequired
     */
    public function testCreateItemError()
    {
        $request = Mockery::mock(WP_REST_Request::class); // Mock WP_REST_Request
        $request->expects('get_params')->once()->andReturn([
            'reason' => 'A reason',
            'date_start' => '2021-08-29T21:47:59+0200'
        ]);

        $wpError = Mockery::mock('WP_Error');

        $importObject = Mockery::mock('overload:abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('__construct')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof DateTimeImmutable && $arg->getTimestamp() === 1630266479;
        }), 'A reason');
        $importObject->expects('setImageId')->never(); // Ensure it's not called

        $reportInserter = Mockery::mock('overload:abrain\Einsatzverwaltung\DataAccess\ReportInserter');
        $reportInserter->expects('__construct')->once()->with(false);
        $reportInserter->expects('insertReport')->once()->with(Mockery::on(function ($arg) {
            return $arg instanceof ReportInsertObject;
        }))->andReturn($wpError);

        // If insertReport returns WP_Error, a WP_REST_Response is not constructed by our code.
        // So, no need to mock WP_REST_Response here for the error path.

        $reportsApi = new Reports();
        $this->assertEquals($wpError, $reportsApi->create_item($request));
    }
}
