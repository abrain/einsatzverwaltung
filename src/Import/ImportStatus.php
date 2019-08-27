<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Util\ProgressTracker;
use abrain\Einsatzverwaltung\Utilities;

/**
 * Class ImportStatus
 * @package abrain\Einsatzverwaltung\Import
 */
class ImportStatus extends ProgressTracker
{
    private $postIds;

    /**
     * ImportStatus constructor.
     *
     * @param Utilities $utilities
     * @param int $numReports
     */
    public function __construct(Utilities $utilities, $numReports)
    {
        parent::__construct($utilities, $numReports);
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
