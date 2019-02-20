<?php
namespace abrain\Einsatzverwaltung\Util;

/**
 * Class ProgressTrackerTest
 * @package abrain\Einsatzverwaltung\Util
 */
class ProgressTrackerTest extends \WP_UnitTestCase
{
    public function testAddStep()
    {
        $progressTracker = new ProgressTracker();
        $this->assertEquals(0, $progressTracker->currentStep);
        $progressTracker->addStep();
        $this->assertEquals(1, $progressTracker->currentStep);
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(3, $progressTracker->currentStep);
    }

    public function testGetPercentage()
    {
        $progressTracker = new ProgressTracker(6);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $this->assertEquals(16, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(50, $progressTracker->getPercentage());
    }

    public function testGetPercentageUnspecifiedTotal()
    {
        $progressTracker = new ProgressTracker();
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
    }

    public function testPercentageNegativeTotalSteps()
    {
        $progressTracker = new ProgressTracker(-5);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
    }

    public function testFinish()
    {
        $progressTracker = new ProgressTracker(4);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $this->expectOutputRegex('/Test message for finish method/');
        $progressTracker->finish('Test message for finish method');
        $this->assertEquals(100, $progressTracker->getPercentage());
    }

    public function testFinishUnspecifiedTotal()
    {
        $progressTracker = new ProgressTracker();
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->finish();
        $this->assertEquals(100, $progressTracker->getPercentage());
    }

    public function testDisplayMessage()
    {
        $progressTracker = new ProgressTracker();
        $this->expectOutputRegex('/Some test message/');
        $progressTracker->displayMessage('Some test message');
    }

    public function testAbort()
    {
        $progressTracker = new ProgressTracker(9);
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(22, $progressTracker->getPercentage());
        $this->expectOutputRegex('/Some message about a failure/');
        $progressTracker->abort('Some message about a failure');
        $this->assertEquals(22, $progressTracker->getPercentage());
    }
}
