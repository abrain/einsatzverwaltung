<?php
namespace abrain\Einsatzverwaltung;

use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * Class UtilitiesTest
 * @covers \abrain\Einsatzverwaltung\Utilities
 * @package abrain\Einsatzverwaltung
 */
class UtilitiesTest extends UnitTestCase
{

    /**
     * @dataProvider dataForGetArrayValue
     * @param $array
     * @param $key
     * @param $defaultValue
     * @param $expected
     */
    public function testGetArrayValueIfKey($array, $key, $defaultValue, $expected)
    {
        $this->assertEquals($expected, Utilities::getArrayValueIfKey($array, $key, $defaultValue));
    }

    /**
     * @return array
     */
    public function dataForGetArrayValue(): array
    {
        return array(
            array(array(), 'key', 'defaultValue', 'defaultValue'),
            array(array('stringkey' => false, 3 => 'null'), '3', 'defaultValue', 'null'),
            array(array('stringkey' => false, 3 => 'null'), 3, 'defaultValue', 'null'),
            array(array('stringkey' => false, 3 => 'null'), 'stringkey', 'defaultValue', false)
        );
    }

    /**
     * @uses \abrain\Einsatzverwaltung\Types\Unit::compare()
     * @uses \abrain\Einsatzverwaltung\Types\Vehicle::compareVehicles()
     * @throws ExpectationArgsRequired
     */
    public function testGroupVehiclesByUnit()
    {
        $vehicle1 = Mockery::mock('\WP_Term');
        $vehicle1->term_id = 124;
        $vehicle1->name = 'A';
        expect('get_term_meta')->once()->with(124, 'vehicle_unit', true)->andReturn(53);
        expect('get_term_meta')->once()->with(124, 'vehicleorder', true)->andReturn(3);

        $vehicle2 = Mockery::mock('\WP_Term');
        $vehicle2->term_id = 6232;
        $vehicle2->name = 'B';
        expect('get_term_meta')->once()->with(6232, 'vehicle_unit', true)->andReturn(-1);

        $vehicle3 = Mockery::mock('\WP_Term');
        $vehicle3->term_id = 7632;
        $vehicle3->name = 'C';
        expect('get_term_meta')->once()->with(7632, 'vehicle_unit', true)->andReturn(53);
        expect('get_term_meta')->once()->with(7632, 'vehicleorder', true)->andReturn(2);

        $vehicle4 = Mockery::mock('\WP_Term');
        $vehicle4->term_id = 523;
        $vehicle4->name = 'D';
        expect('get_term_meta')->once()->with(523, 'vehicle_unit', true)->andReturn(991);

        $vehicle5 = Mockery::mock('\WP_Term');
        $vehicle5->term_id = 1928;
        $vehicle5->name = 'E';
        expect('get_term_meta')->once()->with(1928, 'vehicle_unit', true)->andReturn(false);

        $unit1 = Mockery::mock('\WP_Term');
        $unit1->term_id = 991;
        expect('get_term')->once()->with(991)->andReturn($unit1);
        expect('get_term_meta')->once()->with(991, 'unit_order', true)->andReturn(2);

        $unit2 = Mockery::mock('\WP_Term');
        $unit2->term_id = 53;
        expect('get_term')->once()->with(53)->andReturn($unit2);
        expect('get_term_meta')->once()->with(53, 'unit_order', true)->andReturn(1);

        self::assertEquals([
            53 => [$vehicle3, $vehicle1],
            991 => [$vehicle4],
            -1 => [$vehicle2, $vehicle5]
        ], Utilities::groupVehiclesByUnit([$vehicle1, $vehicle2, $vehicle3, $vehicle4, $vehicle5]));
    }

    public function testPrintError()
    {
        $utilities = new Utilities();
        $this->expectOutputRegex('/<p class="[^"]*error[^"]*">Some random string<\/p>/');
        $utilities->printError('Some random string');
    }

    public function testPrintInfo()
    {
        $utilities = new Utilities();
        $this->expectOutputRegex('/<p class="[^"]*info[^"]*">Some random string<\/p>/');
        $utilities->printInfo('Some random string');
    }

    public function testPrintSuccess()
    {
        $utilities = new Utilities();
        $this->expectOutputRegex('/<p class="[^"]*success[^"]*">Some random string<\/p>/');
        $utilities->printSuccess('Some random string');
    }

    public function testPrintWarning()
    {
        $utilities = new Utilities();
        $this->expectOutputRegex('/<p class="[^"]*warning[^"]*">Some random string<\/p>/');
        $utilities->printWarning('Some random string');
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testRemovePostFromCategory()
    {
        $postId = 24;
        expect('wp_get_post_categories')->once()->with($postId)->andReturn(array(22,33,99,123,150));
        expect('wp_set_post_categories')->once()->with($postId, array(22,33,123,150));
        Utilities::removePostFromCategory($postId, 99);
    }

    /**
     * If we want to remove a category that is not even set, nothing should be changed.
     * @throws ExpectationArgsRequired
     */
    public function testRemovePostFromCategoryBail()
    {
        $postId = 36;
        expect('wp_get_post_categories')->once()->with($postId)->andReturn(array(22,33,99,123,150));
        expect('wp_set_post_categories')->never();
        Utilities::removePostFromCategory($postId, 50);
    }

    public function testSanitizeCheckbox()
    {
        $this->assertEquals(0, Utilities::sanitizeCheckbox(''));
        $this->assertEquals(0, Utilities::sanitizeCheckbox(' '));
        $this->assertEquals(0, Utilities::sanitizeCheckbox('adsf'));
        $this->assertEquals(0, Utilities::sanitizeCheckbox(null));
        $this->assertEquals(0, Utilities::sanitizeCheckbox(false));
        $this->assertEquals(0, Utilities::sanitizeCheckbox('23'));
        $this->assertEquals(0, Utilities::sanitizeCheckbox(23));
        $this->assertEquals(1, Utilities::sanitizeCheckbox(true));
        $this->assertEquals(1, Utilities::sanitizeCheckbox('1'));
        $this->assertEquals(1, Utilities::sanitizeCheckbox(1));
    }
}
