<?php
namespace abrain\Einsatzverwaltung\Util;

use abrain\Einsatzverwaltung\UnitTestCase;
use Mockery;

/**
 * Class ProgressTrackerTest
 * @covers \abrain\Einsatzverwaltung\Util\ProgressTracker
 * @package abrain\Einsatzverwaltung\Util
 */
class ProgressTrackerTest extends UnitTestCase
{
    public function testAddStep()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities);
        $this->assertEquals(0, $progressTracker->currentStep);
        $progressTracker->addStep();
        $this->assertEquals(1, $progressTracker->currentStep);
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(3, $progressTracker->currentStep);
    }

    public function testGetPercentage()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities, 6);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $this->assertEquals(16, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(50, $progressTracker->getPercentage());
    }

    public function testGetPercentageUnspecifiedTotal()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
    }

    public function testPercentageNegativeTotalSteps()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities, -5);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(0, $progressTracker->getPercentage());
    }

    public function testFinish()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities, 4);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $utilities->expects('printSuccess')->once()->with('Test message for finish method');
        $progressTracker->finish('Test message for finish method');
        $this->assertEquals(100, $progressTracker->getPercentage());
    }

    public function testFinishUnspecifiedTotal()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities);
        $this->assertEquals(0, $progressTracker->getPercentage());
        $progressTracker->finish();
        $this->assertEquals(100, $progressTracker->getPercentage());
    }

    public function testDisplayMessage()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities);
        $this->expectOutputRegex('/Some test message/');
        $progressTracker->displayMessage('Some test message');
    }

    public function testAbort()
    {
        $utilities = Mockery::mock('\abrain\Einsatzverwaltung\Utilities');
        $progressTracker = new ProgressTracker($utilities, 9);
        $progressTracker->addStep();
        $progressTracker->addStep();
        $this->assertEquals(22, $progressTracker->getPercentage());
        $utilities->expects('printError')->once()->with('Some message about a failure');
        $progressTracker->abort('Some message about a failure');
        $this->assertEquals(22, $progressTracker->getPercentage());
    }
}
