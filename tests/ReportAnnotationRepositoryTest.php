<?php
namespace abrain\Einsatzverwaltung\Tests;

use abrain\Einsatzverwaltung\Model\ReportAnnotation;
use abrain\Einsatzverwaltung\ReportAnnotationRepository;

class ReportAnnotationRepositoryTest extends \WP_UnitTestCase
{
    /**
     * @var ReportAnnotationRepository
     */
    private $repository;

    function setUp()
    {
        parent::setUp();
        $this->repository = new ReportAnnotationRepository();
    }

    public function testCreateReportAnnotation()
    {
        self::assertCount(0, $this->repository->getAnnotations());
        $this->repository->addAnnotation(new ReportAnnotation('dings', 'Dings', '', 'camera', 'aktiv', 'inaktiv'));
        self::assertCount(1, $this->repository->getAnnotations());
        self::assertArrayHasKey('dings', $this->repository->getAnnotations());
        self::assertContains('dings', $this->repository->getAnnotationIdentifiers());
    }
}
