<?php

namespace abrain\Einsatzverwaltung\Admin;

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
        $admin = new Admin();
        add_action('add_meta_boxes_einsatz', array($admin, 'addMetaBoxes'));
        add_action('admin_menu', array($admin, 'adjustTaxonomies'));
        add_action('admin_notices', array($admin, 'displayAdminNotices'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueueEditScripts'));
        add_filter('manage_edit-einsatz_columns', array($admin, 'filterColumnsEinsatz'));
        add_action('manage_einsatz_posts_custom_column', array($admin, 'filterColumnContentEinsatz'), 10, 2);
        add_action('dashboard_glance_items', array($admin, 'addEinsatzberichteToDashboard')); // since WP 3.8
        add_filter('plugin_row_meta', array($admin, 'pluginMetaLinks'), 10, 2);
        add_filter(
            'plugin_action_links_' . plugin_basename(einsatzverwaltung_plugin_file()),
            array($admin,'addActionLinks')
        );

        // Register Settings
        $mainPage = new MainPage($options);
        add_action('admin_menu', array($mainPage, 'addToSettingsMenu'));
        add_action('admin_init', array($mainPage, 'registerSettings'));

        $importTool = new ImportTool($utilities, $options, $data);
        add_action('admin_menu', array($importTool, 'addToolToMenu'));

        $exportTool = new ExportTool();
        add_action('admin_menu', array($exportTool, 'addToolToMenu'));
        add_action('init', array($exportTool, 'startExport'), 20); // 20, damit alles andere initialisiert ist
        add_action('admin_enqueue_scripts', array($exportTool, 'enqueueAdminScripts'));

        $tasksPage = new TasksPage($utilities, $data);
        add_action('admin_menu', array($tasksPage, 'registerPage'));
        add_action('admin_menu', array($tasksPage, 'hidePage'), 999);
    }
}
