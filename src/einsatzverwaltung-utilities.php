<?php
namespace abrain\Einsatzverwaltung;

/**
 * Stellt nützliche Helferlein zur Verfügung
 *
 * @author Andreas Brain
 */
class Utilities
{
    /**
     * Hilfsfunktion für Checkboxen, übersetzt 1/0 Logik in Haken an/aus
     *
     * @param mixed $value Der zu überprüfende Wert
     *
     * @return bool Der entsprechende boolsche Wert für $value
     */
    public static function checked($value)
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
    public static function getArrayValueIfKey($array, $key, $default)
    {
        return (array_key_exists($key, $array) ? $array[$key] : $default);
    }

    /**
     * Gibt eine lesbare Angabe einer Dauer zurück (z.B. 2 Stunden 12 Minuten)
     *
     * @param int $minutes Dauer in Minuten
     * @param bool $abbreviated
     *
     * @return string
     */
    public static function getDurationString($minutes, $abbreviated = false)
    {
        if (!is_numeric($minutes) || $minutes < 0) {
            return '';
        }

        if ($minutes < 60) {
            $dauerstring = $minutes . ' ' . ($abbreviated ? 'min' : _n('Minute', 'Minuten', $minutes));
        } else {
            $hours = intval($minutes / 60);
            $remainingMinutes = $minutes % 60;
            $dauerstring = $hours . ' ' . ($abbreviated ? 'h' : _n('Stunde', 'Stunden', $hours));
            if ($remainingMinutes > 0) {
                $dauerstring .= ' ' . $remainingMinutes . ' ' . ($abbreviated ? 'min' : _n('Minute', 'Minuten', $remainingMinutes));
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
    public static function isMinWPVersion($ver)
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
                    intval($neededversionparts[1]) > intval($currentversionparts[1])) {
            return false;
        } elseif (intval($neededversionparts[0]) == intval($currentversionparts[0]) &&
                    intval($neededversionparts[1]) == intval($currentversionparts[1]) &&
                    intval($neededversionparts[2]) > intval($currentversionparts[2])) {
            return false;
        }

        return true;
    }


    /**
     * Gibt eine Fehlermeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public static function printError($message)
    {
        echo '<p class="evw_error"><i class="fa fa-exclamation-circle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Gibt eine Warnmeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public static function printWarning($message)
    {
        echo '<p class="evw_warning"><i class="fa fa-exclamation-triangle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Gibt eine Erfolgsmeldung aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public static function printSuccess($message)
    {
        echo '<p class="evw_success"><i class="fa fa-check-circle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Gibt eine Information aus
     *
     * @param string $message Meldung, die ausgegeben werden soll
     */
    public static function printInfo($message)
    {
        echo '<p class="evw_info"><i class="fa fa-info-circle"></i>&nbsp;' . $message . '</p>';
    }


    /**
     * Bereitet den Formularwert einer Checkbox für das Speichern in der Datenbank vor
     *
     * @param array $input Der aufzubereitende Wert
     *
     * @return int 0 für false, 1 für true
     */
    public static function sanitizeCheckbox($input)
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
    public static function sanitizeEinsatznummerStellen($input)
    {
        $val = intval($input);
        if (is_numeric($val) && $val > 0) {
            return $val;
        } else {
            return Options::getDefaultEinsatznummerStellen();
        }
    }


    /**
     * Stellt einen gültigen Wert der Exzerpttypeinstellung sicher
     *
     * @param string $input Eingegebener Wert
     *
     * @return string Der Eingabewert, wenn gültig, ansonsten ein Standardwert
     */
    public static function sanitizeExcerptType($input)
    {
        if (array_key_exists($input, Core::getExcerptTypes())) {
            return $input;
        } else {
            return Options::getDefaultExcerptType();
        }
    }


    /**
     * Stellt sicher, dass eine Zahl positiv ist
     *
     * @param mixed $input
     * @param int $defaultvalue
     *
     * @return int
     */
    public static function sanitizePositiveNumber($input, $defaultvalue = 0)
    {
        $val = intval($input);
        if (is_numeric($val) && $val >= 0) {
            return $val;
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
    public static function sanitizeColumns($input)
    {
        if (empty($input)) {
            return Options::getDefaultColumns();
        }

        $columns = Core::getListColumns();
        $columnIds = array_keys($columns);

        $inputArray = explode(',', $input);
        $validColumnIds = array();
        foreach ($inputArray as $colId) {
            $colId = trim($colId);
            if (in_array($colId, $columnIds)) {
                $validColumnIds[] = $colId;
            }
        }

        if (empty($validColumnIds)) {
            return Options::getDefaultColumns();
        }

        return implode(',', $validColumnIds);
    }
}
