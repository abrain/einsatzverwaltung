<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Tests für die Grundfunktionen des Plugins
 *
 * @author Andreas Brain
 */
class CoreTest extends WP_UnitTestCase
{
    /* @var Core $core */
    private $core;

    public function setUp()
    {
        parent::setUp();
        $this->core = new Core();
    }


    public function testUserHasCap()
    {
        $userID1 = wp_create_user('Testuser', 'abc123');
        $user1 = get_user_by('ID', $userID1);
        $this->assertEmpty($this->core->userHasCap(array(), array(), array(), $user1));

        $this->markTestIncomplete();
    }
}
