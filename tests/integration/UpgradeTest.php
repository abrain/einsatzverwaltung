<?php
namespace abrain\Einsatzverwaltung;

use WP_UnitTestCase;
use wpdb;
use Yoast\PHPUnitPolyfills\Polyfills\AssertIsType;
use function add_post_meta;
use function array_map;
use function delete_option;
use function get_permalink;
use function get_post_meta;
use function get_term_meta;
use function is_wp_error;
use function update_option;
use function update_term_meta;
use function wp_create_term;
use function wp_insert_post;

/**
 * Class UpgradeTest
 * @package abrain\Einsatzverwaltung
 *
 * Überprüft die Funktion des Upgrademechanismus für die Datenbank
 */
class UpgradeTest extends WP_UnitTestCase
{
    use AssertIsType;

    /**
     * @var Update
     */
    private $updater;

    public function setUp(): void
    {
        parent::setUp();

        $this->updater = new Update();
    }

    /**
     * Führt ein Datenbank-Upgrade von einer bestimmten Version auf eine andere durch und überprüft das korrekte Setzen
     * der Versionsnummer
     *
     * @param int $fromVersion Datenbankversionsnummer von der ausgegangen werden soll
     * @param int $toVersion Datenbankversionsnummer auf die aktualisiert werden soll
     */
    private function runUpgrade($fromVersion, $toVersion)
    {
        if ($fromVersion === false) {
            delete_option('einsatzvw_db_version');
        } else {
            update_option('einsatzvw_db_version', $fromVersion);
        }

        self::assertEquals($fromVersion, get_option('einsatzvw_db_version'));
        $this->updater->doUpdate($fromVersion, $toVersion);
        self::assertEquals($toVersion, get_option('einsatzvw_db_version'));
    }

    public function testInsufficientParameters()
    {
        $this->assertWPError($this->updater->doUpdate(false, false));
    }

    public function testUpgrade054()
    {
        /** @var wpdb $wpdb */
        global $wpdb;

        $reportFactory = new ReportFactory();
        $reportIds = $reportFactory->create_many(3);

        $dates = array(
            '2016-03-01 01:02:03',
            '2016-04-01 04:05:06',
            '2016-11-01 07:08:09',
        );

        $gmtDates = array(
            '2016-03-01 00:02:03',
            '2016-04-01 02:05:06', // 2 Stunden Differenz
            '2016-11-01 06:08:09',
        );

        foreach ($reportIds as $index => $reportId) {
            $wpdb->update(
                $wpdb->posts,
                array('post_date' => $dates[$index], 'post_date_gmt' => '0000-00-00 00:00:00'),
                array('ID' => $reportId),
                array('%s', '%s'),
                array('%d')
            );
        }

        update_option('timezone_string', 'Europe/Berlin');
        $this->runUpgrade(false, 1);

        foreach ($reportIds as $index => $reportId) {
            self::assertEquals(
                $gmtDates[$index],
                $wpdb->get_var("SELECT post_date_gmt FROM $wpdb->posts WHERE ID = $reportId")
            );
        }
    }

    public function testUpgrade070()
    {
        $capabilities = array(
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
        );

        update_option('einsatzvw_cap_roles_administrator', 0);
        $roleObject = get_role('administrator');

        $this->runUpgrade(1, 2);

        self::assertEquals(1, get_option('einsatzvw_cap_roles_administrator'));
        foreach ($capabilities as $cap) {
            self::assertTrue($roleObject->has_cap($cap));
        }
    }

    public function testUpgrade082()
    {
        update_option('einsatzvw_show_links_in_excerpt', 'to be deleted');
        $this->runUpgrade(2, 3);
        self::assertFalse(get_option('einsatzvw_show_links_in_excerpt'));
    }

    public function testUpgrade090()
    {
        $reportFactory = new ReportFactory();
        $reportIds = $reportFactory->create_many(3);
        update_post_meta($reportIds[0], 'einsatz_mannschaft', '1');
        update_post_meta($reportIds[1], 'einsatz_mannschaft', '0');
        update_post_meta($reportIds[2], 'einsatz_mannschaft', '1/8');

        $this->runUpgrade(3, 4);

        self::assertEquals('1', get_post_meta($reportIds[0], 'einsatz_mannschaft', true));
        self::assertEquals('', get_post_meta($reportIds[1], 'einsatz_mannschaft', true));
        self::assertEquals('1/8', get_post_meta($reportIds[2], 'einsatz_mannschaft', true));
    }

    public function testUpgrade100()
    {
        delete_option('einsatzvw_rewrite_slug');
        $this->runUpgrade(4, 5);
        self::assertEquals('einsaetze', get_option('einsatzvw_rewrite_slug'));
    }

    public function testUpgrade113()
    {
        $capabilities = array(
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
        );

        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $roleSlug) {
                $roleObject = get_role($roleSlug);
                foreach ($capabilities as $cap) {
                    $roleObject->add_cap($cap);
                }
            }
        }
        update_option('einsatzvw_cap_roles_administrator', 1);

        $this->runUpgrade(5, 6);

        $roles = get_editable_roles();
        if (!empty($roles)) {
            foreach (array_keys($roles) as $roleSlug) {
                $roleObject = get_role($roleSlug);
                foreach ($capabilities as $cap) {
                    self::assertFalse($roleObject->has_cap($cap));
                }
            }
        }
        self::assertFalse(get_option('einsatzvw_cap_roles_administrator'));
    }

    public function testUpgrade114()
    {
        update_option('einsatzvw_flush_rewrite_rules', 0);
        $this->runUpgrade(6, 7);
        self::assertEquals(1, get_option('einsatzvw_flush_rewrite_rules'));
    }

    public function testUpgrade120()
    {
        // Einsatzberichte ohne Kategorie vorbereiten
        $categoryId = wp_create_category('Test Einsätze');
        self::assertNotEquals(0, $categoryId);
        update_option('einsatzvw_category', $categoryId);
        $reportFactory = new ReportFactory();
        $reportIds = $reportFactory->create_many(8);
        foreach (array_slice($reportIds, 0, 3) as $reportId) {
            wp_set_post_categories($reportId, array());
        }
        foreach (array_slice($reportIds, 0, 3) as $reportId) {
            self::assertFalse(in_category($categoryId, $reportId));
        }

        // Nicht bzw. falsch nummerierte Einsatzberichte vorbereiten
        $dates = array(
            '2016-10-05 01:02:03',
            '2016-10-01 04:05:06',
            '2016-11-01 07:08:10',
            '2016-11-01 07:08:09',
            '2016-10-23 10:11:12',
        );
        foreach (array_slice($reportIds, 3, 5) as $index => $reportId) {
            wp_update_post(array(
                'ID' => $reportId,
                'post_date' => $dates[$index]
            ));
        }
        foreach (array_slice($reportIds, 3, 5) as $reportId) {
            delete_post_meta($reportId, 'einsatz_seqNum');
        }

        // Einzelne Einsatzberichte als besonders markieren
        delete_post_meta_by_key('einsatz_special');
        update_post_meta($reportIds[0], 'einsatz_special', 1);
        update_post_meta($reportIds[3], 'einsatz_special', 1);
        update_post_meta($reportIds[5], 'einsatz_special', 1);

        update_option('einsatzvw_category_only_special', 'randomContent');
        delete_option('einsatzvw_loop_only_special');

        $this->runUpgrade(7, 10);

        foreach (array_slice($reportIds, 0, 3) as $reportId) {
            self::assertTrue(in_category($categoryId, $reportId));
        }

        self::assertEquals(1, get_post_meta($reportIds[4], 'einsatz_seqNum', true));
        self::assertEquals(2, get_post_meta($reportIds[3], 'einsatz_seqNum', true));
        self::assertEquals(3, get_post_meta($reportIds[7], 'einsatz_seqNum', true));
        self::assertEquals(4, get_post_meta($reportIds[6], 'einsatz_seqNum', true));
        self::assertEquals(5, get_post_meta($reportIds[5], 'einsatz_seqNum', true));

        self::assertEquals(1, get_post_meta($reportIds[0], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[1], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[2], 'einsatz_special', true));
        self::assertEquals(1, get_post_meta($reportIds[3], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[4], 'einsatz_special', true));
        self::assertEquals(1, get_post_meta($reportIds[5], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[6], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[7], 'einsatz_special', true));

        self::assertEquals('randomContent', get_option('einsatzvw_loop_only_special'));
        self::assertFalse(get_option('einsatzvw_category_only_special'));
    }

    public function testUpgrade120NoCategory()
    {
        delete_option('einsatzvw_category');

        $reportFactory = new ReportFactory();
        $reportIds = $reportFactory->create_many(3);
        foreach ($reportIds as $reportId) {
            self::assertEmpty(wp_get_post_categories($reportId));
        }

        $this->runUpgrade(7, 10);

        foreach ($reportIds as $reportId) {
            self::assertEmpty(wp_get_post_categories($reportId));
        }
    }

    public function testUpgrade130()
    {
        $ee1 = wp_create_term('Externes Einsatzmittel 1', 'exteinsatzmittel');
        delete_term_meta($ee1['term_id'], 'url'); // Metafelder sollen noch nicht existieren
        add_option('evw_tax_exteinsatzmittel_'.$ee1['term_id'].'_url', 'website1');
        $ee2 = wp_create_term('Externes Einsatzmittel 2', 'exteinsatzmittel');
        delete_term_meta($ee2['term_id'], 'url');
        add_option('evw_tax_exteinsatzmittel_'.$ee2['term_id'].'_url', 'website2');

        $vehicle1 = wp_create_term('Fahrzeug 1', 'fahrzeug');
        delete_term_meta($vehicle1['term_id'], 'fahrzeugpid');
        delete_term_meta($vehicle1['term_id'], 'vehicleorder');
        add_option('evw_tax_fahrzeug_'.$vehicle1['term_id'].'_fahrzeugpid', 46);
        add_option('evw_tax_fahrzeug_'.$vehicle1['term_id'].'_vehicleorder', 1);
        $vehicle2 = wp_create_term('Fahrzeug 2', 'fahrzeug');
        delete_term_meta($vehicle2['term_id'], 'fahrzeugpid');
        delete_term_meta($vehicle2['term_id'], 'vehicleorder');
        add_option('evw_tax_fahrzeug_'.$vehicle2['term_id'].'_vehicleorder', 147);

        // Unbehandelte Term-Splits
        $fakeTermId = 987;
        add_option('evw_tax_fahrzeug_'.$fakeTermId.'_fahrzeugpid', 915);
        update_option('_split_terms', array($fakeTermId => array('fahrzeug' => $vehicle2['term_id'])));

        // Ungültige Metakeys
        add_option('evw_tax_exteinsatzmittel_'.$ee1['term_id'].'_invalid', 'dontcare');
        add_option('evw_tax_fahrzeug_'.$vehicle1['term_id'].'_something', 'dontcare');
        add_option('evw_tax_fahrzeug_'.$vehicle2['term_id'].'_rubbish', 'dontcare');

        // Einsatzbericht mit altem post_name
        $reportFactory = new ReportFactory();
        $reportId1 = $reportFactory->create(array('post_name' => '1234'));
        $reportId2 = $reportFactory->create(array('post_name' => '4567'));
        $reportId3 = $reportFactory->create(array('post_name' => '7890'));
        self::assertEmpty(get_post_meta($reportId1, 'einsatz_incidentNumber', true));
        self::assertEmpty(get_post_meta($reportId2, 'einsatz_incidentNumber', true));
        self::assertEmpty(get_post_meta($reportId3, 'einsatz_incidentNumber', true));

        $this->runUpgrade(10, 20);

        self::assertFalse(get_option('evw_tax_exteinsatzmittel_'.$ee1['term_id'].'_url'));
        self::assertEquals('website1', get_term_meta($ee1['term_id'], 'url', true));
        self::assertFalse(get_option('evw_tax_exteinsatzmittel_'.$ee2['term_id'].'_url'));
        self::assertEquals('website2', get_term_meta($ee2['term_id'], 'url', true));

        self::assertFalse(get_option('evw_tax_fahrzeug_'.$vehicle1['term_id'].'_fahrzeugpid'));
        self::assertFalse(get_option('evw_tax_fahrzeug_'.$vehicle1['term_id'].'_vehicleorder'));
        self::assertEquals(46, get_term_meta($vehicle1['term_id'], 'fahrzeugpid', true));
        self::assertEquals(1, get_term_meta($vehicle1['term_id'], 'vehicleorder', true));
        self::assertFalse(get_option('evw_tax_fahrzeug_'.$vehicle2['term_id'].'_vehicleorder'));
        self::assertEquals(147, get_term_meta($vehicle2['term_id'], 'vehicleorder', true));

        // Unbehandelte Term-Splits
        self::assertFalse(get_option('evw_tax_fahrzeug_'.$fakeTermId.'_fahrzeugpid'));
        self::assertEquals(915, get_term_meta($vehicle2['term_id'], 'fahrzeugpid', true));

        // Ungültige Metakeys
        self::assertNotFalse(get_option('evw_tax_exteinsatzmittel_'.$ee1['term_id'].'_invalid'));
        self::assertEmpty(get_term_meta($ee1['term_id'], 'invalid', true));
        self::assertNotFalse(get_option('evw_tax_fahrzeug_'.$vehicle1['term_id'].'_something'));
        self::assertEmpty(get_term_meta($vehicle1['term_id'], 'something', true));
        self::assertNotFalse(get_option('evw_tax_fahrzeug_'.$vehicle2['term_id'].'_rubbish'));
        self::assertEmpty(get_term_meta($vehicle2['term_id'], 'rubbish', true));

        // Einsatznummern sollten jetzt in Postmeta gespeichert sein
        self::assertEquals('1234', get_post_meta($reportId1, 'einsatz_incidentNumber', true));
        self::assertEquals('4567', get_post_meta($reportId2, 'einsatz_incidentNumber', true));
        self::assertEquals('7890', get_post_meta($reportId3, 'einsatz_incidentNumber', true));

        // Prüfe auf aktivierte Admin Notice
        self::assertIsArray(get_option('einsatzverwaltung_admin_notices'));
        self::assertContains('regenerateSlugs', get_option('einsatzverwaltung_admin_notices'));
    }

    public function testUpgrade134()
    {
        $reportFactory = new ReportFactory();
        $reportIds = $reportFactory->create_many(5);
        delete_post_meta($reportIds[1], 'einsatz_special');
        delete_post_meta($reportIds[4], 'einsatz_special');
        update_post_meta($reportIds[3], 'einsatz_special', 1);

        self::assertEquals(0, get_post_meta($reportIds[0], 'einsatz_special', true));
        self::assertEquals('', get_post_meta($reportIds[1], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[2], 'einsatz_special', true));
        self::assertEquals(1, get_post_meta($reportIds[3], 'einsatz_special', true));
        self::assertEquals('', get_post_meta($reportIds[4], 'einsatz_special', true));

        $this->runUpgrade(20, 21);

        self::assertEquals(0, get_post_meta($reportIds[0], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[1], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[2], 'einsatz_special', true));
        self::assertEquals(1, get_post_meta($reportIds[3], 'einsatz_special', true));
        self::assertEquals(0, get_post_meta($reportIds[4], 'einsatz_special', true));
    }

    public function testUpgrade140()
    {
        $this->runUpgrade(21, 30);
    }

    public function testUpgrade150()
    {
        $this->runUpgrade(30, 40);

        $this->assertEquals('1', get_option('einsatz_support_posttag'));
    }

    public function testUpgrade162()
    {
        delete_option('einsatzverwaltung_report_contentifempty');
        update_option('einsatzverwaltung_use_reporttemplate', 'no');
        $this->runUpgrade(40, 41);
        $this->assertNotEmpty(get_option('einsatzverwaltung_report_contentifempty'));
    }

    public function testUpgrade162WithTemplate()
    {
        delete_option('einsatzverwaltung_report_contentifempty');
        update_option('einsatzverwaltung_use_reporttemplate', 'singular');
        $this->runUpgrade(40, 41);
        $this->assertTrue('' === get_option('einsatzverwaltung_report_contentifempty'));
    }

    public function testUpgrade170ConvertPageIdToUrl()
    {
        // Create some terms for testing
        $vehicle1 = wp_create_term('Test Vehicle 1', 'fahrzeug');
        $vehicle1Id = $vehicle1['term_id'];
        $vehicle2 = wp_create_term('Test Vehicle 2', 'fahrzeug');
        $vehicle2Id = $vehicle2['term_id'];
        $postTag = wp_create_term('Some tag');
        $tagId = $postTag['term_id'];

        // Create some test posts/pages and associate them with the terms
        $post1Id = wp_insert_post(array('post_type' => 'post', 'post_title' => 'Some post'), true);
        $pageId = wp_insert_post(array('post_type' => 'page', 'post_title' => 'Some page'), true);
        $post2Id = wp_insert_post(array('post_type' => 'post', 'post_title' => 'Another post'), true);
        if (is_wp_error($post1Id) || is_wp_error($pageId) || is_wp_error($post2Id)) {
            $this->fail('Could not create posts');
        }
        update_term_meta($vehicle1Id, 'fahrzeugpid', $post1Id);
        update_term_meta($vehicle2Id, 'fahrzeugpid', $pageId);
        update_term_meta($tagId, 'fahrzeugpid', $post2Id);

        $this->runUpgrade(41, 50);

        // Check that the associated post got moved to the external URL field
        $this->assertEmpty(get_term_meta($vehicle1Id, 'fahrzeugpid', true));
        $this->assertEquals(get_permalink($post1Id), get_term_meta($vehicle1Id, 'vehicle_exturl', true));

        // Check that the associated page stayed as it was
        $this->assertEquals($pageId, get_term_meta($vehicle2Id, 'fahrzeugpid', true));
        $this->assertEmpty(get_term_meta($vehicle2Id, 'vehicle_exturl', true));

        // Check that another taxonomy term with the same term meta did not get changed
        $this->assertEquals($post2Id, get_term_meta($tagId, 'fahrzeugpid', true));
        $this->assertEmpty(get_term_meta($tagId, 'vehicle_exturl', true));
    }

    public function testUpgrade170RemoveVersionFromDb()
    {
        update_option('einsatzvw_version', '1.7.0');
        $this->runUpgrade(41, 50);
        $this->assertFalse(get_option('einsatzvw_version'));
    }

    public function testUpgrade171RemoveDeletedUnitRelations()
    {
        // Associate a report with two units, of which one does not exist
        $reportId = wp_insert_post(array('post_type' => 'einsatz', 'post_title' => 'Some Report'));
        $unit1Id = wp_insert_post(array('post_type' => 'evw_unit', 'post_title' => 'Unit 1', 'post_status' => 'publish'));
        add_post_meta($reportId, '_evw_unit', $unit1Id);
        add_post_meta($reportId, '_evw_unit', 999999);

        // Also create a post that has _evw_unit postmeta entries, just in case some other plugin does that
        $postId = wp_insert_post(array('post_type' => 'post', 'post_title' => 'Some Post'));
        add_post_meta($postId, '_evw_unit', $unit1Id);
        add_post_meta($postId, '_evw_unit', 999999);

        $this->runUpgrade(50, 51);

        // The report should no longer have a reference to the deleted unit
        $unitIds = array_map('intval', get_post_meta($reportId, '_evw_unit'));
        $this->assertEquals(array($unit1Id), $unitIds);

        // The post should still have its postmeta entries as the update must not touch anything except the einsatz CPT
        $postMeta = array_map('intval', get_post_meta($postId, '_evw_unit'));
        $this->assertEquals(array($unit1Id, 999999), $postMeta);
    }
}
