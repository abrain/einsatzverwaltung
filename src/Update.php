<?php
namespace abrain\Einsatzverwaltung;

use WP_Error;
use WP_User;
use function add_option;
use function add_post_meta;
use function add_term_meta;
use function array_key_exists;
use function array_keys;
use function array_map;
use function delete_option;
use function delete_term_meta;
use function error_log;
use function function_exists;
use function get_editable_roles;
use function get_option;
use function get_permalink;
use function get_post_meta;
use function get_post_type;
use function get_posts;
use function get_term_meta;
use function get_users;
use function is_array;
use function is_wp_error;
use function register_taxonomy;
use function time;
use function unregister_taxonomy;
use function update_option;
use function update_term_meta;
use function wp_insert_term;
use function wp_schedule_single_event;
use const ABSPATH;

/**
 * Performs data structure and data migrations after a plugin upgrade
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
    public function doUpdate(int $currentDbVersion, int $targetDbVersion)
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

        if ($currentDbVersion < 51 && $targetDbVersion >= 51) {
            $this->upgrade171();
        }

        // Register taxonomy stub, so that convenience functions work during the update
        register_taxonomy('evw_unit', 'einsatz');

        if ($currentDbVersion < 60 && $targetDbVersion >= 60) {
            $this->upgrade180();
        }

        // Unregister the taxonomy again, so it can be registered properly later
        unregister_taxonomy('evw_unit');

        if ($currentDbVersion < 71 && $targetDbVersion >= 71) {
            $this->upgrade1100();
        }

        if ($currentDbVersion < 72 && $targetDbVersion >= 72) {
            $this->upgrade1102();
        }

        if ($currentDbVersion < 80 && $targetDbVersion >= 80) {
            $this->upgrade1111();
        }
    }

    /**
     * GMT-Datum wurde nicht gespeichert EVW-58
     */
    private function upgrade054()
    {
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
        $capabilities = array(
            'edit_einsatzberichte',
            'edit_private_einsatzberichte',
            'edit_published_einsatzberichte',
            'edit_others_einsatzberichte',
            'publish_einsatzberichte',
            'read_private_einsatzberichte',
            'delete_einsatzberichte',
            'delete_private_einsatzberichte',
            'delete_published_einsatzberichte',
            'delete_others_einsatzberichte'
        );
        foreach ($capabilities as $cap) {
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
            $capabilities = array(
                'edit_einsatzberichte',
                'edit_private_einsatzberichte',
                'edit_published_einsatzberichte',
                'edit_others_einsatzberichte',
                'publish_einsatzberichte',
                'read_private_einsatzberichte',
                'delete_einsatzberichte',
                'delete_private_einsatzberichte',
                'delete_published_einsatzberichte',
                'delete_others_einsatzberichte'
            );
            foreach (array_keys($roles) as $roleSlug) {
                $roleObject = get_role($roleSlug);
                foreach ($capabilities as $cap) {
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

        $categoryId = get_option('einsatzvw_category');
        if ($categoryId && category_exists($categoryId)) {
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
        add_option('einsatzverwaltung_reporttemplate');
        add_option('einsatzverwaltung_use_excerpttemplate', '0');
        add_option('einsatzverwaltung_excerpttemplate');

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
        global $wpdb;

        /**
         * From now on only pages should be stored in the fahrzeugpid term meta (as it used to be). All other post types
         * should utilize the new 'external URL' term meta.
         */
        $termIds = $wpdb->get_col("SELECT term_id FROM $wpdb->termmeta WHERE meta_key = 'fahrzeugpid' AND `meta_value` != '' AND term_id IN (SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = 'fahrzeug')");
        foreach ($termIds as $termId) {
            $postId = get_term_meta($termId, 'fahrzeugpid', true);
            if (empty($postId) || get_post_type($postId) === 'page') {
                // Either there is nothing to convert or it's a page (pages are the only post types to remain)
                continue;
            }

            // Move anything that is not a page to the 'external URL' field
            $permalink = get_permalink($postId);
            if ($permalink !== false) {
                update_term_meta($termId, 'vehicle_exturl', $permalink);
            }
            delete_term_meta($termId, 'fahrzeugpid');
        }

        /**
         * The current version number is written to the database on every page load and never used. Let's remove it.
         */
        delete_option('einsatzvw_version');

        update_option('einsatzvw_db_version', 50);
    }

    private function upgrade171()
    {
        global $wpdb;

        /*
         * Remove associations with nonexistant Units from Reports (and only from Reports)
         */
        $query = $wpdb->prepare(
            "DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value NOT IN (SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status = %s) AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = %s)",
            '_evw_unit',
            'evw_unit',
            'publish',
            'einsatz'
        );
        $wpdb->query($query);

        update_option('einsatzvw_db_version', 51);
    }

    /**
     * - Transforms the Unit custom post type to a taxonomy
     *
     * @since 1.8.0
     */
    public function upgrade180()
    {
        // Rewrite post_type to evw_legacy_unit
        global $wpdb;
        $query = $wpdb->prepare("UPDATE $wpdb->posts SET post_type = %s WHERE post_type = %s", 'evw_legacy_unit', 'evw_unit');
        $wpdb->query($query);

        $oldUnits = get_posts([
            'nopaging' => true,
            'post_type' => 'evw_legacy_unit',
            'post_status' => ['publish', 'private']
        ]);

        // If there are no units, there's nothing to do
        if (empty($oldUnits)) {
            update_option('einsatzvw_db_version', 60);
            return;
        }

        // Recreate units as terms
        $map = [];
        foreach ($oldUnits as $oldUnit) {
            $newUnit = wp_insert_term($oldUnit->post_title, 'evw_unit');
            if (is_wp_error($newUnit)) {
                error_log('Could not create term for Unit: ' . $newUnit->get_error_message());
                continue;
            }
            $termId = $newUnit['term_id'];
            add_term_meta($termId, 'unit_exturl', get_post_meta($oldUnit->ID, 'unit_exturl', true), true);
            add_term_meta($termId, 'unit_pid', get_post_meta($oldUnit->ID, 'unit_pid', true), true);
            add_term_meta($termId, 'old_unit_id', $oldUnit->ID, true);

            // Map old to new ID
            $map[$oldUnit->ID] = $termId;
        }

        // Schedule the initial run of the data migration job
        wp_schedule_single_event(time() + 30, 'einsatzverwaltung_migrate_units');

        // Update the Units IDs in the widgets if configured
        foreach (['einsatzverwaltung_widget', 'recent-incidents-formatted'] as $widgetId) {
            $optionName = "widget_$widgetId";
            $widgetConfigs = get_option($optionName);
            if (empty($widgetConfigs)) {
                continue;
            }
            $updatedConfigs = [];
            $modified = false;
            foreach ($widgetConfigs as $key => $config) {
                if (!is_array($config) || !array_key_exists('units', $config) || empty($config['units'])) {
                    // Transfer as-is
                    $updatedConfigs[$key] = $config;
                    continue;
                }

                $config['units'] = array_map(function ($unitId) use ($map) {
                    return array_key_exists($unitId, $map) ? $map[$unitId] : $unitId;
                }, $config['units']);
                $updatedConfigs[$key] = $config;
                $modified = true;
            }
            if ($modified) {
                update_option($optionName, $updatedConfigs);
            }
        }

        update_option('einsatzvw_db_version', 60);
    }

    /**
     * - Adds missing default value for incicent report category setting
     * - Adds new user roles
     * - Adds role with full permission to users which had those rights before
     *
     * @since 1.10.0
     */
    public function upgrade1100()
    {
        add_option('einsatzvw_category', '-1');

        // Get roles and check if they were allowed to edit reports before
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
        }
        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $roleSlug) {
                // The administrator role can be skipped
                if ('administrator' === $roleSlug) {
                    continue;
                }

                if (get_option("einsatzvw_cap_roles_{$roleSlug}", '0') === '1') {
                    // Check for users with this previously allowed role and give them the new editor role as well
                    $users = get_users(['role__in' => [$roleSlug]]); /** @var WP_User[] $users */
                    foreach ($users as $user) {
                        $user->add_role('einsatzverwaltung_reports_editor');
                    }
                }
            }
        }

        // Set option that the user roles should be updated on the next init hook
        update_option(UserRightsManager::ROLE_UPDATE_OPTION, '1');

        update_option('einsatzvw_db_version', 71);
    }

    /**
     * - Adds missing 'special' postmeta for reports that got created via the API
     *
     * @since 1.10.2
     */
    public function upgrade1102()
    {
        $postsWithoutPostmeta = get_posts([
            'post_type' => 'einsatz',
            'post_status' => ['publish', 'private', 'draft'],
            'nopaging' => true,
            'meta_query' => [
                [
                    'key' => 'einsatz_special',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);
        foreach ($postsWithoutPostmeta as $post) {
            add_post_meta($post->ID, 'einsatz_special', 0, true);
        }
        update_option('einsatzvw_db_version', 72);
    }

    /**
     * @since 1.11.1
     */
    public function upgrade1111()
    {
        add_option('einsatzvw_disable_fontawesome', '0');

        update_option('einsatzvw_db_version', 80);
    }

    /**
     * Fügt einen Bezeichner für eine Admin Notice der Liste der noch anzuzeigenden Notices hinzu
     *
     * @param string $slug Bezeichner für die Notice
     */
    private function addAdminNotice(string $slug)
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
