<?php

namespace abrain\Einsatzverwaltung;

use function add_management_page;
use function esc_html;
use function printf;

/**
 * Base class for pages in the admin area.
 *
 * @package abrain\Einsatzverwaltung
 */
abstract class AdminPage
{
    /**
     * @var string
     */
    private $menuSlug;

    /**
     * @var string
     */
    private $pageTitle;

    /**
     * @param string $pageTitle
     * @param string $menuSlug
     */
    public function __construct(string $pageTitle, string $menuSlug)
    {
        $this->pageTitle = $pageTitle;
        $this->menuSlug = $menuSlug;
    }

    abstract protected function echoPageContent();

    /**
     * Prints an error message.
     *
     * @param string $message The message text.
     */
    protected function printError(string $message)
    {
        printf('<p class="notice notice-error">%s</p>', esc_html($message));
    }

    /**
     * Prints an informational message.
     *
     * @param string $message The message text.
     */
    protected function printInfo(string $message)
    {
        printf('<p class="notice notice-info">%s</p>', esc_html($message));
    }

    /**
     * Prints a success message.
     *
     * @param string $message The message text.
     */
    protected function printSuccess(string $message)
    {
        printf('<p class="notice notice-success">%s</p>', esc_html($message));
    }

    /**
     * Prints a warning message.
     *
     * @param string $message The message text.
     */
    protected function printWarning(string $message)
    {
        printf('<p class="notice notice-warning">%s</p>', esc_html($message));
    }

    /**
     * Registers this page under the Tools menu.
     */
    public function registerAsToolPage()
    {
        add_management_page(
            $this->pageTitle,
            esc_html($this->pageTitle),
            'manage_options',
            $this->menuSlug,
            array($this, 'render')
        );
    }

    /**
     * Generates the output of the page.
     */
    public function render()
    {
        echo '<div class="wrap">';
        printf("<h1>%s</h1>", esc_html($this->pageTitle));
        $this->echoPageContent();
        echo '</div>';
    }
}
