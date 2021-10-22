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
    /**
     * @throws ExpectationArgsRequired
     */
    public function testUpdateRoles()
    {
        $adminRole = Mockery::mock('\WP_Role');
        $adminRole->expects('add_cap')->once()->with('edit_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('edit_private_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('edit_published_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('edit_others_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('publish_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('read_private_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('delete_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('delete_private_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('delete_published_einsatzberichte');
        $adminRole->expects('add_cap')->once()->with('delete_others_einsatzberichte');

        expect('get_role')->once()->with('administrator')->andReturn($adminRole);

        expect('remove_role')->atLeast()->once()->with(Mockery::type('string'));
        expect('add_role')->atLeast()->once()->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'));
        (new UserRightsManager())->updateRoles();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testRoleUpdateToleratesMissingAdminRole()
    {
        expect('get_role')->once()->with('administrator')->andReturn(false);

        expect('remove_role')->atLeast()->once()->with(Mockery::type('string'));
        expect('add_role')->atLeast()->once()->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'));
        (new UserRightsManager())->updateRoles();
    }
}
