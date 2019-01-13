<?php

namespace abrain\Einsatzverwaltung\Settings;

use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\Settings\Pages\About;
use abrain\Einsatzverwaltung\Settings\Pages\Advanced;
use abrain\Einsatzverwaltung\Settings\Pages\Capabilities;
use abrain\Einsatzverwaltung\Settings\Pages\General;
use abrain\Einsatzverwaltung\Settings\Pages\Numbers;
use abrain\Einsatzverwaltung\Settings\Pages\Report;
use abrain\Einsatzverwaltung\Settings\Pages\ReportList;
use abrain\Einsatzverwaltung\Settings\Pages\SubPage;

/**
 * Entry point for the plugin settings
 *
 * @package abrain\Einsatzverwaltung\Settings
 */
class MainPage
{
    const EVW_SETTINGS_SLUG = 'einsatzvw-settings';

    /**
     * @var SubPage[]
     */
    private $subPages;

    /**
     * MainPage constructor.
     *
     * @param Options $options
     * @param PermalinkController $permalinkController
     */
    public function __construct(Options $options, PermalinkController $permalinkController)
    {
        $this->subPages = array();

        SubPage::$options = $options;
        $this->addSubPage(new General());
        $this->addSubPage(new Numbers());
        $this->addSubPage(new Report());
        $this->addSubPage(new ReportList());
        $this->addSubPage(new Capabilities());
        $this->addSubPage(new Advanced($permalinkController));
        $this->addSubPage(new About());
    }

    /**
     * Fügt der Einstellungsseite eine Unterseite hinzu
     * @param SubPage $subPage
     */
    private function addSubPage(SubPage $subPage)
    {
        $this->subPages[$subPage->identifier] = $subPage;
    }

    /**
     * Fügt die Einstellungsseite zum Menü hinzu
     */
    public function addToSettingsMenu()
    {
        add_options_page(
            'Einstellungen',
            'Einsatzverwaltung',
            'manage_options',
            self::EVW_SETTINGS_SLUG,
            array($this, 'echoSettingsPage')
        );
    }

    /**
     * Generiert den Inhalt der Einstellungsseite
     */
    public function echoSettingsPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to manage options for this site.'));
        }
        ?>

        <div class="wrap">
        <h1>Einstellungen &rsaquo; Einsatzverwaltung</h1>

        <?php
        // Prüfen, ob Rewrite Slug von einer Seite genutzt wird
        $rewriteSlug = sanitize_title(get_option('einsatzvw_rewrite_slug'), 'einsatzberichte');
        $conflictingPage = get_page_by_path($rewriteSlug);
        if ($conflictingPage instanceof \WP_Post) {
            $pageEditLink = '<a href="' . get_edit_post_link($conflictingPage->ID) . '">' . $conflictingPage->post_title . '</a>';
            $message = sprintf('Die Seite %s und das Archiv der Einsatzberichte haben einen identischen Permalink (%s). &Auml;ndere einen der beiden Permalinks, um beide Seiten erreichen zu k&ouml;nnen.', $pageEditLink, $rewriteSlug);
            echo '<div class="error"><p>' . $message . '</p></div>';
        }

        $currentSubPage = $this->getCurrentSubPage();
        if (empty($currentSubPage)) {
            return;
        }

        echo "<h2 class=\"nav-tab-wrapper\">";
        foreach ($this->subPages as $subPage) {
            printf(
                '<a href="?page=%s&tab=%s" class="%s">%s</a>',
                self::EVW_SETTINGS_SLUG,
                $subPage->identifier,
                $currentSubPage === $subPage ? "nav-tab nav-tab-active" : "nav-tab",
                $subPage->title
            );
        }
        echo "</h2>";

        $currentSubPage->beforeContent();
        $currentSubPage->echoStaticContent();

        // Einstellungen ausgeben
        if ($currentSubPage->hasForm()) {
            echo '<form method="post" action="options.php">';
            settings_fields('einsatzvw_settings_' . $currentSubPage->identifier);
            do_settings_sections(self::EVW_SETTINGS_SLUG . '-' . $currentSubPage->identifier);
            submit_button();
            echo '</form>';
        }
    }

    /**
     * @return SubPage
     */
    private function getCurrentSubPage()
    {
        $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH;
        $tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING, $flags);

        if (empty($tab) || !array_key_exists($tab, $this->subPages)) {
            $subPageObjects = array_values($this->subPages);
            return $subPageObjects[0];
        }

        return $this->subPages[$tab];
    }

    /**
     * Macht Einstellungen im System bekannt und regelt die Zugehörigkeit zu Abschnitten auf Einstellungsseiten
     */
    public function registerSettings()
    {
        // NEEDS_WP4.7 Standardwerte in register_setting() mitgeben
        foreach ($this->subPages as $subPage) {
            $subPage->addSettingsSections();
            $subPage->addSettingsFields();
            $subPage->registerSettings();
        }
    }
}
