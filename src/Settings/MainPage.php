<?php

namespace abrain\Einsatzverwaltung\Settings;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\Settings\Pages\About;
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
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->subPages = array();

        require_once dirname(__FILE__) . '/Pages/SubPage.php';
        require_once dirname(__FILE__) . '/Pages/General.php';
        require_once dirname(__FILE__) . '/Pages/Numbers.php';
        require_once dirname(__FILE__) . '/Pages/Report.php';
        require_once dirname(__FILE__) . '/Pages/ReportList.php';
        require_once dirname(__FILE__) . '/Pages/Capabilities.php';
        require_once dirname(__FILE__) . '/Pages/About.php';

        SubPage::$options = $options;
        $this->subPages[] = new General();
        $this->subPages[] = new Numbers();
        $this->subPages[] = new Report();
        $this->subPages[] = new ReportList();
        $this->subPages[] = new Capabilities();
        $this->subPages[] = new About();
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

        $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH;
        $currentTab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING, $flags);

        if (empty($currentTab) || !$this->subPageExists($currentTab)) {
            $currentTab = $this->subPages[0]->identifier;
        }

        echo "<h2 class=\"nav-tab-wrapper\">";
        foreach ($this->subPages as $subPage) {
            $class = $currentTab === $subPage->identifier ? "nav-tab nav-tab-active" : "nav-tab";
            printf(
                '<a href="?page=%s&tab=%s" class="%s">%s</a>',
                self::EVW_SETTINGS_SLUG,
                $subPage->identifier,
                $class,
                $subPage->title
            );
        }
        echo "</h2>";

        if ('about' === $currentTab) {
            ?>
            <div class="aboutpage-icons">
                <div class="aboutpage-icon"><a href="https://einsatzverwaltung.abrain.de" target="_blank"><i class="fa fa-globe fa-4x"></i><br>Webseite</a></div>
                <div class="aboutpage-icon"><a href="https://einsatzverwaltung.abrain.de/dokumentation/" target="_blank"><i class="fa fa-book fa-4x"></i><br>Dokumentation</a></div>
                <div class="aboutpage-icon"><a href="https://github.com/abrain/einsatzverwaltung" target="_blank"><i class="fa fa-github fa-4x"></i><br>GitHub</a></div>
                <div class="aboutpage-icon"><a href="https://de.wordpress.org/plugins/einsatzverwaltung/" target="_blank"><i class="fa fa-wordpress fa-4x"></i><br>Plugin-Verzeichnis</a></div>
            </div>

            <h2>Support</h2>
            <p>Solltest Du ein Problem bei der Benutzung des Plugins haben, schaue bitte erst auf <a href="https://github.com/abrain/einsatzverwaltung/issues">GitHub</a> und im <a href="https://wordpress.org/support/plugin/einsatzverwaltung">Forum auf wordpress.org</a> nach, ob andere das Problem auch haben bzw. hatten. Wenn es noch keinen passenden Eintrag gibt, lege bitte einen entsprechenden Issue bzw. Thread an. Du kannst aber auch einfach eine <a href="mailto:kontakt@abrain.de">E-Mail</a> schreiben.</p>
            <p>Gib bitte immer die folgenden Versionen mit an:&nbsp;<code style="border: 1px solid grey;">
            <?php printf('Plugin: %s, WordPress: %s, PHP: %s', Core::VERSION, get_bloginfo('version'), phpversion()); ?>
            </code></p>

            <h2>Social Media</h2>
            <ul>
                <li>Twitter: <a href="https://twitter.com/einsatzvw" title="Einsatzverwaltung auf Twitter">@einsatzvw</a></li>
                <li>Mastodon: <a href="https://chaos.social/@einsatzverwaltung" title="Einsatzverwaltung im Fediverse">@einsatzverwaltung</a></li>
                <li>Facebook: <a href="https://www.facebook.com/einsatzverwaltung/" title="Einsatzverwaltung auf Facebook">Einsatzverwaltung</a></li>
            </ul>
            <p>Du kannst die Neuigkeiten auch mit deinem Feedreader abonnieren: <a href="https://einsatzverwaltung.abrain.de/feed/">RSS</a> / <a href="https://einsatzverwaltung.abrain.de/feed/atom/">Atom</a></p>
            <?php
            return;
        }

        // Einstellungen ausgeben
        echo '<form method="post" action="options.php">';
        settings_fields('einsatzvw_settings_' . $currentTab);
        do_settings_sections(self::EVW_SETTINGS_SLUG . '-' . $currentTab);
        submit_button();
        echo '</form>';
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

    /**
     * @param string $slug
     * @return bool
     */
    private function subPageExists($slug)
    {
        if (empty($slug)) {
            return false;
        }

        foreach ($this->subPages as $subPage) {
            if ($subPage->identifier === $slug) {
                return true;
            }
        }

        return false;
    }
}
