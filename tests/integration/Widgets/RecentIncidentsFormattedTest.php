<?php
namespace abrain\Einsatzverwaltung\Widgets;

use abrain\Einsatzverwaltung\Core;
use WP_UnitTestCase;

/**
 * Test für Widget RecentIncidentsFormatted
 *
 * @author Andreas Brain
 */
class RecentIncidentsFormattedTest extends WP_UnitTestCase
{
    public function testUpdate()
    {
        $core = Core::getInstance();
        $widget = new RecentIncidentsFormatted($core->formatter);
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
            'units' => array(),
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
        $new['numIncidents'] = 'abc';
        $new['beforeContent'] = '<h1>Nix</h1><p>Text<br><script></script><span>';
        $new['pattern'] = '<li><strong>%title%</strong><a href="#">Link</a><br><table></table></li>';
        $new['afterContent'] = '';
        $result = $widget->update($new, $old);
        $this->assertEquals($old['numIncidents'], $result['numIncidents']);
        $this->assertEquals('Nix<p>Text<br><span>', $result['beforeContent']);
        $this->assertEquals('<li><strong>%title%</strong><a href="#">Link</a><br></li>', $result['pattern']);
        $this->assertEquals('', $result['afterContent']);
    }
}
