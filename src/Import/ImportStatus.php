<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Util\ProgressTracker;

/**
 * Class ImportStatus
 * @package abrain\Einsatzverwaltung\Import
 */
class ImportStatus extends ProgressTracker
{
    private $postIds;

    /**
     * ImportStatus constructor.
     * @param int $numReports
     */
    public function __construct($numReports)
    {
        parent::__construct($numReports);
        $this->postIds = array();
    }

    /**
     * @param int $postId
     */
    public function importSuccesss($postId)
    {
        $this->postIds[] = $postId;
        $this->addStep();
        $this->displayMessage(sprintf(
            'Importiert: <a href="%s">%s</a>',
            get_permalink($postId),
            get_the_title($postId)
        ));
    }
}
