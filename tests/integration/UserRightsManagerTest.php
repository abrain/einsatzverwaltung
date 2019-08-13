<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Class UserRightsManagerTest
 * @package abrain\Einsatzverwaltung
 */
class UserRightsManagerTest extends WP_UnitTestCase
{

    public function testIsRoleAllowedToEdit()
    {
        $userRightsManager = new UserRightsManager();
        $this->assertTrue($userRightsManager->isRoleAllowedToEdit('administrator'));

        delete_option('einsatzvw_cap_roles_randomrole');
        $this->assertFalse($userRightsManager->isRoleAllowedToEdit('randomrole'));
        update_option('einsatzvw_cap_roles_randomrole', '0');
        $this->assertFalse($userRightsManager->isRoleAllowedToEdit('randomrole'));
        update_option('einsatzvw_cap_roles_randomrole', '1');
        $this->assertTrue($userRightsManager->isRoleAllowedToEdit('randomrole'));
    }

    /**
     * Testet, ob die Benutzerberechtigungen korrekt vergeben werden. Die Implementierung könnte schöner sein, aber
     * bei der Entkopplung von den Benutzerrollen ändert sich ohnehin wieder vieles/alles.
     */
    public function testUserHasCap()
    {
        $userRightsManager = new UserRightsManager();

        $user1 = $this->factory->user->create_and_get(); /* @var \WP_User $user1 */

        // Ohne Rolle keine Rechte
        $requestedCaps = array('edit_einsatzberichte');
        $grantedCaps = $userRightsManager->userHasCap($user1->allcaps, $requestedCaps, array(), $user1);
        foreach ($requestedCaps as $requestedCap) {
            if (array_key_exists($requestedCap, $grantedCaps)) {
                $this->assertTrue($grantedCaps[$requestedCap] === 0);
            }
        }

        // Administratoren dürfen alles
        $user1->add_role('administrator');
        $requestedCaps = array('edit_einsatzberichte');
        $grantedCaps = $userRightsManager->userHasCap($user1->allcaps, $requestedCaps, array(), $user1);
        foreach ($requestedCaps as $requestedCap) {
            $this->assertArrayHasKey($requestedCap, $grantedCaps);
            $this->assertTrue($grantedCaps[$requestedCap] === 1);
        }

        // Fremde Berechtigungen sollen ignoriert werden
        $requestedCaps = array('some_cap');
        $grantedCaps = $userRightsManager->userHasCap($user1->allcaps, $requestedCaps, array(), $user1);
        $this->assertEquals($user1->allcaps, $grantedCaps);

        // Unberechtigte Rollen dürfen auch nichts
        $user1->remove_role('administrator');
        $user1->add_role('editor');
        $requestedCaps = array('edit_einsatzberichte');
        $grantedCaps = $userRightsManager->userHasCap($user1->allcaps, $requestedCaps, array(), $user1);
        foreach ($requestedCaps as $requestedCap) {
            if (array_key_exists($requestedCap, $grantedCaps)) {
                $this->assertTrue($grantedCaps[$requestedCap] === 0);
            }
        }

        // Ab jetzt bekommt auch die Rolle editor die Rechte
        update_option('einsatzvw_cap_roles_editor', '1');
        $requestedCaps = array('edit_einsatzberichte');
        $grantedCaps = $userRightsManager->userHasCap($user1->allcaps, $requestedCaps, array(), $user1);
        foreach ($requestedCaps as $requestedCap) {
            $this->assertArrayHasKey($requestedCap, $grantedCaps);
            $this->assertTrue($grantedCaps[$requestedCap] === 1);
        }
    }
}
