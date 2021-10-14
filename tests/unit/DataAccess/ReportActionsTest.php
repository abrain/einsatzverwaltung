<?php
namespace abrain\Einsatzverwaltung\DataAccess;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Actions\expectAdded;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\DataAccess\ReportActions
 * @uses \abrain\Einsatzverwaltung\Types\Unit
 * @uses \abrain\Einsatzverwaltung\Types\Vehicle
 */
class ReportActionsTest extends UnitTestCase
{
    public function testAddHooks()
    {
        expectAdded('save_post_einsatz');
        $reportActions = new ReportActions();
        $reportActions->addHooks();
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testAddMissingUnitsNoVehicles()
    {
        expect('wp_get_post_terms')->once()->with(47, 'fahrzeug')->andReturn([]);

        $reportActions = new ReportActions();
        $reportActions->addMissingUnits(47);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testAddMissingUnitsNoUnitsExpected()
    {
        $vehicle1 = Mockery::mock('\WP_Term');
        $vehicle1->term_id = 238;
        $vehicle2 = Mockery::mock('\WP_Term');
        $vehicle2->term_id = 12;
        expect('wp_get_post_terms')->once()->with(48, 'fahrzeug')->andReturn([$vehicle1, $vehicle2]);
        expect('get_term_meta')->once()->with(238, 'vehicle_unit', true)->andReturn('');
        expect('get_term_meta')->once()->with(12, 'vehicle_unit', true)->andReturn('');

        $reportActions = new ReportActions();
        $reportActions->addMissingUnits(48);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testAddMissingUnitsNoUnitsToAdd()
    {
        $vehicle1 = Mockery::mock('\WP_Term');
        $vehicle1->term_id = 41;
        $vehicle2 = Mockery::mock('\WP_Term');
        $vehicle2->term_id = 42;
        $vehicle3 = Mockery::mock('\WP_Term');
        $vehicle3->term_id = 43;
        $vehicle4 = Mockery::mock('\WP_Term');
        $vehicle4->term_id = 44;
        expect('wp_get_post_terms')->once()->with(49, 'fahrzeug')->andReturn([$vehicle1, $vehicle2, $vehicle3, $vehicle4]);
        expect('get_term_meta')->once()->with(41, 'vehicle_unit', true)->andReturn('55');
        expect('get_term_meta')->once()->with(42, 'vehicle_unit', true)->andReturn('');
        expect('get_term_meta')->once()->with(43, 'vehicle_unit', true)->andReturn('67');
        expect('get_term_meta')->once()->with(44, 'vehicle_unit', true)->andReturn('55');

        $unit1 = Mockery::mock('\WP_Term');
        $unit1->term_id = 55;
        $unit2 = Mockery::mock('\WP_Term');
        $unit2->term_id = 67;
        expect('wp_get_post_terms')->once()->with(49, 'evw_unit')->andReturn([$unit1, $unit2]);

        // wp_set_post_terms should not be called, because the expected unit IDs are already set
        expect('wp_set_post_terms')->never();

        $reportActions = new ReportActions();
        $reportActions->addMissingUnits(49);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testAddMissingUnits()
    {
        $vehicle1 = Mockery::mock('\WP_Term');
        $vehicle1->term_id = 41;
        $vehicle2 = Mockery::mock('\WP_Term');
        $vehicle2->term_id = 42;
        $vehicle3 = Mockery::mock('\WP_Term');
        $vehicle3->term_id = 43;
        $vehicle4 = Mockery::mock('\WP_Term');
        $vehicle4->term_id = 44;
        expect('wp_get_post_terms')->once()->with(50, 'fahrzeug')->andReturn([$vehicle1, $vehicle2, $vehicle3, $vehicle4]);
        expect('get_term_meta')->once()->with(41, 'vehicle_unit', true)->andReturn('55');
        expect('get_term_meta')->once()->with(42, 'vehicle_unit', true)->andReturn('');
        expect('get_term_meta')->once()->with(43, 'vehicle_unit', true)->andReturn('67');
        expect('get_term_meta')->once()->with(44, 'vehicle_unit', true)->andReturn('55');
        expect('wp_get_post_terms')->once()->with(50, 'evw_unit')->andReturn([]);
        expect('wp_set_post_terms')->once()->with(50, Mockery::capture($unitIdsToAdd), 'evw_unit', true);

        $reportActions = new ReportActions();
        $reportActions->addMissingUnits(50);

        $this->assertEqualSets([55, 67], $unitIdsToAdd);
    }
}
