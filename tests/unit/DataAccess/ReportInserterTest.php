<?php
namespace abrain\Einsatzverwaltung\DataAccess;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use DateTimeImmutable;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\DataAccess\ReportInserter
 * @uses \abrain\Einsatzverwaltung\Types\ExtEinsatzmittel
 * @uses \abrain\Einsatzverwaltung\Types\IncidentType
 * @uses \abrain\Einsatzverwaltung\Types\Report
 * @uses \abrain\Einsatzverwaltung\Types\Vehicle
 */
class ReportInserterTest extends UnitTestCase
{
    /**
     * @throws ExpectationArgsRequired
     */
    public function testMinimalData()
    {
        $importObject = Mockery::mock('abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('getContent')->once()->andReturn('');
        $importObject->expects('getEndDateTime')->once()->andReturnNull();
        $importObject->expects('getKeyword')->once()->andReturn('');
        $importObject->expects('getLocation')->once()->andReturn('');
        $importObject->expects('getResources')->once()->andReturn([]);
        $importObject->expects('getStartDateTime')->once()->andReturn(new DateTimeImmutable('2021-08-29T17:51:15+0200'));
        $importObject->expects('getTitle')->once()->andReturn('Some title');

        expect('wp_insert_post')->once()->with(Mockery::capture($insertArgs), true)->andReturn(4122);

        $reportInserter = new ReportInserter();
        $this->assertEquals(4122, $reportInserter->insertReport($importObject));
        $this->assertEqualSets([
            'post_type' => 'einsatz',
            'post_status' => 'draft',
            'post_title' => 'Some title',
            'meta_input' => [
                '_einsatz_timeofalerting' => '2021-08-29 17:51:15',
                'einsatz_special' => 0,
            ],
            'tax_input' => []
        ], $insertArgs);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testCompletePublish()
    {
        $importObject = Mockery::mock('abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('getStartDateTime')->once()->andReturn(new DateTimeImmutable('2021-08-29T21:31:37+0200'));
        $importObject->expects('getTitle')->once()->andReturn('Some public title');
        $importObject->expects('getContent')->once()->andReturn('Some random post content');
        $importObject->expects('getKeyword')->once()->andReturn('The keyword');
        $importObject->expects('getLocation')->once()->andReturn('The location');
        $importObject->expects('getResources')->once()->andReturn(['resource', 'Resource 2', 'unknown', 'abc', 'def']);
        $importObject->expects('getEndDateTime')->once()->andReturn(new DateTimeImmutable('2021-08-29T21:34:42+0200'));

        // Incident Type exists already
        $term = Mockery::mock('\WP_Term');
        $term->term_id = 5123;
        expect('get_term_by')->once()->with('name', 'The keyword', 'einsatzart')->andReturn($term);

        // Resource look-up, first by name then by alias
        $resource1 = Mockery::mock('\WP_Term');
        $resource1->name = 'Resource 2';
        $resource1->term_id = 9023;
        $resource1->taxonomy = 'fahrzeug';
        $resource2 = Mockery::mock('\WP_Term');
        $resource2->name = 'resource';
        $resource2->term_id = 822;
        $resource2->taxonomy = 'exteinsatzmittel';
        $resource3 = Mockery::mock('\WP_Term');
        $resource3->term_id = 374;
        $resource3->taxonomy = 'fahrzeug';
        $resource4 = Mockery::mock('\WP_Term');
        $resource4->term_id = 2983;
        $resource4->taxonomy = 'exteinsatzmittel';
        expect('get_terms')->once()->with(Mockery::capture($byNameArgs))->andReturn([$resource1, $resource2]);
        expect('get_terms')->once()->with(Mockery::capture($byAliasArgs))->andReturn([$resource3, $resource4]);

        expect('wp_insert_post')->once()->with(Mockery::capture($insertArgs), true)->andReturn(91234);

        $reportInserter = new ReportInserter(true);
        $this->assertEquals(91234, $reportInserter->insertReport($importObject));

        // Check that get_terms was called with the right args to search by name
        $this->assertEqualSets([
            'name' => ['resource', 'Resource 2', 'unknown', 'abc', 'def'],
            'hide_empty' => false,
            'taxonomy' => ['fahrzeug', 'exteinsatzmittel']
        ], $byNameArgs);

        // Check that get_terms was called with the right args to search by alias
        $this->assertEqualSets([
            'hide_empty' => false,
            'taxonomy' => ['fahrzeug', 'exteinsatzmittel'],
            'meta_query' => [
                ['key' => 'altname', 'compare' => 'IN', 'value' => ['unknown', 'abc', 'def']]
            ]
        ], $byAliasArgs);

        // Check that wp_insert_post was called with the right args
        $this->assertEqualSets([
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'post_date' => '2021-08-29 21:31:37',
            'post_date_gmt' => '2021-08-29 19:31:37',
            'post_title' => 'Some public title',
            'post_content' => 'Some random post content',
            'meta_input' => [
                'einsatz_einsatzort' => 'The location',
                'einsatz_einsatzende' => '2021-08-29 21:34:42',
                'einsatz_special' => 0,
            ],
            'tax_input' => [
                'einsatzart' => [5123],
                'exteinsatzmittel' => [822, 2983],
                'fahrzeug' => [9023, 374]
            ]
        ], $insertArgs);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testKeywordCreation()
    {
        $importObject = Mockery::mock('abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('getContent')->once()->andReturn('');
        $importObject->expects('getEndDateTime')->once()->andReturnNull();
        $importObject->expects('getKeyword')->once()->andReturn('The keyword');
        $importObject->expects('getLocation')->once()->andReturn('');
        $importObject->expects('getResources')->once()->andReturn([]);
        $importObject->expects('getStartDateTime')->once()->andReturn(new DateTimeImmutable('2021-08-29T17:51:15+0200'));
        $importObject->expects('getTitle')->once()->andReturn('Some title');

        // Incident Type does not exist and has to be created
        expect('get_term_by')->once()->with('name', 'The keyword', 'einsatzart')->andReturn(false);
        expect('get_terms')->once()->with(Mockery::type('array'))->andReturn([]);
        expect('wp_insert_term')->once()->with('The keyword', 'einsatzart')->andReturn(['term_id' => 9384]);
        $term = Mockery::mock('\WP_Term');
        $term->term_id = 9384;
        expect('get_term')->once()->with(9384, 'einsatzart')->andReturn($term);

        expect('wp_insert_post')->once()->with(Mockery::capture($insertArgs), true)->andReturn(9114);

        $reportInserter = new ReportInserter();
        $this->assertEquals(9114, $reportInserter->insertReport($importObject));
        $this->assertEqualSets([
            'post_type' => 'einsatz',
            'post_status' => 'draft',
            'post_title' => 'Some title',
            'meta_input' => [
                '_einsatz_timeofalerting' => '2021-08-29 17:51:15',
                'einsatz_special' => 0,
            ],
            'tax_input' => [
                'einsatzart' => [9384]
            ]
        ], $insertArgs);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testKeywordCreationError()
    {
        $importObject = Mockery::mock('abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('getContent')->once()->andReturn('');
        $importObject->expects('getEndDateTime')->once()->andReturnNull();
        $importObject->expects('getKeyword')->once()->andReturn('A keyword');
        $importObject->expects('getStartDateTime')->once()->andReturn(new DateTimeImmutable());
        $importObject->expects('getTitle')->once()->andReturn('Some title');

        // Incident Type does not exist and creating it causes an error
        expect('get_term_by')->once()->with('name', 'A keyword', 'einsatzart')->andReturn(false);
        expect('get_terms')->once()->with(Mockery::type('array'))->andReturn([]);
        $wpError = Mockery::mock('\WP_Error');
        expect('wp_insert_term')->once()->with('A keyword', 'einsatzart')->andReturn($wpError);

        $reportInserter = new ReportInserter();
        $this->assertEquals($wpError, $reportInserter->insertReport($importObject));
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testFindIncidentCategoryByAlias()
    {
        $importObject = Mockery::mock('abrain\Einsatzverwaltung\Model\ReportInsertObject');
        $importObject->expects('getContent')->once()->andReturn('');
        $importObject->expects('getEndDateTime')->once()->andReturnNull();
        $importObject->expects('getKeyword')->once()->andReturn('Alternative keyword');
        $importObject->expects('getLocation')->once()->andReturn('');
        $importObject->expects('getResources')->once()->andReturn([]);
        $importObject->expects('getStartDateTime')->once()->andReturn(new DateTimeImmutable());
        $importObject->expects('getTitle')->once()->andReturn('');

        // Incident Type is not found by name, search by alias
        expect('get_term_by')->once()->with('name', 'Alternative keyword', 'einsatzart')->andReturn(false);
        $term1 = Mockery::mock('\WP_Term');
        $term1->term_id = 8451;
        $term2 = Mockery::mock('\WP_Term');
        $term2->term_id = 4561;
        expect('get_terms')->once()->with(Mockery::capture($getTermsArgs))->andReturn([$term1, $term2]);

        expect('wp_insert_post')->once()->with(Mockery::capture($insertArgs), true)->andReturn(9114);

        $reportInserter = new ReportInserter();
        $this->assertEquals(9114, $reportInserter->insertReport($importObject));
        $this->assertEqualSets([
            'taxonomy' => 'einsatzart',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'altname',
                    'value' => 'Alternative keyword'
                ]
            ]
        ], $getTermsArgs);
        $this->assertEquals([8451], $insertArgs['tax_input']['einsatzart']);
    }
}
