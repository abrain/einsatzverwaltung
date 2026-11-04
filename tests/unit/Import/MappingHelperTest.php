<?php
namespace abrain\Einsatzverwaltung\Import;

use abrain\Einsatzverwaltung\Exceptions\ImportCheckException;
use abrain\Einsatzverwaltung\Stubs\ImportSource;
use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * @covers \abrain\Einsatzverwaltung\Import\MappingHelper
 * @covers \abrain\Einsatzverwaltung\Import\Sources\AbstractSource
 * @uses \abrain\Einsatzverwaltung\ReportNumberController
 */
class MappingHelperTest extends UnitTestCase
{
    /** @var MappingHelper */
    private $helper;

    /** @var array Simulate $_POST data for filter_input */
    private $postData = [];

    /** @var bool */
    private $autoIncidentNumbers = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new MappingHelper();
        $this->postData = [];
        $this->autoIncidentNumbers = false;

        Functions\when('filter_input')->alias(function ($type, $name) {
            return $this->postData[$name] ?? null;
        });
        Functions\when('get_option')->alias(function ($option, $default = false) {
            if ($option === 'einsatzverwaltung_incidentnumbers_auto') {
                return $this->autoIncidentNumbers ? '1' : '0';
            }
            return $default;
        });
    }

    /**
     * @param string[] $fields
     * @param array $autoMatch sourceField => ownField
     * @param array $unmatchable
     * @return ImportSource
     */
    private function createSource(array $fields, array $autoMatch = [], array $unmatchable = []): ImportSource
    {
        return new ImportSource($fields, $autoMatch, $unmatchable);
    }

    public function testGetMappingMapsSelectedFields()
    {
        $source = $this->createSource(['Datum', 'Ort']);
        $this->postData = ['stub-field0' => 'post_date', 'stub-field1' => 'einsatz_einsatzort'];
        $ownFields = ['post_date' => 'Alarmzeit', 'einsatz_einsatzort' => 'Einsatzort'];

        $mapping = $this->helper->getMapping($source, $ownFields);

        $this->assertSame(['Datum' => 'post_date', 'Ort' => 'einsatz_einsatzort'], $mapping);
    }

    public function testGetMappingSkipsEmptyDashAndMissingValues()
    {
        $source = $this->createSource(['A', 'B', 'C', 'D']);
        $this->postData = ['stub-field0' => '', 'stub-field1' => '-', 'stub-field2' => 'post_date'];
        // 'D' is not part of the POST data
        $ownFields = ['post_date' => 'Alarmzeit'];

        $mapping = $this->helper->getMapping($source, $ownFields);

        $this->assertSame(['C' => 'post_date'], $mapping);
    }

    public function testGetMappingSkipsNonStringValues()
    {
        $source = $this->createSource(['A']);
        $this->postData = ['stub-field0' => ['post_date']];

        $mapping = $this->helper->getMapping($source, ['post_date' => 'Alarmzeit']);

        $this->assertSame([], $mapping);
    }

    public function testGetMappingThrowsOnUnknownOwnField()
    {
        $source = $this->createSource(['A']);
        $this->postData = ['stub-field0' => 'does_not_exist'];

        $this->expectException(ImportCheckException::class);
        $this->expectExceptionMessage('Unknown field: does_not_exist');

        $this->helper->getMapping($source, ['post_date' => 'Alarmzeit']);
    }

    public function testGetMappingAddsAutoMatchFields()
    {
        $source = $this->createSource(['A'], ['Nummer' => 'einsatz_incidentNumber']);
        $this->postData = ['stub-field0' => 'post_date'];
        $ownFields = ['post_date' => 'Alarmzeit', 'einsatz_incidentNumber' => 'Einsatznummer'];

        $mapping = $this->helper->getMapping($source, $ownFields);

        $this->assertSame(['A' => 'post_date', 'Nummer' => 'einsatz_incidentNumber'], $mapping);
    }

    public function testGetMappingAutoMatchOverridesUserSelection()
    {
        $source = $this->createSource(['Nummer'], ['Nummer' => 'einsatz_incidentNumber']);
        $this->postData = ['stub-field0' => 'post_date'];
        $ownFields = ['post_date' => 'Alarmzeit', 'einsatz_incidentNumber' => 'Einsatznummer'];

        $mapping = $this->helper->getMapping($source, $ownFields);

        $this->assertSame(['Nummer' => 'einsatz_incidentNumber'], $mapping);
    }

    public function testGetMappingWithoutFieldsReturnsEmptyArray()
    {
        $source = $this->createSource([]);

        $this->assertSame([], $this->helper->getMapping($source, []));
    }

    public function testValidateMappingThrowsWithoutPostDate()
    {
        $source = $this->createSource(['A']);

        $this->expectException(ImportCheckException::class);
        $this->expectExceptionMessage('Pflichtfeld Alarmzeit wurde nicht zugeordnet');

        $this->helper->validateMapping(['A' => 'einsatz_einsatzort'], $source);
    }

    public function testValidateMappingThrowsOnEmptyMapping()
    {
        $this->expectException(ImportCheckException::class);

        $this->helper->validateMapping([], $this->createSource([]));
    }

    public function testValidateMappingAcceptsValidMapping()
    {
        $source = $this->createSource(['A', 'B']);

        $this->helper->validateMapping(['A' => 'post_date', 'B' => 'einsatz_einsatzort'], $source);

        $this->addToAssertionCount(1); // no error means success
    }

    public function testValidateMappingThrowsOnUnmatchableTarget()
    {
        $source = $this->createSource(['A', 'B'], [], ['einsatz_internal']);

        $this->expectException(ImportCheckException::class);
        $this->expectExceptionMessage('Feld einsatz_internal kann nicht');

        $this->helper->validateMapping(['A' => 'post_date', 'B' => 'einsatz_internal'], $source);
    }

    public function testValidateMappingAllowsUnmatchableFieldIfAutoMatched()
    {
        $source = $this->createSource(['A'], ['Nummer' => 'einsatz_incidentNumber']);

        $this->helper->validateMapping(
            ['A' => 'post_date', 'Nummer' => 'einsatz_incidentNumber'],
            $source
        );

        $this->addToAssertionCount(1);
    }

    public function testValidateMappingRejectsIncidentNumberWhenAutoNumberingIsActive()
    {
        $this->autoIncidentNumbers = true;
        $source = $this->createSource(['A', 'B']);

        $this->expectException(ImportCheckException::class);
        $this->expectExceptionMessage('Feld einsatz_incidentNumber kann nicht');

        $this->helper->validateMapping(['A' => 'post_date', 'B' => 'einsatz_incidentNumber'], $source);
    }

    public function testValidateMappingAllowsIncidentNumberWithoutAutoNumbering()
    {
        $this->autoIncidentNumbers = false;
        $source = $this->createSource(['A', 'B']);

        $this->helper->validateMapping(['A' => 'post_date', 'B' => 'einsatz_incidentNumber'], $source);

        $this->addToAssertionCount(1);
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Model\IncidentReport
     */
    public function testValidateMappingThrowsOnDuplicateTargets()
    {

        $source = $this->createSource(['A', 'B', 'C']);

        $this->expectException(ImportCheckException::class);

        $this->helper->validateMapping(
            ['A' => 'post_date', 'B' => 'einsatz_einsatzort', 'C' => 'einsatz_einsatzort'],
            $source
        );
    }
}
