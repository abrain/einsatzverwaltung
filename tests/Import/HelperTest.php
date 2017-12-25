<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Core;
use Exception;

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
        require_once dirname(dirname(dirname(__FILE__))) . '/src/Import/Helper.php';

        self::$core = Core::getInstance();
        self::$helper = new Helper(self::$core->utilities, self::$core->options, self::$core->getData());

        register_taxonomy('hierarchy', array(), array('hierarchical' => true));
        register_taxonomy('nohierarchy', array(), array('hierarchical' => false));
        register_taxonomy('someTax', array(), array('hierarchical' => false));

        self::$helper->setMetaFields(array('someMetaField' => array('label' => 'Some meta field')));
        self::$helper->setPostFields(array(
            'somePostField' => array('label' => 'Some post field'),
            'anotherPostField' => array('label' => 'Another post field')
        ));
        self::$helper->setTaxonomies(array(
            'hierarchy' => array('label' => 'Hierarchical taxonomy'),
            'nohierarchy' => array('label' => 'Non-hierarchical taxonomy'),
            'someTax' => array('label' => 'Another non-hierarchical taxonomy')
        ));

        parent::setUpBeforeClass();
    }

    /**
     * Checks the generated string for tax_input for a non-hierarchical taxonomy.
     */
    public function testGetTaxInputStringForNT()
    {
        $input = 'term1, term78, term99';
        $result = self::$helper->getTaxInputString('nohierarchy', $input);
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
        $result = self::$helper->getTaxInputString('hierarchy', $input);
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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

    public function testFillInsertArgs()
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

        self::$helper->fillInsertArgs($mapping, $entry, $insertArgs);

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
}
