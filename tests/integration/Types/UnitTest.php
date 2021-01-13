<?php
namespace abrain\Einsatzverwaltung\Types;

use PHPUnit_Framework_TestCase;
use function get_taxonomy;

class UnitTest extends PHPUnit_Framework_TestCase
{
    public function testTypeExists()
    {
        $taxonomy = get_taxonomy(Unit::getSlug());
        $this->assertNotNull($taxonomy);
    }
}
