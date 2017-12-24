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

        parent::setUpBeforeClass();
    }

    public function testGetTaxInputStringForNT()
    {
        $input = 'term1, term78, term99';
        $result = self::$helper->getTaxInputString('nohierarchy', $input);
        $this->assertEquals($input, $result);
    }

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
}
