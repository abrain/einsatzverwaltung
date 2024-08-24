<?php

namespace abrain\Einsatzverwaltung;

use Mockery;
use function Brain\Monkey\Actions\expectAdded;

/**
 * @covers \abrain\Einsatzverwaltung\Frontend
 */
class FrontendTest extends UnitTestCase
{
    public function testAddsHooks()
    {
        expectAdded('awb_readd_third_party_the_content_changes')->once()->with(Mockery::type('Closure'), 99);
        expectAdded('awb_remove_third_party_the_content_changes')->once()->with(Mockery::type('Closure'), 5);

        $options = Mockery::mock('abrain\Einsatzverwaltung\Options');
        $formatter = Mockery::mock('abrain\Einsatzverwaltung\Util\Formatter');
        $frontend = new Frontend($options, $formatter);
        $frontend->addHooks();

        self::assertSame(10, has_action('pre_get_posts', 'abrain\Einsatzverwaltung\Frontend->addReportsToQuery()'));
        self::assertSame(10, has_action('wp_enqueue_scripts', 'abrain\Einsatzverwaltung\Frontend->enqueueStyleAndScripts()'));
        self::assertSame(10, has_filter('default_post_metadata', 'abrain\Einsatzverwaltung\Frontend->filterDefaultThumbnail()'));
        self::assertSame(9, has_filter('the_content', 'abrain\Einsatzverwaltung\Frontend->renderContent()'));
        self::assertSame(10, has_filter('the_excerpt', 'abrain\Einsatzverwaltung\Frontend->filterEinsatzExcerpt()'));
        self::assertSame(10, has_filter('the_excerpt_rss', 'abrain\Einsatzverwaltung\Frontend->filterEinsatzExcerpt()'));
        self::assertSame(10, has_filter('the_excerpt_embed', 'abrain\Einsatzverwaltung\Frontend->filterEinsatzExcerpt()'));
    }

    public function testDoesNotDisturbAllInOneEventCalendar()
    {
        $_REQUEST['plugin'] = 'all-in-one-event-calendar';
        $_REQUEST['action'] = 'export_events';

        $options = Mockery::mock('abrain\Einsatzverwaltung\Options');
        $formatter = Mockery::mock('abrain\Einsatzverwaltung\Util\Formatter');
        $frontend = new Frontend($options, $formatter);
        $frontend->addHooks();

        $this->assertFalse(has_filter('the_content'));
    }
}
