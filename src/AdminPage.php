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
    private $pageTitle;

    /**
     * @param string $pageTitle
     */
    public function __construct(string $pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    abstract protected function echoPageContent();

    /**
     * Prints an error message.
     *
     * @param string $message The message text.
     */
    public function printError(string $message)
    {
        printf('<p class="notice notice-error">%s</p>', esc_html($message));
    }

    /**
     * Prints an informational message.
     *
     * @param string $message The message text.
     */
    public function printInfo(string $message)
    {
        printf('<p class="notice notice-info">%s</p>', esc_html($message));
    }

    /**
     * Prints a success message.
     *
     * @param string $message The message text.
     */
    public function printSuccess(string $message)
    {
        printf('<p class="notice notice-success">%s</p>', esc_html($message));
    }

    /**
     * Prints a warning message.
     *
     * @param string $message The message text.
     */
    public function printWarning(string $message)
    {
        printf('<p class="notice notice-warning">%s</p>', esc_html($message));
    }

    /**
     * Registers this page under the Tools menu.
     *
     * @param string $menuSlug
     */
    public function registerAsToolPage(string $menuSlug)
    {
        add_management_page(
            $this->pageTitle,
            esc_html($this->pageTitle),
            'manage_options',
            $menuSlug,
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
