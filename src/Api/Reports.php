<?php
namespace abrain\Einsatzverwaltung\Api;

use abrain\Einsatzverwaltung\Types\Report;
use DateTime;
use DateTimeZone;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function array_key_exists;
use function current_user_can;
use function esc_html__;
use function get_date_from_gmt;
use function is_bool;
use function is_string;
use function is_wp_error;
use function wp_insert_post;
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
    public function register_routes()
    {
        $namespace = 'einsatzverwaltung/v1';
        register_rest_route($namespace, '/reports', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args'                => array(
                    'reason' => array(
                        'description' => esc_html__('', 'einsatzverwaltung'), // TODO
                        'type' => 'string',
                        'validate_callback' => function ($param, $request, $key) {
                            return !empty($param);
                        },
                        'sanitize_callback' => function ($param, $request, $key) {
                            return wp_strip_all_tags($param);
                        },
                        'required' => true,
                    ),
                    'date_start' => array(
                        'description' => esc_html__('', 'einsatzverwaltung'), // TODO
                        'type' => 'string',
                        'format' => 'date-time',
                        'validate_callback' => array($this, 'validate_date_time'),
                        'required' => true,
                    ),
                    'date_end' => array(
                        'description' => esc_html__('', 'einsatzverwaltung'), // TODO
                        'type' => 'string',
                        'format' => 'date-time',
                        'validate_callback' => array($this, 'validate_date_time'),
                        'required' => false,
                    ),
                    'content' => array(
                        'description' => esc_html__('The content of the report. No HTML allowed, but line breaks are preserved.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => function ($param, $request, $key) {
                            return is_string($param);
                        },
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'required' => false,
                    ),
                    'location' => array(
                        'description' => esc_html__('The location of the incident.', 'einsatzverwaltung'),
                        'type' => 'string',
                        'validate_callback' => function ($param, $request, $key) {
                            return is_string($param);
                        },
                        'sanitize_callback' => 'sanitize_text_field',
                        'required' => false,
                    ),
                    'publish' => array(
                        'description' => esc_html__('If the report should be published immediately.', 'einsatzverwaltung'),
                        'type' => 'boolean',
                        'default' => false,
                        'validate_callback' => function ($param, $request, $key) {
                            return is_bool($param);
                        },
                        'required' => false,
                    ),
                ),
            ),
        ));
    }

    /**
     * @inheritDoc
     */
    public function create_item($request)
    {
        $params = $request->get_params();

        // Calculate the UTC post date
        $start_date_time = DateTime::createFromFormat(DATE_RFC3339, $params['date_start']);
        $start_date_time->setTimezone(new DateTimeZone('UTC'));
        $post_date_gmt = $start_date_time->format('Y-m-d H:i:s');

        $args = array(
            'post_type' => Report::getSlug(),
            'post_title' => $params['reason'],
            'meta_input' => array()
        );

        if (array_key_exists('publish', $params) && $params['publish'] === true) {
            $args['post_status'] = 'publish';
            $args['post_date'] = get_date_from_gmt($post_date_gmt);
            $args['post_date_gmt'] = $post_date_gmt;
        } else {
            $args['post_status'] = 'draft';
            $args['meta_input']['_einsatz_timeofalerting'] = get_date_from_gmt($post_date_gmt);
        }

        // Process optional parameter content
        if (array_key_exists('content', $params) && !empty($params['content'])) {
            $args['post_content'] = $params['content'];
        }

        // Process optional parameter date_end
        if (array_key_exists('date_end', $params)) {
            $end_date_time = DateTime::createFromFormat(DATE_RFC3339, $params['date_end']);
            $end_date_time->setTimezone(new DateTimeZone('UTC'));
            $args['meta_input']['einsatz_einsatzende'] = get_date_from_gmt($end_date_time->format('Y-m-d H:i:s'));
        }

        // Process optional parameter location
        if (array_key_exists('location', $params) && !empty($params['location'])) {
            $args['meta_input']['einsatz_einsatzort'] = $params['location'];
        }

        // Add post to database
        $post = wp_insert_post($args, true);

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
    public function create_item_permissions_check($request)
    {
        return current_user_can('edit_einsatzberichte');
    }

    /**
     * Validates if the passed parameter value is a date conforming to RFC 3339.
     *
     * @param mixed $value
     * @param WP_REST_Request $request
     * @param string $key
     *
     * @return bool
     */
    public function validate_date_time($value, $request, $key): bool
    {
        $dateTime = DateTime::createFromFormat(DATE_RFC3339, $value);
        return $dateTime !== false;
    }
}
