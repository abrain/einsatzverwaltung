<?php
namespace abrain\Einsatzverwaltung\DataAccess;

use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Types\Vehicle;
use WP_Term;
use function add_action;
use function array_diff;
use function array_map;
use function array_unique;
use function get_term_meta;
use function intval;
use function is_wp_error;
use function wp_get_post_terms;
use function wp_set_post_terms;

/**
 * Reacts to events related to incident reports.
 */
class ReportActions
{
    public function addHooks()
    {
        add_action('save_post_einsatz', array($this, 'addMissingUnits'), 11);
    }

    /**
     * Assigns missing units to the report, based on the assigned vehicles.
     *
     * @param int $postId
     */
    public function addMissingUnits(int $postId)
    {
        $assignedVehicles = wp_get_post_terms($postId, Vehicle::getSlug());
        if (empty($assignedVehicles) || is_wp_error($assignedVehicles)) {
            return;
        }

        $expectedUnitIds = [];
        /** @var WP_Term[] $assignedVehicles */
        foreach ($assignedVehicles as $vehicle) {
            $unitId = get_term_meta($vehicle->term_id, 'vehicle_unit', true);
            if (!empty($unitId)) {
                $expectedUnitIds[] = intval($unitId);
            }
        }

        if (empty($expectedUnitIds)) {
            return;
        }

        $assignedUnits = wp_get_post_terms($postId, Unit::getSlug());
        $assignedUnitIds = array_map(function (WP_Term $unit) {
            return $unit->term_id;
        }, $assignedUnits);

        // Check for missing unit IDs
        $unitIdsToAdd = array_diff($expectedUnitIds, $assignedUnitIds);
        if (empty($unitIdsToAdd)) {
            return;
        }

        // Append missing units
        wp_set_post_terms($postId, array_unique($unitIdsToAdd), Unit::getSlug(), true);
    }
}
