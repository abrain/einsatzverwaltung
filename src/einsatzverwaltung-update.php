<?php
namespace abrain\Einsatzverwaltung;

use wpdb;

/**
 *
 */
class Update
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * Update constructor.
     * @param Core $core
     * @param Options $options
     * @param Utilities $utilities
     */
    public function __construct($core, $options, $utilities)
    {
        $this->core = $core;
        $this->options = $options;
        $this->utilities = $utilities;
    }

    /**
     * Fürt ein Update der Datenbank duch
     *
     * @param int $current_db_ver derzeitige Version der Datenbank
     * @param int $target_db_ver Zielversion der Datenbank
     */
    public function doUpdate($current_db_ver, $target_db_ver)
    {
        if (empty($current_db_ver) || empty($target_db_ver)) {
            error_log('Parameter für Datenbank-Update unvollständig');
            return;
        }

        // Kein Timeout während des Updates
        set_time_limit(0);

        while ($current_db_ver < $target_db_ver) {
            $current_db_ver ++;
            error_log("Update auf DB-Version {$current_db_ver}...");
            $func = array($this, "updateTo{$current_db_ver}");
            if (!is_callable($func)) {
                error_log("Keine Update-Methode für Datenbankversion {$current_db_ver} gefunden!");
                break;
            }

            $result = call_user_func($func);
            if ($result === false) {
                error_log("Datenbankupdate auf Version {$current_db_ver} ist fehlgeschlagen");
                break;
            }

            update_option('einsatzvw_db_version', $current_db_ver);
        }

        error_log("Datenbank-Update beendet");
    }

    /**
     * GMT-Datum wurde nicht gespeichert EVW-58
     */
    private function updateTo1()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        foreach (Data::getEinsatzberichte('') as $bericht) {
            $gmtdate = get_gmt_from_date($bericht->post_date);
            $result = $wpdb->update(
                $wpdb->posts,
                array('post_date_gmt' => $gmtdate),
                array('ID' => $bericht->ID),
                array('%s'),
                array('%d')
            );
            if (false === $result) {
                error_log('Problem beim Aktualisieren des GMT-Datums bei Post-ID ' . $bericht->ID);
            }
        }
    }

    private function updateTo2()
    {
        update_option('einsatzvw_cap_roles_administrator', 1);
        $role_obj = get_role('administrator');
        foreach ($this->core->getCapabilities() as $cap) {
            $role_obj->add_cap($cap);
        }
    }

    /**
     * @return bool True bei Erfolg, False bei Fehler
     */
    private function updateTo3()
    {
        delete_option('einsatzvw_show_links_in_excerpt');
        return true;
    }

    /**
     * @return bool True bei Erfolg, False bei Fehler
     */
    private function updateTo4()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $result = $wpdb->delete(
            $wpdb->postmeta,
            array(
                'meta_key' => 'einsatz_mannschaft',
                'meta_value' => '0'
            )
        );
        return (false !== $result);
    }

    /**
     * @since 1.0.0
     *
     * @return bool Gibt immer True zurück
     */
    private function updateTo5()
    {
        add_option('einsatzvw_rewrite_slug', 'einsaetze');
        return true;
    }

    /**
     * Entfernt die Berechtigungen aus den Benutzerrollen und die unnötige Option für Administratoren
     *
     * @return bool Gibt immer True zurück
     */
    private function updateTo6()
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
        }
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $role_slug) {
                $role_obj = get_role($role_slug);
                foreach ($this->core->getCapabilities() as $cap) {
                    error_log("Remove $cap from $role_slug");
                    $role_obj->remove_cap($cap);
                }
            }
        }

        delete_option('einsatzvw_cap_roles_administrator');
        return true;
    }

    /**
     * Aktualisiert die Rewrite Rules nach einer Änderung
     */
    private function updateTo7()
    {
        $this->options->setFlushRewriteRules(true);
    }

    /**
     * Fügt alle veröffentlichten Einsatzberichte einer Kategorie hinzu, wenn diese in den Einstellungen für die
     * Einsatzberichte gesetzt wurde
     */
    private function updateTo8()
    {
        if (!function_exists('category_exists')) {
            require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
        }

        $categoryId = $this->options->getEinsatzberichteCategory();
        if (category_exists($categoryId)) {
            $posts = get_posts(array(
                'post_type' => 'einsatz',
                'post_status' => array('publish', 'private'),
                'numberposts' => -1
            ));

            foreach ($posts as $post) {
                $this->utilities->addPostToCategory($post->ID, $categoryId);
            }
        }
    }
}
