<?php
namespace abrain\Einsatzverwaltung\Types;

use PHPUnit_Framework_TestCase;
use function array_map;
use function get_post_meta;
use function get_post_type_object;
use function wp_delete_post;
use function wp_insert_post;

class UnitTest extends PHPUnit_Framework_TestCase
{
    public function testTypeExists()
    {
        $postType = get_post_type_object(Unit::getSlug());
        $this->assertNotNull($postType);
    }

    public function testRemoveReferencesWhenUnitGetsDeleted()
    {
        // Associate a report with two units
        $reportId = wp_insert_post(array('post_type' => 'einsatz', 'post_title' => 'Some Report'));
        $unit1Id = wp_insert_post(array('post_type' => 'evw_unit', 'post_title' => 'Unit 1', 'post_status' => 'publish'));
        $unit2Id = wp_insert_post(array('post_type' => 'evw_unit', 'post_title' => 'Unit 2', 'post_status' => 'publish'));
        add_post_meta($reportId, '_evw_unit', $unit1Id);
        add_post_meta($reportId, '_evw_unit', $unit2Id);

        // Delete one unit
        wp_delete_post($unit2Id);

        // The report should no longer have a reference to the deleted unit
        $unitIds = array_map('intval', get_post_meta($reportId, '_evw_unit'));
        $this->assertEquals(array($unit1Id), $unitIds);
    }
}
