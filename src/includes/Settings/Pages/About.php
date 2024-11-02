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
        parent::__construct('about', __('About', 'einsatzverwaltung'));
    }

    public function addSettingsFields()
    {
    }

    public function addSettingsSections()
    {
    }

    /**
     * @inheritDoc
     */
    public function echoStaticContent()
    {
        ?>
        <div class="aboutpage-icons">
            <div class="aboutpage-icon"><a href="https://einsatzverwaltung.org" target="_blank"><i class="fa-solid fa-globe fa-4x"></i><br>Webseite</a></div>
            <div class="aboutpage-icon"><a href="https://einsatzverwaltung.org/dokumentation/" target="_blank"><i class="fa-solid fa-book fa-4x"></i><br>Dokumentation</a></div>
            <div class="aboutpage-icon"><a href="https://wordpress.org/support/plugin/einsatzverwaltung/" target="_blank"><i class="fa-solid fa-circle-question fa-4x"></i><br>Support-Forum</a></div>
            <div class="aboutpage-icon"><a href="https://github.com/abrain/einsatzverwaltung" target="_blank"><i class="fa-brands fa-github fa-4x"></i><br>GitHub</a></div>
            <div class="aboutpage-icon"><a href="https://de.wordpress.org/plugins/einsatzverwaltung/" target="_blank"><i class="fa-brands fa-wordpress fa-4x"></i><br>Plugin-Verzeichnis</a></div>
        </div>

        <h2>Support</h2>
        <p>Solltest Du Fragen zur Benutzung des Plugins haben, schaue in die <a href="https://einsatzverwaltung.org/faq/">FAQ</a> und ins <a href="https://wordpress.org/support/plugin/einsatzverwaltung">Support-Forum</a>. Es kann sein, dass die Frage dort schon einmal gel&ouml;st wurde. Findest Du nichts zu dem Thema, erstelle einen neuen Thread im Forum.</p>
        <p>
            Wenn Du einen Fehler melden oder eine Verbesserung vorschlagen m&ouml;chtest, ist <a href="https://github.com/abrain/einsatzverwaltung/issues">GitHub</a> der beste Ort daf&uuml;r. Du kannst mir aber auch eine <a href="mailto:support@einsatzverwaltung.org">E-Mail</a> schreiben.
        </p>
        <p>
            Bei Problembeschreibungen helfen mir die folgenden Angaben bei der Eingrenzung der Ursache:
            <code>
                <?php printf('Plugin: %s, WordPress: %s, PHP: %s', Core::VERSION, get_bloginfo('version'), phpversion()); ?>
            </code>
        </p>

        <h2>Spenden</h2>
        <p>Unterst&uuml;tze die Weiterentwicklung des Plugins mit einer Spende.</p>
        <a class="button" href="https://www.paypal.com/donate?hosted_button_id=U7LCWUZ8E54JG" target="_blank">
            <i class="fa-brands fa-paypal"></i>
            Spende via PayPal
        </a>
        <a class="button" href="https://einsatzverwaltung.org/unterstuetzen/" target="_blank">
            <i class="fa-solid fa-piggy-bank"></i>
            Weitere Optionen
        </a>

        <h2>Social Media</h2>
        <ul>
            <li>Twitter: <a href="https://twitter.com/einsatzvw" title="Einsatzverwaltung auf Twitter">@einsatzvw</a></li>
            <li>Mastodon: <a href="https://chaos.social/@einsatzverwaltung" title="Einsatzverwaltung im Fediverse">@einsatzverwaltung</a></li>
        </ul>
        <p>Du kannst die Neuigkeiten auch mit deinem Feedreader abonnieren: <a href="https://einsatzverwaltung.org/feed/">RSS</a> / <a href="https://einsatzverwaltung.org/feed/atom/">Atom</a></p>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function hasForm(): bool
    {
        return false;
    }

    public function registerSettings()
    {
    }
}
