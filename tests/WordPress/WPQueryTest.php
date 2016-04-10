<?php
namespace abrain\Einsatzverwaltung\WordPress;

use abrain\Einsatzverwaltung\ReportFactory;
use WP_Query;
use WP_UnitTestCase;

/**
 * Prüft, ob die Klasse WP_Query auch mit dem Plugin noch korrekt arbeitet
 *
 * @package abrain\Einsatzverwaltung\WordPress
 */
class WPQueryTest extends WP_UnitTestCase
{
    /**
     * Erzeugt vor jedem Test die benötigten Testdaten
     */
    public function setUp()
    {
        parent::setUp();

        // Normale WordPress-Beiträge
        $this->factory->post->create_many(7);

        // Einsatzberichte
        $reportFactory = new ReportFactory();
        $reports = $reportFactory->create_many(5);
        update_post_meta($reports[0], 'einsatz_special', 1);
        update_post_meta($reports[2], 'einsatz_special', 1);

        // Beiträge eines fremden Plugins
        $this->factory->post->create_many(3, array('post_type' => 'thirdparty'));
    }

    /**
     * Es sollen nur WordPress-Beiträge abgerufen werden
     */
    public function testOnlyPosts()
    {
        update_option('einsatzvw_show_einsatzberichte_mainloop', 0);

        // Without post_type
        $query = new WP_Query(array(
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(7, $query->found_posts);

        // Empty post_type
        $query1 = new WP_Query(array(
            'post_type' => '',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(7, $query1->found_posts);
        
        // With single post_type
        $query2 = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(7, $query2->found_posts);

        // With post_type array
        $query3 = new WP_Query(array(
            'post_type' => array('post'),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(7, $query3->found_posts);
    }

    /**
     * Die Einsatzberichte sollen zwischen den Beiträgen erscheinen
     */
    public function testPostsWithReports()
    {
        update_option('einsatzvw_show_einsatzberichte_mainloop', 1);

        // Without post_type
        $query = new WP_Query(array(
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(12, $query->found_posts);

        // With single post_type
        $query2 = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(12, $query2->found_posts);

        // With post_type array
        $query3 = new WP_Query(array(
            'post_type' => array('post'),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(12, $query3->found_posts);
    }

    /**
     * Beitragstypen anderer Plugins sollen nicht gestört werden
     */
    public function testOnlyThirdParty()
    {
        update_option('einsatzvw_show_einsatzberichte_mainloop', 0);

        // With single post_type
        $query = new WP_Query(array(
            'post_type' => 'thirdparty',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(3, $query->found_posts);

        // With post_type array
        $query2 = new WP_Query(array(
            'post_type' => array('thirdparty'),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(3, $query2->found_posts);
    }

    /**
     * Beitragstypen anderer Plugins sollen auch dann nicht gestört werden, wenn die Einsatzberichte zwischen den
     * Beiträgen erscheinen sollen
     */
    public function testOnlyThirdPartyMainloop()
    {
        update_option('einsatzvw_show_einsatzberichte_mainloop', 1);

        // With single post_type
        $query = new WP_Query(array(
            'post_type' => 'thirdparty',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(3, $query->found_posts);

        // With post_type array
        $query2 = new WP_Query(array(
            'post_type' => array('thirdparty'),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(3, $query2->found_posts);
    }

    /**
     * Werden fremde Beitragstypen zusammen mit Beiträgen abgefragt, werden die Einsatzberichte hinzugenommen, sofern
     * die Option, die Einsatzberichte zwischen den Beiträgen anzuzeigen, aktiviert ist
     */
    public function testThirdPartyWithPosts()
    {
        update_option('einsatzvw_show_einsatzberichte_mainloop', 0);

        // With post_type array
        $query = new WP_Query(array(
            'post_type' => array('post', 'thirdparty'),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(10, $query->found_posts);

        update_option('einsatzvw_show_einsatzberichte_mainloop', 1);

        // With post_type array
        $query2 = new WP_Query(array(
            'post_type' => array('post', 'thirdparty'),
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(15, $query2->found_posts);
    }

    /**
     * Optional können nur als besonders markierte Einsatzberichte zusammen mit den Beiträgen angezeigt werden
     */
    public function testPostsAndOnlySpecialReports()
    {
        update_option('einsatzvw_show_einsatzberichte_mainloop', 1);
        update_option('einsatzvw_loop_only_special', 1);

        // Without post_type
        $query = new WP_Query(array(
            'post_status' => 'publish',
            'posts_per_page' => '-1',
        ));
        $this->assertEquals(9, $query->found_posts);
    }
}
