<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Frontend\ReportList;
use abrain\Einsatzverwaltung\Model\IncidentReport;

/**
 * Stellt nützliche Helferlein zur Verfügung
 *
 * @author Andreas Brain
 */
class Utilities
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var Core
     */
    private $core;

    /**
     * Utilities constructor.
     *
     * @param Core $core
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /**
     * @param Options $options
     */
    public function setDependencies($options)
    {
        $this->options = $options;
    }

    /**
     * Veranlasst die Zuordnung eines Einsatzberichts (bzw. eines beliebigen Beitrags) zu einer Kategorie
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param int $category Die ID der Kategorie
     */
    public function addPostToCategory($postId, $category)
    {
        wp_set_post_categories($postId, $category, true);
    }

    /**
     * Hilfsfunktion für Checkboxen, übersetzt 1/0 Logik in Haken an/aus
     *
     * @param mixed $value Der zu überprüfende Wert
     *
     * @return bool Der entsprechende boolsche Wert für $value
     */
    public function checked($value)
    {
        return ($value === true || $value == 1 ? 'checked="checked" ' : '');
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getArrayValueIfKey($array, $key, $default)
    {
        return (array_key_exists($key, $array) ? $array[$key] : $default);
    }

    /**
     * Gibt eine lesbare Angabe einer Dauer zurück (z.B. 2 Stunden 12 Minuten)
     *
     * TODO In die Klasse Formatter verschieben
     *
     * @param int $minutes Dauer in Minuten
     * @param bool $abbreviated
     *
     * @return string
     */
    public function getDurationString($minutes, $abbreviated = false)
    {
        if (!is_numeric($minutes) || $minutes < 0) {
            return '';
        }

        if ($minutes < 60) {
            $dauerstring = sprintf(
                '%d %s',
                $minutes,
                ($abbreviated ? 'min' : _n('minute', 'minutes', $minutes, 'einsatzverwaltung'))
            );
        } else {
            $hours = intval($minutes / 60);
            $remainingMinutes = $minutes % 60;
            $dauerstring = $hours . ' ' . ($abbreviated ? 'h' : _n('hour', 'hours', $hours, 'einsatzverwaltung'));
            if ($remainingMinutes > 0) {
                $unit = $abbreviated ? 'min' : _n('minute', 'minutes', $remainingMinutes, 'einsatzverwaltung');
                $dauerstring .= sprintf(' %d %s', $remainingMinutes, $unit);
            }
        }

        return $dauerstring;
    }

    /**
     * Prüft, ob WordPress mindestens in Version $ver läuft
     *
     * @param string $ver gesuchte Version von WordPress
     *
     * @return bool
     */
    public function isMinWPVersion($ver)
    {
        $currentversionparts = explode(".", get_bloginfo('version'));
        if (count($currentversionparts) < 3) {
            $currentversionparts[2] = "0";
        }

        $neededversionparts = explode(".", $ver);
        if (count($neededversionparts) < 3) {
            $neededversionparts[2] = "0";
        }

        if (intval($neededversionparts[0]) > intval($currentversionparts[0])) {
            return false;
        } elseif (intval($neededversionparts[0]) == intval($currentversionparts[0]) &&
            intval($neededversionparts[1]) > intval($currentversionparts[1])
        ) {
            return false;
        } elseif (intval($neededversionparts[0]) == intval($currentversionparts[0]) &&
            intval($neededversionparts[1]) == intval($currentversionparts[1]) &&
            intval($neededversionparts[2]) > intval($currentversionparts[2])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Wandelt ein Array von WP_Post-Objekten in ein Array von IncidentReport-Objekten um
     *
     * @param array $arr Array mit WP_Post-Objekten
     *
     * @return array Array mit IncidentReport-Objekten
     */
    public function postsToIncidentReports($arr)
    {
        $reports = array();
        foreach ($arr as $post) {
            $reports[] = new IncidentReport($post);
        }

        return $reports;
    }

    /**
     * Gibt eine Fehlermeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printError($message)
    {
        echo '<p class="evw_error"><i class="fa fa-exclamation-circle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Gibt eine Warnmeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printWarning($message)
    {
        echo '<p class="evw_warning"><i class="fa fa-exclamation-triangle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Gibt eine Erfolgsmeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printSuccess($message)
    {
        echo '<p class="evw_success"><i class="fa fa-check-circle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Gibt eine Information aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public function printInfo($message)
    {
        echo '<p class="evw_info"><i class="fa fa-info-circle"></i>&nbsp;' . $message . '</p>';
    }

    /**
     * Entfernt die Zuordnung eines Einsatzberichts (bzw. eines beliebigen Beitrags) zu einer Kategorie
     *
     * @param int $postId Die ID des Einsatzberichts
     * @param int $category Die ID der Kategorie
     */
    public function removePostFromCategory($postId, $category)
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
     * @param array $input Der aufzubereitende Wert
     *
     * @return int 0 für false, 1 für true
     */
    public function sanitizeCheckbox($input)
    {
        if (is_array($input)) {
            $arr = $input[0];
            $index = $input[1];
            $value = (array_key_exists($index, $arr) ? $arr[$index] : "");
        } else {
            $value = $input;
        }

        if (isset($value) && $value == "1") {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * Stellt einen sinnvollen Wert für die Anzahl Stellen der laufenden Einsatznummer sicher
     *
     * @param mixed $input
     *
     * @return int
     */
    public function sanitizeEinsatznummerStellen($input)
    {
        $val = intval($input);
        if (is_numeric($val) && $val > 0) {
            return $val;
        } else {
            return $this->options->getDefaultEinsatznummerStellen();
        }
    }

    /**
     * Stellt sicher, dass es sich um einen validen Farbwert im Hexformat handelt
     *
     * TODO NEEDS_WP4.6 das globale sanitize_hex_color() verwenden
     *
     * @param string $color Die Farbe, die überprüft werden soll
     * @param string $default Standardwert, der bei einem Fehler zurückgegeben wird
     *
     * @return string Den übergebenen Farbwert, wenn er korrekt ist, ansonsten den Standardwert
     */
    public function sanitizeHexColor($color, $default)
    {
        if (empty($color)) {
            return $default;
        }
        
        // Es muss ein Gartenzaun mit 3 oder 6 Hexziffern sein
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return $default;
    }

    /**
     * Stellt sicher, dass eine Zahl positiv ist
     *
     * @param mixed $input
     * @param int $defaultvalue
     *
     * @return int
     */
    public function sanitizeNumberGreaterZero($input, $defaultvalue = 0)
    {
        if (is_numeric($input) && intval($input) > 0 && intval($input) < PHP_INT_MAX) {
            return intval($input);
        } else {
            return $defaultvalue;
        }
    }


    /**
     * Stellt sicher, dass nur gültige Spalten-Ids gespeichert werden.
     *
     * @param string $input Kommaseparierte Spalten-Ids
     *
     * @return string Der Eingabestring ohne ungültige Spalten-Ids, bei Problemen werden die Standardspalten
     * zurückgegeben
     */
    public function sanitizeColumns($input)
    {
        if (empty($input)) {
            return $this->options->getDefaultColumns();
        }

        $inputArray = explode(',', $input);
        $validColumnIds = $this->sanitizeColumnsArray($inputArray);

        if (empty($validColumnIds)) {
            return $this->options->getDefaultColumns();
        }

        return implode(',', $validColumnIds);
    }

    /**
     * Bereinigt ein Array von Spalten-Ids, sodass nur gültige Ids darin verbleiben
     *
     * @param $inputArray
     *
     * @return array
     */
    public function sanitizeColumnsArray($inputArray)
    {
        $columns = ReportList::getListColumns();
        $columnIds = array_keys($columns);

        $validColumnIds = array();
        foreach ($inputArray as $colId) {
            $colId = trim($colId);
            if (in_array($colId, $columnIds)) {
                $validColumnIds[] = $colId;
            }
        }

        if (empty($validColumnIds)) {
            $defaultColumns = $this->options->getDefaultColumns();
            $validColumnIds = explode(',', $defaultColumns);
        }

        return $validColumnIds;
    }
}
