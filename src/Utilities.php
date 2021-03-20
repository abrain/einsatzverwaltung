<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Types\Vehicle;
use WP_Term;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function get_term_meta;
use function usort;

/**
 * Stellt nützliche Helferlein zur Verfügung
 *
 * @author Andreas Brain
 */
class Utilities
{
    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function getArrayValueIfKey(array $array, string $key, $default)
    {
        return (array_key_exists($key, $array) ? $array[$key] : $default);
    }

    /**
     * Groups the vehicles into an array per unit
     *
     * @param WP_Term[] $vehicles
     *
     * @return array
     */
    public static function groupVehiclesByUnit(array $vehicles): array
    {
        $grouped = [];
        foreach ($vehicles as $vehicle) {
            $unitId = get_term_meta($vehicle->term_id, 'vehicle_unit', true);
            if (empty($unitId)) {
                $unitId = -1;
            }
            if (!array_key_exists($unitId, $grouped)) {
                $grouped[$unitId] = [];
            }
            $grouped[$unitId][] = $vehicle;
        }

        // Sort the units
        $unitIds = array_keys($grouped);
        /** @var WP_Term[] $units */
        $units = array_map('get_term', array_filter($unitIds, function ($unitId) {
            return $unitId > 0;
        }));
        usort($units, array(Unit::class, 'compare'));
        $groupedAndSorted = [];
        foreach ($units as $unit) {
            $groupedAndSorted[$unit->term_id] = $grouped[$unit->term_id];
        }
        if (array_key_exists(-1, $grouped)) {
            $groupedAndSorted[-1] = $grouped[-1];
        }

        // Sort the vehicles per unit
        foreach ($unitIds as $unitId) {
            usort($groupedAndSorted[$unitId], array(Vehicle::class, 'compareVehicles'));
        }

        return $groupedAndSorted;
    }

    /**
     * Gibt eine Fehlermeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printError(string $message)
    {
        echo '<p class="notice notice-error">' . $message . '</p>';
    }


    /**
     * Gibt eine Warnmeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printWarning(string $message)
    {
        echo '<p class="notice notice-warning">' . $message . '</p>';
    }


    /**
     * Gibt eine Erfolgsmeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printSuccess(string $message)
    {
        echo '<p class="notice notice-success">' . $message . '</p>';
    }


    /**
     * Gibt eine Information aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printInfo(string $message)
    {
        echo '<p class="notice notice-info">' . $message . '</p>';
    }

    /**
     * Entfernt die Zuordnung eines Einsatzberichts (bzw. eines beliebigen Beitrags) zu einer Kategorie
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param int $category Die ID der Kategorie
     */
    public static function removePostFromCategory(int $postId, int $category)
    {
        $categories = wp_get_post_categories($postId);
        $key = array_search($category, $categories);
        if ($key !== false) {
            array_splice($categories, $key, 1);
            wp_set_post_categories($postId, $categories);
        }
    }

    /**
     * Bereitet den Formularwert einer Checkbox für das Speichern in der Datenbank vor
     *
     * @param mixed $value Der aufzubereitende Wert
     *
     * @return int 0 für false, 1 für true
     */
    public static function sanitizeCheckbox($value): int
    {
        if (isset($value) && $value == "1") {
            return 1;
        } else {
            return 0;
        }
    }
}
