<?php
namespace abrain\Einsatzverwaltung\Api;

use abrain\Einsatzverwaltung\DataAccess\ReportInserter;
use abrain\Einsatzverwaltung\Model\ReportInsertObject;
use DateTime;
use DateTimeImmutable;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function __;
use function array_key_exists;
use function array_map;
use function current_user_can;
use function explode;
use function is_bool;
use function is_string;
use function is_wp_error;
use function strlen;
use function trim;
use function wp_strip_all_tags;
use const DATE_RFC3339;

/**
 * API Controller for receiving incident data from third-party systems
 * @package abrain\Einsatzverwaltung\Api
 */
class Reports extends WP_REST_Controller
{
    /**
     * @inheritDoc
     */
    public function register_routes() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- overridden method
    {
        $namespace = 'einsatzverwaltung/v1';
        register_rest_route($namespace, '/reports', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args'                => array(
                    'reason' => array(
                        'description' => __('Very short description of the incident, will be used in the title', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => array($this, 'validateStringNotEmpty'),
                        'sanitize_callback' => function ($param, $request, $key) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
                            return wp_strip_all_tags($param);
                        },
                        'required' => true,
                    ),
                    'date_start' => array(
                        'description' => __('The start date and time of the incident, RFC 3339 format.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'format' => 'date-time',
                        'validate_callback' => array($this, 'validateDateTime'),
                        'required' => true,
                    ),
                    'date_end' => array(
                        'description' => __('The end date and time of the incident, RFC 3339 format.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'format' => 'date-time',
                        'validate_callback' => array($this, 'validateDateTime'),
                        'required' => false,
                    ),
                    'content' => array(
                        'description' => __('The content of the report. No HTML allowed, but line breaks are preserved.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => array($this, 'validateIsString'),
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'required' => false,
                    ),
                    'keyword' => array(
                        'description' => __('Identifier used to categorize incidents.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => array($this, 'validateIsString'),
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false
                    ),
                    'location' => array(
                        'description' => __('The location of the incident.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => array($this, 'validateIsString'),
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'publish' => array(
                        'description' => __('If the report should be published immediately.', 'einsatzverwaltung'),
                        'type' => 'boolean',
                        'default' => false,
                        'validate_callback' => function ($param, $request, $key) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
                            return is_bool($param);
                        },
                        'required' => false,
                    ),
                    'resources' => array(
                        'description' => __('The resources dispatched to this incident as a comma-separated list.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => array($this, 'validateIsString'),
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                ),
            ),
        ));
    }

    /**
     * @inheritDoc
     */
    public function create_item($request) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- overridden method
    {
        $params = $request->get_params();

        $start_date_time = DateTimeImmutable::createFromFormat(DATE_RFC3339, $params['date_start']);
        $importObject = new ReportInsertObject($start_date_time, $params['reason']);

        // Process optional parameter content
        if (array_key_exists('content', $params) && !empty($params['content'])) {
            $importObject->setContent($params['content']);
        }

        // Process optional parameter date_end
        if (array_key_exists('date_end', $params)) {
            $end_date_time = DateTimeImmutable::createFromFormat(DATE_RFC3339, $params['date_end']);
            $importObject->setEndDateTime($end_date_time);
        }

        // Process optional parameter keyword
        if (array_key_exists('keyword', $params) && !empty($params['keyword'])) {
            $importObject->setKeyword($params['keyword']);
        }

        // Process optional parameter location
        if (array_key_exists('location', $params) && !empty($params['location'])) {
            $importObject->setLocation($params['location']);
        }

        // Process optional parameter resources
        if (array_key_exists('resources', $params) && !empty($params['resources'])) {
            $resources = explode(',', $params['resources']);
            $importObject->setResources(array_map('trim', $resources));
        }

        // Add post to database
        $publishReport = array_key_exists('publish', $params) && $params['publish'] === true;
        $reportInserter = new ReportInserter($publishReport);
        $post = $reportInserter->insertReport($importObject);

        if (is_wp_error($post)) {
            return $post;
        }

        // Return the ID of the created post
        $response = new WP_REST_Response(array('id' => $post));
        $response->set_status(201);
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function create_item_permissions_check($request) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- overridden method
    {
        $params = $request->get_params();
        $publishReport = array_key_exists('publish', $params) && $params['publish'] === true;
        return current_user_can('edit_einsatzberichte') && (!$publishReport || current_user_can('publish_einsatzberichte'));
    }

    /**
     * Validates if the passed parameter value is a date conforming to RFC 3339.
     *
     * @param mixed $value
     * @param WP_REST_Request $request
     * @param string $key
     *
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateDateTime($value, WP_REST_Request $request, string $key): bool
    {
        $dateTime = DateTime::createFromFormat(DATE_RFC3339, $value);
        return $dateTime !== false;
    }

    /**
     * Validates if the passed parameter value is a string
     *
     * @param mixed $value
     * @param WP_REST_Request $request
     * @param string $key
     *
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateIsString($value, WP_REST_Request $request, string $key): bool
    {
        return is_string($value);
    }

    /**
     * Validates if the passed parameter value is a string and contains non-whitespace characters
     *
     * @param mixed $value
     * @param WP_REST_Request $request
     * @param string $key
     *
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateStringNotEmpty($value, WP_REST_Request $request, string $key): bool
    {
        return is_string($value) && strlen(trim($value)) > 0;
    }
}
