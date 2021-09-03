<?php
namespace abrain\Einsatzverwaltung\DataAccess;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use DateTimeImmutable;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\DataAccess\ReportInserter
 * @uses \abrain\Einsatzverwaltung\Types\Report
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
        $importObject->expects('getLocation')->once()->andReturn('');
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
                '_einsatz_timeofalerting' => '2021-08-29 17:51:15'
            ]
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
        $importObject->expects('getLocation')->once()->andReturn('The location');
        $importObject->expects('getEndDateTime')->once()->andReturn(new DateTimeImmutable('2021-08-29T21:34:42+0200'));

        expect('wp_insert_post')->once()->with(Mockery::capture($insertArgs), true)->andReturn(91234);

        $reportInserter = new ReportInserter(true);
        $this->assertEquals(91234, $reportInserter->insertReport($importObject));
        $this->assertEqualSets([
            'post_type' => 'einsatz',
            'post_status' => 'publish',
            'post_date' => '2021-08-29 21:31:37',
            'post_date_gmt' => '2021-08-29 19:31:37',
            'post_title' => 'Some public title',
            'post_content' => 'Some random post content',
            'meta_input' => [
                'einsatz_einsatzort' => 'The location',
                'einsatz_einsatzende' => '2021-08-29 21:34:42'
            ]
        ], $insertArgs);
    }
}
