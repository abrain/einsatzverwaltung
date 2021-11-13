<?php
namespace abrain\Einsatzverwaltung\Api;

use Exception;
use function array_map;
use function current_user_can;
use function sort;
use function wp_send_json_error;
use function wp_send_json_success;
use const SORT_FLAG_CASE;
use const SORT_STRING;

/**
 * Provides handlers for requests through admin-ajax.php
 */
class Ajax
{
    public function addHooks()
    {
        add_action('wp_ajax_einsatzverwaltung_used_locations', [$this, 'usedLocationsHandler']);
    }

    public function usedLocationsHandler()
    {
        check_ajax_referer('einsatzverwaltung_used_values');

        if (!current_user_can('edit_einsatzberichte')) {
            wp_send_json_error(null, 403);
            return;
        }

        try {
            $locations = $this->getUsedLocations();
        } catch (Exception $e) {
            wp_send_json_error(null, 500);
            return;
        }
        wp_send_json_success($locations);
    }

    /**
     * Gets the previously used values for the incident location.
     *
     * @return string[]
     * @throws Exception
     */
    private function getUsedLocations(): array
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value <> %s",
            ['einsatz_einsatzort', '']
        );
        $results = $wpdb->get_results($query, OBJECT);

        if ($results === null) {
            throw new Exception('Could not query used locations');
        }

        $locations = array_map(function ($result) {
            return $result->meta_value;
        }, $results);
        sort($locations, SORT_STRING | SORT_FLAG_CASE);
        return $locations;
    }
}
