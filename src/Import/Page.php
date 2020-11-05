<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\AdminPage;

/**
 * The main page for the Import tool
 * @package abrain\Einsatzverwaltung\Import
 */
class Page extends AdminPage
{
    /**
     * Page constructor.
     */
    public function __construct()
    {
        parent::__construct('Einsatzberichte importieren');
    }

    protected function echoPageContent()
    {
        echo '<p>Content</p>';
    }
}
