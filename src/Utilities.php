<?php
namespace abrain\Einsatzverwaltung;

use abrain\Einsatzverwaltung\Model\IncidentReport;

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
    public static function getArrayValueIfKey($array, $key, $default)
    {
        return (array_key_exists($key, $array) ? $array[$key] : $default);
    }

    /**
     * Wandelt ein Array von WP_Post-Objekten in ein Array von IncidentReport-Objekten um
     *
     * @param array $arr Array mit WP_Post-Objekten
     *
     * @return array Array mit IncidentReport-Objekten
     */
    public static function postsToIncidentReports($arr)
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
    public static function removePostFromCategory($postId, $category)
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
    public static function sanitizeCheckbox($value)
    {
        if (isset($value) && $value == "1") {
            return 1;
        } else {
            return 0;
        }
    }
}
