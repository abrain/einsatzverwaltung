<?php
namespace abrain\Einsatzverwaltung;

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
    public function dataForGetArrayValue()
    {
        return array(
            array(array(), 'key', 'defaultValue', 'defaultValue'),
            array(array('stringkey' => false, 3 => 'null'), '3', 'defaultValue', 'null'),
            array(array('stringkey' => false, 3 => 'null'), 3, 'defaultValue', 'null'),
            array(array('stringkey' => false, 3 => 'null'), 'stringkey', 'defaultValue', false)
        );
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

    public function testRemovePostFromCategory()
    {
        $postId = 24;
        expect('wp_get_post_categories')->once()->with($postId)->andReturn(array(22,33,99,123,150));
        expect('wp_set_post_categories')->once()->with($postId, array(22,33,123,150));
        Utilities::removePostFromCategory($postId, 99);
    }

    /**
     * If we want to remove a category that is not even set, nothing should be changed.
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
