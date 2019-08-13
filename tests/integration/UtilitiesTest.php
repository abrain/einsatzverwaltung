<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;

/**
 * Tests für diverse Hilfsfunktionen
 *
 * @author Andreas Brain
 */
class UtilitiesTest extends WP_UnitTestCase
{
    public function testRemovePostFromCategory()
    {
        // Einsatzbericht anlegen und allen Kategorien zuweisen
        $postId = $this->factory->post->create(array('post_type' => 'einsatz'));
        $categoryIds = $this->factory->category->create_many(4);
        wp_set_post_categories($postId, $categoryIds);
        $this->assertEquals($categoryIds, wp_get_post_categories($postId));

        // Kategorie entfernen und prüfen, ob die Aktion erfolgreich war
        Utilities::removePostFromCategory($postId, $categoryIds[0]);
        $this->assertEquals(array_slice($categoryIds, 1), wp_get_post_categories($postId));

        // Kategorie entfernen und prüfen, ob die Aktion erfolgreich war
        Utilities::removePostFromCategory($postId, $categoryIds[3]);
        $this->assertEquals(array_slice($categoryIds, 1, 2), wp_get_post_categories($postId));
    }
}
