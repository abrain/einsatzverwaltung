<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * Class UserRightsManagerTest
 * @package abrain\Einsatzverwaltung
 * @covers \abrain\Einsatzverwaltung\UserRightsManager
 */
class UserRightsManagerTest extends UnitTestCase
{
    public function testOtherCapsAreLeftAlone()
    {
        $userRightsManager = new UserRightsManager();
        $user = Mockery::mock('\WP_User');
        $allcaps = array('granted_cap' => 1, 'read' => 1);
        $this->assertEquals($allcaps, $userRightsManager->userHasCap($allcaps, array('foreign_cap'), array(), $user));
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUserWithRoleIsAllowedToEditReports()
    {
        $userRightsManager = new UserRightsManager();
        $user = Mockery::mock('\WP_User');
        $user->roles = array('somerole');

        // Pretend that this role is allowed to edit
        expect('get_option')->once()->with('einsatzvw_cap_roles_somerole', '0')->andReturn('1');

        $allcaps = array('granted_cap' => 1, 'read' => 1);
        $expectedCaps = array('granted_cap' => 1, 'read' => 1, 'edit_einsatzberichte' => 1);
        $userHasCap = $userRightsManager->userHasCap($allcaps, array('edit_einsatzberichte'), array(), $user);
        $this->assertEquals($expectedCaps, $userHasCap);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testUserWithoutRoleIsNotAllowedToEditReports()
    {
        $userRightsManager = new UserRightsManager();
        $user = Mockery::mock('\WP_User');
        $user->roles = array('somerole');

        // Pretend that this role is not allowed to edit
        expect('get_option')->once()->with('einsatzvw_cap_roles_somerole', '0')->andReturn('0');

        $allcaps = array('granted_cap' => 1, 'read' => 1);
        $userHasCap = $userRightsManager->userHasCap($allcaps, array('edit_einsatzberichte'), array(), $user);
        $this->assertEquals($allcaps, $userHasCap);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testAdministratorIsAlwaysAllowedToEdit()
    {
        $userRightsManager = new UserRightsManager();
        $user = Mockery::mock('\WP_User');
        $user->roles = array('somerole', 'administrator');

        // Pretend that this role is not allowed to edit
        expect('get_option')->once()->with('einsatzvw_cap_roles_somerole', '0')->andReturn('0');

        $allcaps = array('granted_cap' => 1, 'read' => 1);
        $expectedCaps = array('granted_cap' => 1, 'read' => 1, 'edit_einsatzberichte' => 1);
        $userHasCap = $userRightsManager->userHasCap($allcaps, array('edit_einsatzberichte'), array(), $user);
        $this->assertEquals($expectedCaps, $userHasCap);
    }
}
