<?php
namespace abrain\Einsatzverwaltung;

use WP_Error;
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
     *
     * @return void|WP_Error
     */
    public function doUpdate($currentDbVersion, $targetDbVersion)
    {
        if (empty($targetDbVersion)) {
            return new WP_Error('', 'Zieldatenbankversion darf nicht leer sein');
        }

        // Kein Timeout während des Updates
        set_time_limit(0);

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
    }

    /**
     * GMT-Datum wurde nicht gespeichert EVW-58
     */
    private function upgrade054()
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

        update_option('einsatzvw_db_version', 1);
    }

    private function upgrade070()
    {
        update_option('einsatzvw_cap_roles_administrator', 1);
        $roleObject = get_role('administrator');
        foreach ($this->core->getCapabilities() as $cap) {
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
                foreach ($this->core->getCapabilities() as $cap) {
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
        $this->options->setFlushRewriteRules(true);
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
        // Alle veröffentlichten Einsatzberichte einer Kategorie hinzufügen, wenn diese in den Einstellungen für die
        // Einsatzberichte gesetzt wurde
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

        // Aktualisiert sämtliche laufenden Nummern der Einsatzberichte
        $this->data->updateSequenceNumbers();

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
