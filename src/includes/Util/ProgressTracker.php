<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\Utilities;

/**
 * Class ProgressTracker
 * @package abrain\Einsatzverwaltung\Util
 */
class ProgressTracker
{
    /**
     * @var int
     */
    public $currentStep;

    /**
     * @var int
     */
    public $totalSteps;

    /**
     * @var Utilities
     */
    protected $utilities;

    /**
     * ProgressTracker constructor.
     *
     * @param Utilities $utilities
     * @param int $totalSteps
     */
    public function __construct(Utilities $utilities, $totalSteps = 0)
    {
        $this->utilities = $utilities;

        $this->currentStep = 0;

        if ($totalSteps < 0) {
            $totalSteps = 0;
        }
        $this->totalSteps = $totalSteps;
    }

    /**
     * @param string $message
     */
    public function abort($message)
    {
        $this->utilities->printError($message);
    }

    public function addStep()
    {
        $this->currentStep += 1;
    }

    /**
     * @param string $message
     */
    public function displayMessage($message)
    {
        printf('<p>%s</p>', $message);
    }

    /**
     * @param string $message
     */
    public function finish($message = '')
    {
        if ($this->totalSteps === 0) {
            $this->totalSteps = 1;
        }

        $this->currentStep = $this->totalSteps;

        if (!empty($message)) {
            $this->utilities->printSuccess($message);
        }
    }

    /**
     * @return int
     */
    public function getPercentage(): int
    {
        if ($this->totalSteps === 0) {
            return 0;
        }

        return intval($this->currentStep * 100 / $this->totalSteps);
    }
}
