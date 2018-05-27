<?php

namespace abrain\Einsatzverwaltung\Settings;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Options;

/**
 * Entry point for the plugin settings
 *
 * @package abrain\Einsatzverwaltung\Settings
 */
class MainPage
{
    const EVW_SETTINGS_SLUG = 'einsatzvw-settings';

    /**
     * @var Options
     */
    private $options;

    /**
     * MainPage constructor.
     * @param Options $options
     */
    public function __construct(Options $options)
    {
        $this->options = $options;
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
        $rewriteSlug = $this->options->getRewriteSlug();
        $conflictingPage = get_page_by_path($rewriteSlug);
        if ($conflictingPage instanceof \WP_Post) {
            $pageEditLink = '<a href="' . get_edit_post_link($conflictingPage->ID) . '">' . $conflictingPage->post_title . '</a>';
            $message = sprintf('Die Seite %s und das Archiv der Einsatzberichte haben einen identischen Permalink (%s). &Auml;ndere einen der beiden Permalinks, um beide Seiten erreichen zu k&ouml;nnen.', $pageEditLink, $rewriteSlug);
            echo '<div class="error"><p>' . $message . '</p></div>';
        }

        $tabs = array(
            'general' => 'Allgemein',
            'numbers' => 'Einsatznummern',
            'report' => 'Einsatzberichte',
            'list' => 'Einsatzliste',
            'capabilities' => 'Berechtigungen',
            'about' => '&Uuml;ber',
        );

        $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH;
        $currentTab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING, $flags);

        if (empty($currentTab) || !array_key_exists($currentTab, $tabs)) {
            $tabIds = array_keys($tabs);
            $currentTab = $tabIds[0]; // NEEDS_PHP5.4 array_keys($tabs)[0]
        }

        echo "<h2 class=\"nav-tab-wrapper\">";
        foreach ($tabs as $identifier => $label) {
            $class = $currentTab === $identifier ? "nav-tab nav-tab-active" : "nav-tab";
            printf(
                '<a href="?page=%s&tab=%s" class="%s">%s</a>',
                self::EVW_SETTINGS_SLUG,
                $identifier,
                $class,
                $label
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
}
