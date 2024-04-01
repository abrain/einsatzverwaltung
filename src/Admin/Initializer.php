<?php

namespace abrain\Einsatzverwaltung\Admin;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Data;
use abrain\Einsatzverwaltung\Export\Tool as ExportTool;
use abrain\Einsatzverwaltung\Import\Tool as ImportTool;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\Settings\MainPage;
use abrain\Einsatzverwaltung\Types\Report;
use abrain\Einsatzverwaltung\Utilities;
use function add_filter;
use function esc_html__;
use function sprintf;
use function wp_enqueue_style;

/**
 * Bootstraps and registers all the things we can do in WordPress' admin area
 * @package abrain\Einsatzverwaltung\Admin
 */
class Initializer
{
    /**
     * Initializer constructor.
     *
     * @param Data $data
     * @param Options $options
     * @param Utilities $utilities
     * @param PermalinkController $permalinkController
     */
    public function __construct(Data $data, Options $options, Utilities $utilities, PermalinkController $permalinkController)
    {
        $pluginBasename = Core::$pluginBasename;
        add_action('admin_menu', array($this, 'hideTaxonomies'));
        add_action('admin_notices', array($this, 'displayAdminNotices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueEditScripts'));
        add_filter('dashboard_glance_items', array($this, 'addReportsToDashboard'));
        add_filter('plugin_row_meta', array($this, 'pluginMetaLinks'), 10, 2);
        add_filter("plugin_action_links_{$pluginBasename}", array($this,'addActionLinks'));
        add_filter('use_block_editor_for_post_type', array($this, 'useBlockEditorForReports'), 10, 2);

        $reportListTable = new ReportListTable();
        add_filter('manage_edit-einsatz_columns', array($reportListTable, 'filterColumnsEinsatz'));
        add_action('manage_einsatz_posts_custom_column', array($reportListTable, 'filterColumnContentEinsatz'), 10, 2);
        add_action('quick_edit_custom_box', array($reportListTable, 'quickEditCustomBox'), 10, 3);
        add_action('bulk_edit_custom_box', array($reportListTable, 'bulkEditCustomBox'), 10, 2);

        $reportEditScreen = new ReportEditScreen();
        add_action('add_meta_boxes_einsatz', array($reportEditScreen, 'addMetaBoxes'));
        add_filter('default_hidden_meta_boxes', array($reportEditScreen, 'filterDefaultHiddenMetaboxes'), 10, 2);
        add_filter('wp_dropdown_cats', array($reportEditScreen, 'filterIncidentCategoryDropdown'), 10, 2);

        // Register Settings
        $mainPage = new MainPage($options, $permalinkController);
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
                array('jquery', 'jquery-ui-autocomplete', 'wp-i18n'),
                Core::VERSION
            );
            wp_localize_script(
                'einsatzverwaltung-edit-script',
                'einsatzverwaltung_ajax_object',
                array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('einsatzverwaltung_used_values'))
            );
            wp_set_script_translations('einsatzverwaltung-edit-script', 'einsatzverwaltung');
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
            'einsatzverwaltung-font-awesome',
            Core::$pluginUrl . 'font-awesome/css/fontawesome.min.css',
            false,
            '6.2.1'
        );
        wp_enqueue_style(
            'einsatzverwaltung-font-awesome-solid',
            Core::$pluginUrl . 'font-awesome/css/solid.min.css',
            array('einsatzverwaltung-font-awesome'),
            '6.2.1'
        );
        wp_enqueue_style(
            'einsatzverwaltung-font-awesome-brands',
            Core::$pluginUrl . 'font-awesome/css/brands.min.css',
            array('einsatzverwaltung-font-awesome'),
            '6.2.1'
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
    public function addReportsToDashboard($items): array
    {
        $postType = 'einsatz';
        if (post_type_exists($postType)) {
            $postCounts = wp_count_posts($postType);
            $text = sprintf(
                // translators: 1: number of reports
                _n('%d Incident Report', '%d Incident Reports', intval($postCounts->publish), 'einsatzverwaltung'),
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
    public function pluginMetaLinks($links, $file): array
    {
        if (Core::$pluginBasename === $file) {
            $links[] = sprintf(
                '<a href="%1$s" target="_blank">%2$s</a>',
                'https://www.paypal.com/donate?hosted_button_id=U7LCWUZ8E54JG',
                esc_html__('Donate', 'einsatzverwaltung')
            );
            $links[] = sprintf(
                '<a href="%1$s">%2$s</a>',
                admin_url('options-general.php?page=' . MainPage::EVW_SETTINGS_SLUG . '&tab=about'),
                esc_html__('Support & Links', 'einsatzverwaltung')
            );
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
    public function addActionLinks($links): array
    {
        $settingsPage = 'options-general.php?page=' . MainPage::EVW_SETTINGS_SLUG;
        $actionLinks = [
            sprintf('<a href="%s">%s</a>', admin_url($settingsPage), esc_html__('Settings', 'einsatzverwaltung'))
        ];
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

    /**
     * @param bool $useBlockEditor
     * @param string $postType
     *
     * @return bool
     */
    public function useBlockEditorForReports($useBlockEditor, $postType): bool
    {
        if ($postType === Report::getSlug() && get_option('einsatz_disable_blockeditor', '0') === '1') {
            return false;
        }

        return $useBlockEditor;
    }
}
