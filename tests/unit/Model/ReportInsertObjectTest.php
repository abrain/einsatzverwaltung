<?php
namespace abrain\Einsatzverwaltung\Model;

use abrain\Einsatzverwaltung\UnitTestCase;
use DateTimeImmutable;

/**
 * @covers \abrain\Einsatzverwaltung\Model\ReportInsertObject
 * @uses \abrain\Einsatzverwaltung\Types\Report
 */
class ReportInsertObjectTest extends UnitTestCase
{
    public function testDefaultValues()
    {
        $startDate = new DateTimeImmutable('2021-09-03T16:37:05+0200');
        $importObject = new ReportInsertObject($startDate, 'Some title');
        $this->assertEquals('', $importObject->getContent());
        $this->assertEquals(null, $importObject->getEndDateTime());
        $this->assertEquals('', $importObject->getKeyword());
        $this->assertEquals('', $importObject->getLocation());
        $this->assertEquals($startDate, $importObject->getStartDateTime());
        $this->assertEquals('Some title', $importObject->getTitle());
    }

    public function testEmtpyTitle()
    {
        $importObject = new ReportInsertObject(new DateTimeImmutable(), '');
        $this->assertEquals('Incident', $importObject->getTitle());
    }

    public function testSetContent()
    {
        $importObject = new ReportInsertObject(new DateTimeImmutable(), '');
        $this->assertEquals('', $importObject->getContent());

        $importObject->setContent('This is the content');
        $this->assertEquals('This is the content', $importObject->getContent());
    }

    public function testSetKeyword()
    {
        $importObject = new ReportInsertObject(new DateTimeImmutable(), '');
        $this->assertEquals('', $importObject->getKeyword());

        $importObject->setKeyword('A keyword');
        $this->assertEquals('A keyword', $importObject->getKeyword());
    }

    public function testSetLocation()
    {
        $importObject = new ReportInsertObject(new DateTimeImmutable(), '');
        $this->assertEquals('', $importObject->getLocation());

        $importObject->setLocation('This is a location');
        $this->assertEquals('This is a location', $importObject->getLocation());
    }

    public function testSetEndDateTime()
    {
        $importObject = new ReportInsertObject(new DateTimeImmutable(), '');
        $this->assertNull($importObject->getEndDateTime());

        $endTime = new DateTimeImmutable();
        $importObject->setEndDateTime($endTime);
        $this->assertEquals($endTime, $importObject->getEndDateTime());
    }
}
