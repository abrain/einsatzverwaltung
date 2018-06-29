<?php

namespace abrain\Einsatzverwaltung\Settings\Pages;

use abrain\Einsatzverwaltung\Core;

/**
 * About page
 *
 * @package abrain\Einsatzverwaltung\Settings\Pages
 */
class About extends SubPage
{
    public function __construct()
    {
        parent::__construct('about', '&Uuml;ber');
    }

    public function addSettingsFields()
    {
        return;
    }

    public function addSettingsSections()
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function echoStaticContent()
    {
        ?>
        <div class="aboutpage-icons">
            <div class="aboutpage-icon"><a href="https://einsatzverwaltung.abrain.de" target="_blank"><i class="fa fa-globe fa-4x"></i><br>Webseite</a></div>
            <div class="aboutpage-icon"><a href="https://einsatzverwaltung.abrain.de/dokumentation/" target="_blank"><i class="fa fa-book fa-4x"></i><br>Dokumentation</a></div>
            <div class="aboutpage-icon"><a href="https://github.com/abrain/einsatzverwaltung" target="_blank"><i class="fa fa-github fa-4x"></i><br>GitHub</a></div>
            <div class="aboutpage-icon"><a href="https://de.wordpress.org/plugins/einsatzverwaltung/" target="_blank"><i class="fa fa-wordpress fa-4x"></i><br>Plugin-Verzeichnis</a></div>
        </div>

        <h2>Support</h2>
        <p>Solltest Du ein Problem bei der Benutzung des Plugins haben, schaue bitte erst auf <a href="https://github.com/abrain/einsatzverwaltung/issues">GitHub</a> und im <a href="https://wordpress.org/support/plugin/einsatzverwaltung">Support-Forum auf wordpress.org</a> nach, ob andere das Problem auch haben bzw. hatten. Wenn es noch keinen passenden Eintrag gibt, lege bitte einen entsprechenden Issue bzw. Thread an. Du kannst aber auch einfach eine <a href="mailto:kontakt@abrain.de">E-Mail</a> schreiben.</p>
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
    }

    /**
     * @inheritDoc
     */
    public function hasForm()
    {
        return false;
    }

    public function registerSettings()
    {
        return;
    }
}
