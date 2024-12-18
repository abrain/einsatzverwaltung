<?php

namespace abrain\Einsatzverwaltung\Settings;

use abrain\Einsatzverwaltung\Options;
use abrain\Einsatzverwaltung\PermalinkController;
use abrain\Einsatzverwaltung\Settings\Pages\About;
use abrain\Einsatzverwaltung\Settings\Pages\Advanced;
use abrain\Einsatzverwaltung\Settings\Pages\General;
use abrain\Einsatzverwaltung\Settings\Pages\Numbers;
use abrain\Einsatzverwaltung\Settings\Pages\Report;
use abrain\Einsatzverwaltung\Settings\Pages\ReportList;
use abrain\Einsatzverwaltung\Settings\Pages\SubPage;
use abrain\Einsatzverwaltung\Types\Report as ReportType;
use WP_Post;
use function esc_html;
use function esc_html__;
use function esc_url;
use function get_page_by_path;
use function get_permalink;
use function get_post_type_archive_link;
use function home_url;
use function str_replace;
use function strpos;
use function wp_parse_url;
use const PHP_URL_PATH;

/**
 * Entry point for the plugin settings
 *
 * @package abrain\Einsatzverwaltung\Settings
 */
class MainPage
{
    const EVW_SETTINGS_SLUG = 'einsatzvw-settings';

    /**
     * @var PermalinkController
     */
    private $permalinkController;

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
        SubPage::$options = $options;
        $this->permalinkController = $permalinkController;

        $this->subPages = array();
    }

    public function addHooks()
    {
        add_action('admin_menu', array($this, 'addToSettingsMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
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
            __('Settings', 'einsatzverwaltung'),
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
            wp_die(esc_html__('You do not have sufficient permissions to manage options for this site.', 'einsatzverwaltung'));
        }

        $heading = sprintf('%s &rsaquo; Einsatzverwaltung', __('Settings', 'einsatzverwaltung'));
        echo '<div class="wrap"><h1>' . esc_html($heading) . '</h1>';

        // Check if any page uses the same permalink as the archive
        $conflictingPage = $this->getConflictingPage();
        if ($conflictingPage instanceof WP_Post) {
            $message = sprintf(
                // translators: 1: title of the page, 2: page ID, 3: URL
                __('The page "%1$s" uses the same permalink as the archive (%2$s). Please change the permalink of the page.', 'einsatzverwaltung'),
                $conflictingPage->post_title,
                get_permalink($conflictingPage)
            );
            printf('<div class="error"><p>%s</p></div>', esc_html($message));
        }

        $currentSubPage = $this->getCurrentSubPage();
        if (empty($currentSubPage)) {
            return;
        }

        printf(
            '<nav class="nav-tab-wrapper wp-clearfix" aria-label="%s">',
            esc_attr__('Secondary menu', 'einsatzverwaltung')
        );
        foreach ($this->subPages as $subPage) {
            if ($this->isCurrentSubPage($subPage)) {
                printf(
                    '<a href="%s" class="%s" aria-current="page">%s</a>',
                    esc_url(sprintf("?page=%s&tab=%s", self::EVW_SETTINGS_SLUG, $subPage->identifier)),
                    "nav-tab nav-tab-active",
                    esc_html($subPage->title)
                );
            } else {
                printf(
                    '<a href="%s" class="%s">%s</a>',
                    esc_url(sprintf("?page=%s&tab=%s", self::EVW_SETTINGS_SLUG, $subPage->identifier)),
                    "nav-tab",
                    esc_html($subPage->title)
                );
            }
        }
        echo '</nav>';

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
     * Finds a page that uses the same permalink as the archive
     *
     * @return WP_Post|null
     */
    private function getConflictingPage(): ?WP_Post
    {
        $reportArchiveUrl = get_post_type_archive_link(ReportType::getSlug());

        $homeUrl = home_url();
        if (strpos($reportArchiveUrl, $homeUrl) === 0) {
            $reportArchivePath = str_replace($homeUrl, '', $reportArchiveUrl);
        } else {
            $reportArchivePath = wp_parse_url($reportArchiveUrl, PHP_URL_PATH);
        }

        return get_page_by_path($reportArchivePath);
    }

    /**
     * @return SubPage
     */
    private function getCurrentSubPage(): SubPage
    {
        $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH;
        $tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_SPECIAL_CHARS, $flags);

        if (empty($tab) || !array_key_exists($tab, $this->subPages)) {
            $subPageObjects = array_values($this->subPages);
            return $subPageObjects[0];
        }

        return $this->subPages[$tab];
    }

    /**
     * @param SubPage $subPage
     *
     * @return bool Returns true if the supplied sub page matches the currently displayed sub page
     */
    private function isCurrentSubPage(SubPage $subPage): bool
    {
        return $subPage === $this->getCurrentSubPage();
    }

    /**
     * Macht Einstellungen im System bekannt und regelt die Zugehörigkeit zu Abschnitten auf Einstellungsseiten
     */
    public function registerSettings()
    {
        $this->addSubPage(new General());
        $this->addSubPage(new Numbers());
        $this->addSubPage(new Report());
        $this->addSubPage(new ReportList());
        $this->addSubPage(new Advanced($this->permalinkController));
        $this->addSubPage(new About());

        // NEEDS_WP4.7 Standardwerte in register_setting() mitgeben
        foreach ($this->subPages as $subPage) {
            $subPage->addSettingsSections();
            $subPage->addSettingsFields();
            $subPage->registerSettings();
        }
    }
}
