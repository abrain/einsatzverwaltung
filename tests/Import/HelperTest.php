<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Core;
use abrain\Einsatzverwaltung\Exceptions\ImportPreparationException;
use DateTime;

/**
 * Class HelperTest
 * @package abrain\Einsatzverwaltung
 */
class HelperTest extends \WP_UnitTestCase
{
    /** @var Core */
    private static $core;

    /** @var Helper */
    private static $helper;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        self::$core = Core::getInstance();
        self::$helper = new Helper(self::$core->utilities, self::$core->getData());

        register_taxonomy('hierarchy', array(), array('hierarchical' => true));
        register_taxonomy('nohierarchy', array(), array('hierarchical' => false));
        register_taxonomy('someTax', array(), array('hierarchical' => false));

        self::$helper->metaFields = array('someMetaField' => array('label' => 'Some meta field'));
        self::$helper->postFields = array(
            'somePostField' => array('label' => 'Some post field'),
            'anotherPostField' => array('label' => 'Another post field')
        );
        self::$helper->taxonomies = array(
            'hierarchy' => array('label' => 'Hierarchical taxonomy'),
            'nohierarchy' => array('label' => 'Non-hierarchical taxonomy'),
            'someTax' => array('label' => 'Another non-hierarchical taxonomy')
        );

        parent::setUpBeforeClass();
    }

    /**
     * Checks the generated string for tax_input for a non-hierarchical taxonomy.
     */
    public function testGetTaxInputStringForNT()
    {
        $input = 'term1, term78, term99';
        try {
            $result = self::$helper->getTaxInputString('nohierarchy', $input);
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
            return;
        }
        $this->assertEquals($input, $result);
    }

    /**
     * Checks the generated string for tax_input for a hierarchical taxonomy.
     */
    public function testGetTaxInputStringForHT()
    {
        $terms = array('hterm6', 'hterm123', 'hterm777');

        // Check that the terms do not exist yet
        foreach ($terms as $term) {
            $this->assertFalse(get_term_by('name', $term, 'hierarchy'));
        }

        // Create one upfront
        $term = wp_insert_term($terms[0], 'hierarchy');
        $this->assertInternalType('array', $term);
        $exitingId = $term['term_id'];

        $input = implode(',', $terms);
        try {
            $result = self::$helper->getTaxInputString('hierarchy', $input);
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
            return;
        }
        $returnedIds = explode(',', $result);
        $this->assertCount(count($terms), $returnedIds);

        // Check that the existing term was reused
        $this->assertContains($exitingId, $returnedIds);

        // Check that the terms have been created
        $names = array();
        foreach ($returnedIds as $id) {
            $wpterm = get_term_by('id', $id, 'hierarchy');
            $this->assertNotFalse($wpterm);
            $names[] = $wpterm->name;
        }

        $this->assertEquals($names, $terms);
    }

    public function testGetExistingTermId()
    {
        // Make sure term exists
        $termName = 'existingTerm';
        $term = wp_insert_term($termName, 'hierarchy');
        $this->assertInternalType('array', $term);

        try {
            $returnedTermId = self::$helper->getTermId($termName, 'hierarchy');
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
            return;
        }

        $this->assertEquals($term['term_id'], $returnedTermId);
    }

    public function testGetUnknownTermId()
    {
        $termName = 'unknownTerm';

        // Make sure term does not exist
        $wpterm = get_term_by('name', $termName, 'hierarchy');
        $this->assertFalse($wpterm);

        try {
            $returnedTermId = self::$helper->getTermId($termName, 'hierarchy');
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
            return;
        }

        // Term should have been created
        $wpterm = get_term_by('name', $termName, 'hierarchy');
        $this->assertNotFalse($wpterm);

        $termId = $wpterm->term_id;
        $this->assertEquals($termId, $returnedTermId);
        $this->assertNotEquals($termId, $termName);
    }

    public function testMapEntryToInsertArgs()
    {
        $insertArgs = array();
        $valuesTaxonomy1 = array('aValue', 'another value');
        $valuesTaxonomy2 = array('some string', 'even another value');

        $mapping = array(
            'f1' => 'hierarchy',
            'f2' => 'somePostField',
            'f3' => 'anotherPostField',
            'f4' => 'someMetaField',
            'f5' => 'nohierarchy',
            'f6' => 'someTax'
        );

        $entry = array(
            'f1' => implode(',', $valuesTaxonomy1),
            'f2' => 'post field content',
            'f3' => 'lorem ipsum',
            'f4' => 'so meta!',
            'f5' => implode(',', $valuesTaxonomy2),
            'f6' => ''
        );

        try {
            self::$helper->mapEntryToInsertArgs($mapping, $entry, $insertArgs);
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
        }

        // Check post fields
        $this->assertArrayHasKey('somePostField', $insertArgs);
        $this->assertEquals('post field content', $insertArgs['somePostField']);
        $this->assertArrayHasKey('anotherPostField', $insertArgs);
        $this->assertEquals('lorem ipsum', $insertArgs['anotherPostField']);

        // Check taxonomies
        $this->assertArrayHasKey('tax_input', $insertArgs);
        $this->assertArrayHasKey('hierarchy', $insertArgs['tax_input']);
        $inputTaxonomy1 = explode(',', $insertArgs['tax_input']['hierarchy']);
        $this->assertCount(count($valuesTaxonomy1), $inputTaxonomy1);
        foreach ($inputTaxonomy1 as $value) {
            $this->assertTrue(is_numeric($value));
        }
        $this->assertArrayHasKey('nohierarchy', $insertArgs['tax_input']);
        $inputTaxonomy2 = explode(',', $insertArgs['tax_input']['nohierarchy']);
        $this->assertEquals($valuesTaxonomy2, $inputTaxonomy2);
        $this->assertArrayNotHasKey('someTax', $insertArgs['tax_input']); // empty value should have been ignored

        // Check meta data
        $this->assertArrayHasKey('meta_input', $insertArgs);
        $this->assertArrayHasKey('someMetaField', $insertArgs['meta_input']);
        $this->assertEquals('so meta!', $insertArgs['meta_input']['someMetaField']);
    }

    public function testPrepareArgsForInsertPostDraft()
    {
        $insertArgs = array(
            'post_date' => '29.12.2017 18:24',
            'meta_input' => array(
                'einsatz_einsatzende' => '2017/12-29 23 42',
                'einsatz_special' => '1'
            ),
            'post_title' => 'Nr. 112 - Technische Hilfeleistung'
        );
        try {
            $postStatus = 'draft';
            $alarmzeit = DateTime::createFromFormat('d.m.Y H:i', $insertArgs['post_date']);
            self::$helper->prepareArgsForInsertPost($insertArgs, 'Y/m-d H i', $postStatus, $alarmzeit);
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
        }

        // It's not an update, a new post shall be created
        $this->assertArrayNotHasKey('ID', $insertArgs);

        // Post date should not be touched
        $this->assertArrayNotHasKey('post_date', $insertArgs);
        //$this->assertEquals('2017-12-29 18:24', $insertArgs['post_date']);

        // Same goes for the GMT date
        $this->assertArrayNotHasKey('post_date_gmt', $insertArgs);
        //$this->assertEquals('2017-12-29 17:24:00', $insertArgs['post_date_gmt']);

        // Check if the post title has not been modified
        $this->assertArrayHasKey('post_title', $insertArgs);
        $this->assertEquals('Nr. 112 - Technische Hilfeleistung', $insertArgs['post_title']);

        // The given post status should have been preserved
        $this->assertArrayHasKey('post_status', $insertArgs);
        $this->assertEquals($postStatus, $insertArgs['post_status']);

        // The correct post_type must have been set
        $this->assertArrayHasKey('post_type', $insertArgs);
        $this->assertEquals('einsatz', $insertArgs['post_type']);

        // The date and time of ending should be formatted according to the given format
        $this->assertArrayHasKey('meta_input', $insertArgs);
        $this->assertArrayHasKey('einsatz_einsatzende', $insertArgs['meta_input']);
        $this->assertEquals('2017-12-29 23:42', $insertArgs['meta_input']['einsatz_einsatzende']);

        // The time of alterting should have been stored temporarily in postmeta
        $this->assertArrayHasKey('_einsatz_timeofalerting', $insertArgs['meta_input']);
        $this->assertEquals('2017-12-29 18:24:00', $insertArgs['meta_input']['_einsatz_timeofalerting']);

        // Check that the special flag is still present
        $this->assertArrayHasKey('einsatz_special', $insertArgs['meta_input']);
        $this->assertEquals('1', $insertArgs['meta_input']['einsatz_special']);
    }

    public function testPrepareArgsForInsertPostPublish()
    {
        $insertArgs = array(
            'post_date' => '29.12.2017 18:24',
            'meta_input' => array(
                'einsatz_einsatzende' => '2017/12-29 23 42',
                'einsatz_special' => '1'
            ),
            'post_title' => 'Nr. 112 - Technische Hilfeleistung'
        );
        try {
            $postStatus = 'publish';
            $alarmzeit = DateTime::createFromFormat('d.m.Y H:i', $insertArgs['post_date']);
            self::$helper->prepareArgsForInsertPost($insertArgs, 'Y/m-d H i', $postStatus, $alarmzeit);
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
        }

        // It's not an update, a new post shall be created
        $this->assertArrayNotHasKey('ID', $insertArgs);

        // Date should be correctly formatted for the database
        $this->assertArrayHasKey('post_date', $insertArgs);
        $this->assertEquals('2017-12-29 18:24:00', $insertArgs['post_date']);

        // Same goes for the GMT date
        $this->assertArrayHasKey('post_date_gmt', $insertArgs);
        $this->assertEquals('2017-12-29 17:24:00', $insertArgs['post_date_gmt']);

        // Check if the post title has not been modified
        $this->assertArrayHasKey('post_title', $insertArgs);
        $this->assertEquals('Nr. 112 - Technische Hilfeleistung', $insertArgs['post_title']);

        // The given post status should have been preserved
        $this->assertArrayHasKey('post_status', $insertArgs);
        $this->assertEquals($postStatus, $insertArgs['post_status']);

        // The correct post_type must have been set
        $this->assertArrayHasKey('post_type', $insertArgs);
        $this->assertEquals('einsatz', $insertArgs['post_type']);

        // The date and time of ending should be formatted according to the given format
        $this->assertArrayHasKey('meta_input', $insertArgs);
        $this->assertArrayHasKey('einsatz_einsatzende', $insertArgs['meta_input']);
        $this->assertEquals('2017-12-29 23:42', $insertArgs['meta_input']['einsatz_einsatzende']);

        // The time of alterting should not have been stored temporarily in postmeta
        $this->assertArrayNotHasKey('_einsatz_timeofalerting', $insertArgs['meta_input']);

        // Check that the special flag is still present
        $this->assertArrayHasKey('einsatz_special', $insertArgs['meta_input']);
        $this->assertEquals('1', $insertArgs['meta_input']['einsatz_special']);
    }

    public function testDefaults()
    {
        $insertArgs = array(
            'post_date' => '29.12.2017 18:24',
            'meta_input' => array(
                'einsatz_einsatzende' => '2017/12-29 23 42'
            )
        );
        try {
            $postStatus = 'publish';
            $alarmzeit = DateTime::createFromFormat('d.m.Y H:i', $insertArgs['post_date']);
            self::$helper->prepareArgsForInsertPost($insertArgs, 'Y/m-d H i', $postStatus, $alarmzeit);
        } catch (ImportPreparationException $e) {
            $this->fail($e->getMessage());
        }

        // Check if the default post title has been set
        $this->assertArrayHasKey('post_title', $insertArgs);
        $this->assertEquals('Einsatz', $insertArgs['post_title']);

        // The given post status should have been preserved
        $this->assertArrayHasKey('post_status', $insertArgs);
        $this->assertEquals($postStatus, $insertArgs['post_status']);

        // Check that the special flag defaults to 0
        $this->assertArrayHasKey('einsatz_special', $insertArgs['meta_input']);
        $this->assertEquals('0', $insertArgs['meta_input']['einsatz_special']);
    }

    public function testSanitizeBooleanValues()
    {
        $this->assertEquals('1', self::$helper->sanitizeBooleanValues('1'));
        $this->assertEquals('1', self::$helper->sanitizeBooleanValues('ja'));
        $this->assertEquals('1', self::$helper->sanitizeBooleanValues('Ja'));
        $this->assertEquals('0', self::$helper->sanitizeBooleanValues(''));
        $this->assertEquals('0', self::$helper->sanitizeBooleanValues('0'));
        $this->assertEquals('0', self::$helper->sanitizeBooleanValues('invalid'));
        $this->assertEquals('0', self::$helper->sanitizeBooleanValues('Nein'));
    }
}
