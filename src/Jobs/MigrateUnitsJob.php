<?php
namespace abrain\Einsatzverwaltung\Jobs;

use abrain\Einsatzverwaltung\Types\Unit;
use function array_keys;
use function get_post_meta;
use function get_posts;
use function get_term_meta;
use function get_terms;
use function intval;
use function time;
use function wp_schedule_single_event;
use function wp_set_post_terms;

/**
 * Background job that assigns Reports to the new Units (custom taxonomy), in case they were affiliated with the
 * previous version of the Units (custom post type).
 *
 * @package abrain\Einsatzverwaltung\Jobs
 */
class MigrateUnitsJob
{
    public function run()
    {
        global $wpdb;

        $units = get_terms(['taxonomy' => Unit::getSlug(), 'hide_empty' => false]);
        if (empty($units)) {
            // No Units exist, so there is no need for migration.
            return;
        }

        $unitIdMap = [];
        foreach ($units as $unit) {
            $oldId = get_term_meta($unit->term_id, 'old_unit_id', true);
            if (empty($oldId)) {
                continue;
            }
            $unitIdMap[$oldId] = $unit->term_id;
        }
        if (empty($unitIdMap)) {
            // No migrated Units found.
            return;
        }

        $reportsWithUnits = get_posts([
            'numberposts' => 25,
            'fields' => 'ids',
            'post_type' => 'einsatz',
            'post_status' => ['publish', 'private'],
            'meta_query' => [['key' => '_evw_unit', 'compare' => 'IN', 'value' => array_keys($unitIdMap)]]
        ]);

        if (empty($reportsWithUnits)) {
            // The migrated Units were never assigned to any Report, or we are done with the migration.
            return;
        }

        // Run this job again in a minute (or as soon as WordPress lets us)
        wp_schedule_single_event(time() + 60, 'einsatzverwaltung_migrate_units');

        foreach ($reportsWithUnits as $reportId) {
            $assignedUnits = get_post_meta($reportId, '_evw_unit');
            $newUnits = [];
            foreach ($assignedUnits as $assignedUnit) {
                $newUnits[] = $unitIdMap[intval($assignedUnit)];
            }
            wp_set_post_terms($reportId, $newUnits, Unit::getSlug());
            $wpdb->query($wpdb->prepare(
                "UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s AND post_id = %d",
                '_evw_legacy_unit',
                '_evw_unit',
                $reportId
            ));
        }
    }
}
