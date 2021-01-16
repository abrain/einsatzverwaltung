<?php
namespace abrain\Einsatzverwaltung\Jobs;

use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Types\Unit;
use WP_Term;
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
        $units = get_terms(['taxonomy' => Unit::getSlug(), 'hide_empty' => false]);
        if (empty($units)) {
            // No Units exist, so there is no need for migration.
            return;
        }

        $unitIdMapping = $this->getUnitIdMapping($units);
        if (empty($unitIdMapping)) {
            // No migrated Units found.
            return;
        }

        $reportsWithUnits = get_posts([
            'numberposts' => 50,
            'fields' => 'ids',
            'post_type' => Report::getSlug(),
            'post_status' => ['publish', 'private'],
            'meta_query' => [['key' => '_evw_unit', 'compare' => 'IN', 'value' => array_keys($unitIdMapping)]]
        ]);

        if (empty($reportsWithUnits)) {
            // The migrated Units were never assigned to any Report, or we are done with the migration.
            return;
        }

        // Run this job again in a minute (or as soon as WordPress lets us)
        wp_schedule_single_event(time() + 60, 'einsatzverwaltung_migrate_units');

        foreach ($reportsWithUnits as $reportId) {
            $this->assignReportToNewUnit($reportId, $unitIdMapping);
        }
    }

    /**
     * @param WP_Term[] $units
     *
     * @return int[] The IDs of the new Units keyed by the IDs of the old Units
     */
    private function getUnitIdMapping(array $units): array
    {
        $unitIdMap = [];
        foreach ($units as $unit) {
            $oldId = get_term_meta($unit->term_id, 'old_unit_id', true);
            if (empty($oldId)) {
                continue;
            }
            $unitIdMap[$oldId] = $unit->term_id;
        }

        return $unitIdMap;
    }

    /**
     * @param int $reportId
     * @param int[] $unitIdMap
     */
    private function assignReportToNewUnit(int $reportId, array $unitIdMap): void
    {
        global $wpdb;

        $assignedUnits = get_post_meta($reportId, '_evw_unit');
        $newUnits = [];
        foreach ($assignedUnits as $assignedUnit) {
            $newUnits[] = $unitIdMap[intval($assignedUnit)];
        }
        wp_set_post_terms($reportId, $newUnits, Unit::getSlug());

        // Rename postmeta entry, so we know which one is already migrated
        $wpdb->query($wpdb->prepare(
            "UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s AND post_id = %d",
            '_evw_legacy_unit',
            '_evw_unit',
            $reportId
        ));
    }
}
