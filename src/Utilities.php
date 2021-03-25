<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Types\Unit;
use abrain\Einsatzverwaltung\Types\Vehicle;
use WP_Term;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_map;
use function get_term_meta;
use function get_terms;
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
     * @return array Array keyed by unit ID with arrays of vehicles as values. The keys and the vehicles arrays are
     * sorted by the user defined order. Units without vehicles are included and have an empty array as value, vehicles
     * without units are put into the array of key -1, which always comes last.
     */
    public static function groupVehiclesByUnit(array $vehicles): array
    {
        // Get and sort the units
        $units = get_terms(['taxonomy' => Unit::getSlug(), 'hide_empty' => false]);
        usort($units, array(Unit::class, 'compare'));

        // Initialize the array with empty lists per unit ID
        $vehiclesByUnit = array_fill_keys(array_map(function ($unit) {
            return $unit->term_id;
        }, $units), []);

        // Add vehicles to their respective unit list
        foreach ($vehicles as $vehicle) {
            $unitId = get_term_meta($vehicle->term_id, 'vehicle_unit', true);
            if (empty($unitId)) {
                $unitId = -1;
            }
            if (!array_key_exists($unitId, $vehiclesByUnit)) {
                $vehiclesByUnit[$unitId] = [];
            }
            $vehiclesByUnit[$unitId][] = $vehicle;
        }

        // Sort the vehicles per unit
        foreach (array_keys($vehiclesByUnit) as $unitId) {
            usort($vehiclesByUnit[$unitId], array(Vehicle::class, 'compareVehicles'));
        }

        return $vehiclesByUnit;
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
