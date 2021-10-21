<?php
namespace abrain\Einsatzverwaltung;

use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * Class UserRightsManagerTest
 * @package abrain\Einsatzverwaltung
 * @covers \abrain\Einsatzverwaltung\UserRightsManager
 */
class UserRightsManagerTest extends UnitTestCase
{
    public function testUpdateRoles()
    {
        expect('remove_role')->atLeast()->once()->with(Mockery::type('string'));
        expect('add_role')->atLeast()->once()->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'));
        (new UserRightsManager())->updateRoles();
    }
}
