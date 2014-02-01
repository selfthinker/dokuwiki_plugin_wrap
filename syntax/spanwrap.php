<?php
/**
 * Alternate span syntax component for the wrap plugin
 *
 * Defines  <wrap> ... </wrap> syntax
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

require_once(dirname(__FILE__).'/span.php');

class syntax_plugin_wrap_spanwrap extends syntax_plugin_wrap_span {

    protected $entry_pattern = '<wrap.*?>(?=.*?</wrap>)';
    protected $exit_pattern  = '</wrap>';


}

