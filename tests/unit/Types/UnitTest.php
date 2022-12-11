<?php

namespace abrain\Einsatzverwaltung\Types;

use abrain\Einsatzverwaltung\UnitTestCase;
use Brain\Monkey\Expectation\Exception\ExpectationArgsRequired;
use Mockery;
use function Brain\Monkey\Functions\expect;

/**
 * @covers \abrain\Einsatzverwaltung\Types\Unit
 * @covers \abrain\Einsatzverwaltung\Types\CustomTaxonomy
 */
class UnitTest extends UnitTestCase
{
    public function testIgnoresForeignColumns()
    {
        $unit = new Unit();
        $columnContent = $unit->onTaxonomyColumnContent('content to filter', 'not_our_column', 12);
        $this->assertEquals('content to filter', $columnContent);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testInfoUrlIsEmpty()
    {
        $term = Mockery::mock('\WP_Term');
        $term->term_id = 523;

        expect('get_term')->once()->with($term->term_id)->andReturn($term);
        expect('get_term_meta')->once()->with($term->term_id, 'unit_exturl', true)->andReturn('');
        expect('get_term_meta')->once()->with($term->term_id, 'unit_pid', true)->andReturn('');

        $unit = new Unit();
        $columnContent = $unit->onTaxonomyColumnContent('content', 'unit_pid', $term->term_id);
        $this->assertEquals('', $columnContent);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testInfoUrlPrefersExternalUrl()
    {
        $term = Mockery::mock('\WP_Term');
        $term->term_id = 286;

        expect('get_term')->once()->with($term->term_id)->andReturn($term);
        expect('get_term_meta')->once()->with($term->term_id, 'unit_exturl', true)->andReturn('https://example.org');
        expect('url_to_postid')->once()->with('https://example.org')->andReturn(0);

        $unit = new Unit();
        $columnContent = $unit->onTaxonomyColumnContent('content', 'unit_pid', $term->term_id);
        $this->assertEquals('<a href="https://example.org">External URL</a>', $columnContent);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testInfoUrlPageOnly()
    {
        $term = Mockery::mock('\WP_Term');
        $term->term_id = 7634;

        expect('get_term')->once()->with($term->term_id)->andReturn($term);
        expect('get_term_meta')->once()->with($term->term_id, 'unit_exturl', true)->andReturn('');
        expect('get_term_meta')->once()->with($term->term_id, 'unit_pid', true)->andReturn(6685);
        expect('get_permalink')->once()->with(6685)->andReturn('https://example.com/some-page');
        expect('url_to_postid')->once()->with('https://example.com/some-page')->andReturn(6685);
        expect('get_the_title')->once()->with(6685)->andReturn('Awesome title');

        $unit = new Unit();
        $columnContent = $unit->onTaxonomyColumnContent('content', 'unit_pid', $term->term_id);
        $this->assertEquals('<a href="https://example.com/some-page">Awesome title</a>', $columnContent);
    }

    /**
     * @throws ExpectationArgsRequired
     */
    public function testInfoUrlInvalidPostID()
    {
        $term = Mockery::mock('\WP_Term');
        $term->term_id = 7634;

        expect('get_term')->once()->with($term->term_id)->andReturn($term);
        expect('get_term_meta')->once()->with($term->term_id, 'unit_exturl', true)->andReturn('');
        expect('get_term_meta')->once()->with($term->term_id, 'unit_pid', true)->andReturn(6685);
        expect('get_permalink')->once()->with(6685)->andReturn(false);

        $unit = new Unit();
        $columnContent = $unit->onTaxonomyColumnContent('content', 'unit_pid', $term->term_id);
        $this->assertEquals('', $columnContent);
    }
}
