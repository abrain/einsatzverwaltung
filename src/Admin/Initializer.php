<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Export\Tool as ExportTool;
use abrain\Einsatzverwaltung\Import\Tool as ImportTool;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Settings\MainPage;
use abrain\Einsatzverwaltung\Utilities;

/**
 * Bootstraps and registers all the things we can do in WordPress' admin area
 * @package abrain\Einsatzverwaltung\Admin
 */
class Initializer
{
    /**
     * Initializer constructor.
     * @param Data $data
     * @param Options $options
     * @param Utilities $utilities
     */
    public function __construct(Data $data, Options $options, Utilities $utilities)
    {
        $pluginBasename = plugin_basename(einsatzverwaltung_plugin_file());
        add_action('admin_menu', array($this, 'hideTaxonomies'));
        add_action('admin_notices', array($this, 'displayAdminNotices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueEditScripts'));
        add_filter('dashboard_glance_items', array($this, 'addReportsToDashboard'));
        add_filter('plugin_row_meta', array($this, 'pluginMetaLinks'), 10, 2);
        add_filter("plugin_action_links_{$pluginBasename}", array($this,'addActionLinks'));

        $admin = new Admin();
        add_action('add_meta_boxes_einsatz', array($admin, 'addMetaBoxes'));
        add_filter('manage_edit-einsatz_columns', array($admin, 'filterColumnsEinsatz'));
        add_action('manage_einsatz_posts_custom_column', array($admin, 'filterColumnContentEinsatz'), 10, 2);

        // Register Settings
        $mainPage = new MainPage($options);
        add_action('admin_menu', array($mainPage, 'addToSettingsMenu'));
        add_action('admin_init', array($mainPage, 'registerSettings'));

        $importTool = new ImportTool($utilities, $data);
        add_action('admin_menu', array($importTool, 'addToolToMenu'));

        $exportTool = new ExportTool();
        add_action('admin_menu', array($exportTool, 'addToolToMenu'));
        add_action('init', array($exportTool, 'startExport'), 20); // 20, damit alles andere initialisiert ist
        add_action('admin_enqueue_scripts', array($exportTool, 'enqueueAdminScripts'));

        $tasksPage = new TasksPage($utilities, $data);
        add_action('admin_menu', array($tasksPage, 'registerPage'));
        add_action('admin_menu', array($tasksPage, 'hidePage'), 999);
    }

    /**
     * Hides auto-generated UI for the WordPress core taxonomies 'category' and 'post_tag', we only want to use them
     * under the hood
     */
    public function hideTaxonomies()
    {
        // Hide meta box for category selection when editing reports
        remove_meta_box('categorydiv', 'einsatz', 'side');

        // Hide the submenu item to edit categories (still exists for posts)
        remove_submenu_page(
            'edit.php?post_type=einsatz',
            'edit-tags.php?taxonomy=category&amp;post_type=einsatz'
        );
    }

    /**
     * Zusätzliche Skripte im Admin-Bereich einbinden
     *
     * @param string $hook Name der aufgerufenen Datei
     */
    public function enqueueEditScripts($hook)
    {
        if ('post.php' == $hook || 'post-new.php' == $hook) {
            // Nur auf der Bearbeitungsseite anzeigen
            wp_enqueue_script(
                'einsatzverwaltung-edit-script',
                Core::$scriptUrl . 'einsatzverwaltung-edit.js',
                array('jquery', 'jquery-ui-autocomplete'),
                Core::VERSION
            );
            wp_enqueue_style(
                'einsatzverwaltung-edit',
                Core::$styleUrl . 'style-edit.css',
                array(),
                Core::VERSION
            );
        } elseif ('settings_page_einsatzvw-settings' == $hook) {
            wp_enqueue_script(
                'einsatzverwaltung-settings-script',
                Core::$scriptUrl . 'einsatzverwaltung-settings.js',
                array('jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable'),
                Core::VERSION
            );
        }

        wp_enqueue_style(
            'font-awesome',
            Core::$pluginUrl . 'font-awesome/css/font-awesome.min.css',
            false,
            '4.7.0'
        );
        wp_enqueue_style(
            'einsatzverwaltung-admin',
            Core::$styleUrl . 'style-admin.css',
            array(),
            Core::VERSION
        );
        wp_enqueue_script(
            'einsatzverwaltung-admin-script',
            Core::$scriptUrl . 'einsatzverwaltung-admin.js',
            array('wp-color-picker'),
            Core::VERSION
        );
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Zahl der Einsatzberichte im Dashboard anzeigen
     *
     * @param array $items
     *
     * @return array
     */
    public function addReportsToDashboard($items)
    {
        $postType = 'einsatz';
        if (post_type_exists($postType)) {
            $postCounts = wp_count_posts($postType);
            $text = sprintf(
                _n('%d Report', '%d Reports', intval($postCounts->publish), 'einsatzverwaltung'),
                number_format_i18n($postCounts->publish)
            );
            $postTypeObject = get_post_type_object($postType);
            $class = "$postType-count";
            if (current_user_can($postTypeObject->cap->edit_posts)) {
                $items[] = sprintf(
                    '<a class="%s" href="%s">%s</a>',
                    esc_attr($class),
                    esc_attr('edit.php?post_type=' . $postType),
                    esc_html($text)
                );
            } else {
                $items[] = sprintf('<span class="%s">%s</span>', esc_attr($class), esc_html($text));
            }
        }

        return $items;
    }

    /**
     * Fügt weiterführende Links in der Pluginliste ein
     *
     * @param array $links Liste mit Standardlinks von WordPress
     * @param string $file Name der Plugindatei
     * @return array Vervollständigte Liste mit Links
     */
    public function pluginMetaLinks($links, $file)
    {
        if (Core::$pluginBasename === $file) {
            $links[] = '<a href="https://einsatzverwaltung.abrain.de/feed/">Newsfeed</a>';
        }

        return $links;
    }

    /**
     * Zeigt einen Link zu den Einstellungen direkt auf der Plugin-Seite an
     *
     * @param $links
     *
     * @return array
     */
    public function addActionLinks($links)
    {
        $settingsPage = 'options-general.php?page=' . MainPage::EVW_SETTINGS_SLUG;
        $actionLinks = array('<a href="' . admin_url($settingsPage) . '">Einstellungen</a>');
        return array_merge($links, $actionLinks);
    }

    public function displayAdminNotices()
    {
        // Keine Notices auf der TaskPage anzeigen
        $currentScreen = get_current_screen();
        if ('tools_page_einsatzverwaltung-tasks' === $currentScreen->id) {
            return;
        }

        $notices = get_option('einsatzverwaltung_admin_notices');

        if (empty($notices) || !is_array($notices)) {
            return;
        }

        if (in_array('regenerateSlugs', $notices)) {
            $url = admin_url('tools.php?page=' . TasksPage::PAGE_SLUG . '&action=regenerate-slugs');
            echo '<div class="notice notice-info"><p>Die Links zu den einzelnen Einsatzberichten ';
            echo 'werden ab jetzt aus dem Berichtstitel generiert (wie bei gew&ouml;hnlichen WordPress-Beitr&auml;gen)';
            echo ' und nicht mehr aus der Einsatznummer. Dazu ist eine Anpassung der bestehenden Berichte ';
            echo 'notwendig. Die alten Links mit der Einsatznummer funktionieren f&uuml;r die bisherigen Berichte auch';
            echo ' nach der Anpassung, k&uuml;nftige Berichte erhalten nur noch den neuen Link.<br>';
            echo '<strong>Die Anpassung kann durchaus eine Minute dauern, bitte nur einmal klicken.</strong><br>';
            echo '<a href="' . $url . '" class="button button-primary">Anpassung durchf&uuml;hren</a></p></div>';
        }
    }
}
