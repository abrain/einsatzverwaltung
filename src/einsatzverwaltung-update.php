<?php
namespace abrain\Einsatzverwaltung;

use WP_Term;
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
     * @var Data
     */
    private $data;

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
     * @param Data $data
     */
    public function __construct($core, $options, $utilities, $data)
    {
        $this->core = $core;
        $this->options = $options;
        $this->utilities = $utilities;
        $this->data = $data;
    }

    /**
     * Fürt ein Update der Datenbank duch
     *
     * @param int $currentDbVersion derzeitige Version der Datenbank
     * @param int $targetDbVersion Zielversion der Datenbank
     */
    public function doUpdate($currentDbVersion, $targetDbVersion)
    {
        if (empty($currentDbVersion) || empty($targetDbVersion)) {
            error_log('Parameter für Datenbank-Update unvollständig');
            return;
        }

        // Kein Timeout während des Updates
        set_time_limit(0);

        while ($currentDbVersion < $targetDbVersion) {
            $currentDbVersion ++;
            error_log("Update auf DB-Version {$currentDbVersion}...");
            $func = array($this, "updateTo{$currentDbVersion}");
            if (!is_callable($func)) {
                error_log("Keine Update-Methode für Datenbankversion {$currentDbVersion} gefunden!");
                break;
            }

            $result = call_user_func($func);
            if ($result === false) {
                error_log("Datenbankupdate auf Version {$currentDbVersion} ist fehlgeschlagen");
                break;
            }

            update_option('einsatzvw_db_version', $currentDbVersion);
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
     * @since 0.9.0
     *
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
     * @since 1.1.3
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
     *
     * @since 1.1.4
     */
    private function updateTo7()
    {
        $this->options->setFlushRewriteRules(true);
    }

    /**
     * Fügt alle veröffentlichten Einsatzberichte einer Kategorie hinzu, wenn diese in den Einstellungen für die
     * Einsatzberichte gesetzt wurde
     *
     * @since 1.2.0
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

    /**
     * Aktualisiert sämtliche laufenden Nummern der Einsatzberichte
     *
     * @since 1.2.0
     */
    private function updateTo9()
    {
        $this->data->updateSequenceNumbers();
    }

    /**
     * Setzt alle alten Einsatzberichte auf 'nicht als besonders markiert', wichtig für das Einfügen in die Mainloop.
     * Außerdem wird die Option, ob nur besondere Einsatzberichte zwischen den WordPress-Beiträgen auftauchen sollen,
     * umbenannt.
     *
     * @since 1.2.0
     */
    private function updateTo10()
    {
        $posts = get_posts(array(
            'post_type' => 'einsatz',
            'post_status' => array('publish', 'private'),
            'numberposts' => -1
        ));

        foreach ($posts as $post) {
            add_post_meta($post->ID, 'einsatz_special', '0', true);
        }

        // Option umbenennen, betrifft nur Nutzer der Betaversionen von Version 1.2.0
        $option = get_option('einsatzvw_category_only_special');
        if ($option !== false) {
            add_option('einsatzvw_loop_only_special', $option);
        }
        delete_option('einsatzvw_category_only_special');
    }

    private function updateTo11()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $taxonomies = array(
            'exteinsatzmittel' => array('url'),
            'fahrzeug' => array('fahrzeugpid', 'vehicleorder')
        );

        foreach ($taxonomies as $taxonomy => $metakeys) {
            $rows = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE 'evw_tax_{$taxonomy}_%'");

            foreach ($rows as $row) {
                $key = $row->option_name;
                preg_match('/evw_tax_' . $taxonomy . '_(\d+)_([a-z]+)/', $key, $matches);
                $termId = $matches[1];
                $metakey = $matches[2];
                $metavalue = $row->option_value;

                // Prüfen, ob ein Term-Split stattfand, der noch nicht behandelt wurde
                $termIdAfterSplit = wp_get_split_term($termId, $taxonomy);
                if (false !== $termIdAfterSplit) {
                    error_log("Unbehandelter Term-Split: $termId => $termIdAfterSplit");
                    $termId = $termIdAfterSplit;
                }

                $addTermMeta = add_term_meta($termId, $metakey, $metavalue, true);
                if (!is_wp_error($addTermMeta) && false !== $addTermMeta) {
                    delete_option($key);
                }
            }
        }
    }
}
