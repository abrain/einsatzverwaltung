<?php
namespace abrain\Einsatzverwaltung\Taxonomies;

use abrain\Einsatzverwaltung\Taxonomies;
use WP_UnitTestCase;

/**
 * Class TermmetaTest
 * @package abrain\Einsatzverwaltung\Taxonomies
 *
 * Stellt sicher, dass die Metadaten zu den Terms ordnungsgemäß gehandhabt werden
 */
class TermmetaTest extends WP_UnitTestCase
{
    public function testCreateTerm()
    {
        $wpCreateTerm = wp_create_term('testterm', 'exteinsatzmittel');
        $this->assertEmpty(Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));
    }

    public function testCreateTermWithMeta()
    {
        $url = 'http://www.example.org';
        $_POST['url'] = $url;
        $wpCreateTerm = wp_create_term('testterm', 'exteinsatzmittel');
        $this->assertEquals($url, Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));
    }

    public function testEditTerm()
    {
        $wpCreateTerm = wp_create_term('testterm', 'exteinsatzmittel');
        $url1 = 'http://www.example.org';
        $_POST['url'] = $url1;
        wp_update_term($wpCreateTerm['term_id'], 'exteinsatzmittel');
        $this->assertEquals($url1, Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));

        $url2 = '';
        $_POST['url'] = $url2;
        wp_update_term($wpCreateTerm['term_id'], 'exteinsatzmittel');
        $this->assertEmpty(Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));

        $url3 = 'http://www.example.com';
        $_POST['url'] = $url3;
        wp_update_term($wpCreateTerm['term_id'], 'exteinsatzmittel');
        $this->assertEquals($url3, Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));
    }

    public function testDeleteTerm()
    {
        $url = 'http://www.example.org';
        $_POST['url'] = $url;
        $wpCreateTerm = wp_create_term('testterm', 'exteinsatzmittel');
        $this->assertEquals($url, Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));

        wp_delete_term($wpCreateTerm['term_id'], 'exteinsatzmittel');
        $this->assertEmpty(Taxonomies::getTermField($wpCreateTerm['term_id'], 'exteinsatzmittel', 'url'));
    }
}
