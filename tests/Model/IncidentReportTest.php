<?php
namespace abrain\Einsatzverwaltung\Model;

/**
 * Class IncidentReportTest
 * @author Andreas Brain
 * @coversDefaultClass abrain\Einsatzverwaltung\Model\IncidentReport
 * @package abrain\Einsatzverwaltung\Model
 */
class IncidentReportTest extends \WP_UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getPostId
     */
    public function testConstruct()
    {
        // Standardmäßig sollte kein Post gesetzt sein
        $emptyIR = new IncidentReport();
        $this->assertFalse($emptyIR->getPostId());

        // Normale Beiträge sollten nicht angenommen werden
        $post = $this->factory->post->create_and_get();
        $ir0 = new IncidentReport($post->ID);
        $this->assertFalse($ir0->getPostId());
        $ir1 = new IncidentReport($post);
        $this->assertFalse($ir1->getPostId());

        // Einsatzberichte werden als Beitrag gesetzt
        $einsatz = $this->factory->post->create_and_get(array('post_type' => 'einsatz'));
        $ir2 = new IncidentReport($einsatz);
        $this->assertEquals($einsatz->ID, $ir2->getPostId());
        $ir3 = new IncidentReport($einsatz->ID);
        $this->assertEquals($einsatz->ID, $ir3->getPostId());

        // Unsisnn wird ignoriert
        $this->setExpectedIncorrectUsage('__construct');
        $ir4 = new IncidentReport('bla');
        $this->assertFalse($ir4->getPostId());
    }
}
