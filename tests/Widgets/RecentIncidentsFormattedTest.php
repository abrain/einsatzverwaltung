<?php
namespace abrain\Einsatzverwaltung\Widgets;

use WP_UnitTestCase;

/**
 * Test für Widget RecentIncidentsFormatted
 *
 * @author Andreas Brain
 */
class RecentIncidentsFormattedTest extends WP_UnitTestCase {

	function testUpdate() {
        $widget = new RecentIncidentsFormatted();
        $new = array(
            'title' => '',
            'numIncidents' => '',
            'beforeContent' => '',
            'pattern' => '',
            'afterContent' => ''
        );
        $old = array();
        $defaults = array(
            'title' => '',
            'numIncidents' => 3,
            'beforeContent' => '',
            'pattern' => '',
            'afterContent' => ''
        );

        $this->assertEquals($defaults, $widget->update($new, $old));

        $old = array(
            'title' => 'Alter Titel',
            'numIncidents' => 3,
            'beforeContent' => '<ul>',
            'pattern' => '<li>%title%</li>',
            'afterContent' => '</ul>'
        );
        $new['numIncidents'] = '-1';
        $new['beforeContent'] = '<h1>Nix</h1><p>Text<br><script></script><span>';
        $new['pattern'] = '<li><strong>%title%</strong><a href="#">Link</a><br></li>';
        $new['afterContent'] = '';
        $result = $widget->update($new, $old);
        $this->assertEquals($old['numIncidents'], $result['numIncidents']);
        $this->assertEquals('Nix<p>Text<br><span>', $result['beforeContent']);
        $this->assertEquals('<li>%title%<a href="#">Link</a><br></li>', $result['pattern']);
        $this->assertEquals('', $result['afterContent']);
	}
}
