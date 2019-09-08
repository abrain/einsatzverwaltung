<?php
namespace abrain\Einsatzverwaltung;

use WP_Error;
use wpdb;
use function add_option;
use function add_post_meta;
use function array_keys;
use function category_exists;
use function delete_option;
use function error_log;
use function function_exists;
use function get_gmt_from_date;
use function get_post_meta;
use function get_posts;
use function get_role;
use function get_user_option;
use function update_option;
use function update_post_meta;
use function update_user_option;
use function wp_set_post_categories;

/**
 *
 */
class Update
{
    /**
     * Fürt ein Update der Datenbank duch
     *
     * @param int $currentDbVersion derzeitige Version der Datenbank
     * @param int $targetDbVersion Zielversion der Datenbank
     *
     * @return void|WP_Error
     */
    public function doUpdate($currentDbVersion, $targetDbVersion)
    {
        if (empty($targetDbVersion)) {
            return new WP_Error('', 'Zieldatenbankversion darf nicht leer sein');
        }

        if (empty($currentDbVersion) && $targetDbVersion >= 1) {
            $currentDbVersion = 0;
            $this->upgrade054();
        }

        if ($currentDbVersion < 2 && $targetDbVersion >= 2) {
            $this->upgrade070();
        }

        if ($currentDbVersion < 3 && $targetDbVersion >= 3) {
            $this->upgrade082();
        }

        if ($currentDbVersion < 4 && $targetDbVersion >= 4) {
            $this->upgrade090();
        }

        if ($currentDbVersion < 5 && $targetDbVersion >= 5) {
            $this->upgrade100();
        }

        if ($currentDbVersion < 6 && $targetDbVersion >= 6) {
            $this->upgrade113();
        }

        if ($currentDbVersion < 7 && $targetDbVersion >= 7) {
            $this->upgrade114();
        }

        if ($currentDbVersion < 10 && $targetDbVersion >= 10) {
            $this->upgrade120();
        }

        // The updates to versions 1.3.0 until 1.7.0 require the now removed taxonomy 'fahrzeug'
        if ($currentDbVersion < 50 && $targetDbVersion >= 20) {
            register_taxonomy('fahrzeug', 'einsatz', array(
                'label' => 'Fahrzeuge',
                'public' => false,
                'show_in_rest' => false,
                'hierarchical' => true
            ));
        }

        if ($currentDbVersion < 20 && $targetDbVersion >= 20) {
            $this->upgrade130();
        }

        if ($currentDbVersion < 21 && $targetDbVersion >= 21) {
            $this->upgrade134();
        }

        if ($currentDbVersion < 30 && $targetDbVersion >= 30) {
            $this->upgrade140();
        }

        if ($currentDbVersion < 40 && $targetDbVersion >= 40) {
            $this->upgrade150();
        }

        if ($currentDbVersion < 41 && $targetDbVersion >= 41) {
            $this->upgrade162();
        }

        if ($currentDbVersion < 50 && $targetDbVersion >= 50) {
            $this->upgrade170();
        }

        // From version 1.7.0 on this taxonomy does no longer exist
        unregister_taxonomy('fahrzeug');
    }

    /**
     * GMT-Datum wurde nicht gespeichert EVW-58
     */
    private function upgrade054()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $allReports = get_posts(array(
            'nopaging' => true,
            'orderby' => 'post_date',
            'order' => 'ASC',
            'post_type' => 'einsatz',
            'post_status' => array('publish', 'private')
        ));
        foreach ($allReports as $bericht) {
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

        update_option('einsatzvw_db_version', 1);
    }

    private function upgrade070()
    {
        update_option('einsatzvw_cap_roles_administrator', 1);
        $roleObject = get_role('administrator');
        foreach (UserRightsManager::$capabilities as $cap) {
            $roleObject->add_cap($cap);
        }

        update_option('einsatzvw_db_version', 2);
    }

    private function upgrade082()
    {
        delete_option('einsatzvw_show_links_in_excerpt');
        update_option('einsatzvw_db_version', 3);
    }

    /**
     * @since 0.9.0
     */
    private function upgrade090()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $wpdb->delete(
            $wpdb->postmeta,
            array(
                'meta_key' => 'einsatz_mannschaft',
                'meta_value' => '0'
            )
        );

        update_option('einsatzvw_db_version', 4);
    }

    /**
     * @since 1.0.0
     */
    private function upgrade100()
    {
        add_option('einsatzvw_rewrite_slug', 'einsaetze');
        update_option('einsatzvw_db_version', 5);
    }

    /**
     * Entfernt die Berechtigungen aus den Benutzerrollen und die unnötige Option für Administratoren
     *
     * @since 1.1.3
     */
    private function upgrade113()
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
        }
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $roleSlug) {
                $roleObject = get_role($roleSlug);
                foreach (UserRightsManager::$capabilities as $cap) {
                    $roleObject->remove_cap($cap);
                }
            }
        }

        delete_option('einsatzvw_cap_roles_administrator');
        update_option('einsatzvw_db_version', 6);
    }

    /**
     * Aktualisiert die Rewrite Rules nach einer Änderung
     *
     * @since 1.1.4
     */
    private function upgrade114()
    {
        update_option('einsatzvw_flush_rewrite_rules', 1);
        update_option('einsatzvw_db_version', 7);
    }

    /**
     * Fügt alle veröffentlichten Einsatzberichte einer Kategorie hinzu, wenn diese in den Einstellungen für die
     * Einsatzberichte gesetzt wurde
     *
     * @since 1.2.0
     */
    private function upgrade120()
    {
        global $wpdb;

        // Alle veröffentlichten Einsatzberichte einer Kategorie hinzufügen, wenn diese in den Einstellungen für die
        // Einsatzberichte gesetzt wurde
        if (!function_exists('category_exists')) {
            require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
        }

        $categoryId = get_option('einsatzvw_category', -1);
        if (category_exists($categoryId)) {
            $posts = get_posts(array(
                'post_type' => 'einsatz',
                'post_status' => array('publish', 'private'),
                'numberposts' => -1
            ));

            foreach ($posts as $post) {
                wp_set_post_categories($post->ID, $categoryId, true);
            }
        }

        // Aktualisiert sämtliche laufenden Nummern der Einsatzberichte
        $years = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT YEAR(post_date) AS years FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s;",
            array('einsatz', 'publish')
        ));
        foreach ($years as $year) {
            $posts = get_posts(array(
                'nopaging' => true,
                'orderby' => 'post_date',
                'order' => 'ASC',
                'post_type' => 'einsatz',
                'post_status' => array('publish', 'private'),
                'year' => $year
            ));

            $expectedNumber = 1;
            foreach ($posts as $post) {
                $actualNumber = get_post_meta($post->ID, 'einsatz_seqNum', true);
                if ($expectedNumber != $actualNumber) {
                    update_post_meta($post->ID, 'einsatz_seqNum', $expectedNumber);
                }
                $expectedNumber++;
            }
        }

        // Setzt alle alten Einsatzberichte auf 'nicht als besonders markiert', wichtig für das Einfügen in die
        // Mainloop. Außerdem wird die Option, ob nur besondere Einsatzberichte zwischen den WordPress-Beiträgen
        // auftauchen sollen, umbenannt.
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

        update_option('einsatzvw_db_version', 10);
    }

    private function upgrade130()
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

                // nur bekannte metakeys umwandeln
                if (!in_array($metakey, $metakeys)) {
                    continue;
                }

                // Prüfen, ob ein Term-Split stattfand, der noch nicht behandelt wurde
                $termIdAfterSplit = wp_get_split_term($termId, $taxonomy);
                if (false !== $termIdAfterSplit) {
                    $termId = $termIdAfterSplit;
                }

                $addTermMeta = add_term_meta($termId, $metakey, $metavalue, true);
                if (!is_wp_error($addTermMeta) && false !== $addTermMeta) {
                    delete_option($key);
                }
            }
        }

        // Einsatznummern in Postmeta kopieren
        $posts = get_posts(array(
            'nopaging' => true,
            'post_type' => 'einsatz',
            'post_status' => array('publish', 'private'),
        ));
        foreach ($posts as $post) {
            update_post_meta($post->ID, 'einsatz_incidentNumber', get_post_field('post_name', $post->ID));
        }

        // Admin Notice aktivieren
        $this->addAdminNotice('regenerateSlugs');

        update_option('einsatzvw_db_version', 20);
    }

    /**
     * Adds a defined value (0) to the `special` annotation for all reports that did not have a value before
     *
     * @since 1.3.4
     */
    private function upgrade134()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $postIds = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'einsatz'");
        $hasMetaKey = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'einsatz_special'");
        $idsWithoutMetaKey = array_diff($postIds, $hasMetaKey);

        foreach ($idsWithoutMetaKey as $id) {
            add_post_meta($id, 'einsatz_special', 0, true);
        }

        update_option('einsatzvw_db_version', 21);
    }

    /**
     * Adds newly introduced options and removes their predecessors
     *
     * @since 1.4.0
     */
    private function upgrade140()
    {
        add_option('einsatzverwaltung_use_reporttemplate', 'no');
        add_option('einsatzverwaltung_reporttemplate', '');
        add_option('einsatzverwaltung_use_excerpttemplate', '0');
        add_option('einsatzverwaltung_excerpttemplate', '');

        delete_option('einsatzvw_excerpt_type');
        delete_option('einsatzvw_excerpt_type_feed');

        update_option('einsatzvw_db_version', 30);
    }

    private function upgrade150()
    {
        add_option('einsatz_support_posttag', '1');

        update_option('einsatzvw_db_version', 40);
    }

    /**
     * - Transforms replacement text for empty content into an option
     * - Makes sure custom-field metabox is properly hidden
     *
     * @since 1.6.2
     */
    private function upgrade162()
    {
        global $wpdb;

        /**
         * Transforms the replacement text for empty content into an option, while maintaining the current behaviour
         * that empty content is not replaced when using templates
         */
        $replacementText = '';
        if (get_option('einsatzverwaltung_use_reporttemplate', 'no') === 'no') {
            $replacementText = 'Kein Einsatzbericht vorhanden';
        }
        add_option('einsatzverwaltung_report_contentifempty', $replacementText);

        /**
         * Makes sure, the custom-field metabox is also hidden for users, who already have hidden metaboxes on the edit
         * screen for the reports before. The default hidden metaboxes do not apply to them.
         */
        $userIds = $wpdb->get_col("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'metaboxhidden_einsatz'");
        foreach ($userIds as $userId) {
            $hidden = get_user_option('metaboxhidden_einsatz', $userId);

            if (!is_array($hidden)) {
                continue;
            }

            if (!in_array('postcustom', $hidden)) {
                $hidden[] = 'postcustom';
                update_user_option($userId, 'metaboxhidden_einsatz', $hidden, true);
            }
        }

        update_option('einsatzvw_db_version', 41);
    }

    private function upgrade170()
    {
        $oldVehicles = get_terms(array('taxonomy' => 'fahrzeug', 'hide_empty' => false, 'childless' => true));
        foreach ($oldVehicles as $oldVehicle) {
            $newVehicle = wp_insert_post(array(
                'post_type' => 'evw_vehicle',
                'post_title' => $oldVehicle->name,
                'post_name' => $oldVehicle->slug,
                'post_content' => $oldVehicle->description,
                'post_status' => 'publish',
                'menu_order' => get_term_meta($oldVehicle->term_id, 'vehicleorder', true),
                'meta_input' => array(
                    '_page_id' => get_term_meta($oldVehicle->term_id, 'fahrzeugpid', true)
                )
            ));

            if (is_wp_error($newVehicle)) {
                error_log("Vehicle could not be created: {$newVehicle->get_error_message()}");
                $this->addAdminNotice('failedVehicleMigration');
                continue;
            }

            // Assign new vehicle to the reports that have the current term assigned
            $reportsWithVehicle = get_objects_in_term($oldVehicle->term_id, 'fahrzeug');
            foreach ($reportsWithVehicle as $reportId) {
                add_post_meta($reportId, '_evw_vehicle', $newVehicle);
            }
        }

        update_option('einsatzvw_flush_rewrite_rules', 1);
        update_option('einsatzvw_db_version', 50);
    }

    /**
     * Fügt einen Bezeichner für eine Admin Notice der Liste der noch anzuzeigenden Notices hinzu
     *
     * @param string $slug Bezeichner für die Notice
     */
    private function addAdminNotice($slug)
    {
        $notices = get_option('einsatzverwaltung_admin_notices');

        if (!is_array($notices)) {
            $notices = array();
        }

        // Slug soll maximal einmal auftauchen
        if (in_array($slug, $notices)) {
            return;
        }

        $notices[] = $slug;
        update_option('einsatzverwaltung_admin_notices', $notices);
    }
}
