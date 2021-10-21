<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;
use function update_option;
use function user_can;

/**
 * Class CapabilitiesTest
 * @package abrain\Einsatzverwaltung
 */
class CapabilitiesTest extends WP_UnitTestCase
{
    private $capabilities = [
        'edit_einsatzberichte',
        'edit_private_einsatzberichte',
        'edit_published_einsatzberichte',
        'edit_others_einsatzberichte',
        'publish_einsatzberichte',
        'read_private_einsatzberichte',
        'delete_einsatzberichte',
        'delete_private_einsatzberichte',
        'delete_published_einsatzberichte',
        'delete_others_einsatzberichte'
    ];

    public function testUserWithoutRoleHasNoCaps()
    {
        $user = $this->factory->user->create_and_get();

        foreach ($this->capabilities as $capability) {
            $this->assertFalse(user_can($user, $capability));
        }
    }

    public function testAdministratorsCanDoAnything()
    {
        $user = $this->factory->user->create_and_get();
        $user->add_role('administrator');

        foreach ($this->capabilities as $capability) {
            $this->assertTrue(user_can($user, $capability));
        }
    }

    public function testDefaultEditorHasNoCaps()
    {
        $user = $this->factory->user->create_and_get();
        $user->add_role('editor');

        foreach ($this->capabilities as $capability) {
            $this->assertFalse(user_can($user, $capability));
        }
    }

    public function testEditorCanDoAnythingWhenEnabled()
    {
        $user = $this->factory->user->create_and_get();
        $user->add_role('editor');
        update_option('einsatzvw_cap_roles_editor', '1');

        foreach ($this->capabilities as $capability) {
            $this->assertTrue(user_can($user, $capability));
        }
    }
}
