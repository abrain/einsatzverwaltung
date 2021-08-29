<?php
namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\UnitTestCase;
use DateTimeImmutable;

/**
 * @covers \abrain\Einsatzverwaltung\Model\ReportImportObject
 * @uses \abrain\Einsatzverwaltung\Types\Report
 */
class ReportImportObjectTest extends UnitTestCase
{
    public function testMinimalData()
    {
        $importObject = new ReportImportObject(new DateTimeImmutable('2021-08-29T17:51:15+0200'), 'Some title');
        $this->assertEquals([
            'post_type' => 'einsatz',
            'post_status' => 'draft',
            'post_title' => 'Some title',
            'meta_input' => [
                '_einsatz_timeofalerting' => '2021-08-29 17:51:15'
            ]
        ], $importObject->getInsertArgs());
    }

    public function testCompletePublish()
    {
        $importObject = new ReportImportObject(new DateTimeImmutable('2021-08-29T21:31:37+0200'), 'Some public title');
        $importObject->setContent('Some random post content');
        $importObject->setLocation('The location');
        $importObject->setEndTime(new DateTimeImmutable('2021-08-29T21:34:42+0200'));

        $this->assertEquals([
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
        ], $importObject->getInsertArgs(true));
    }

    public function testEmptyStrings()
    {
        $importObject = new ReportImportObject(new DateTimeImmutable(), '');
        $importObject->setContent('');
        $importObject->setLocation('');
        $insertArgs = $importObject->getInsertArgs();
        $this->assertEquals('Incident', $insertArgs['post_title']);
        $this->assertArrayNotHasKey('post_content', $insertArgs);
        $this->assertArrayNotHasKey('einsatz_einsatzort', $insertArgs['meta_input']);
    }
}
