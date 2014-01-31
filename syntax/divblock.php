<?php
/**
 * Div Syntax Component of the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

require_once(dirname(__FILE__).'/div.php');

class syntax_plugin_wrap_divblock extends syntax_plugin_wrap_div {

    protected $entry_pattern = '<block.*?>(?=.*?</block>)';
    protected $exit_pattern  = '</block>';


}

