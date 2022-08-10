<?php
/**
 * Alternate div syntax component for the wrap plugin
 *
 * Defines  <block> ... </block> syntax
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

class syntax_plugin_wrap_divblock extends syntax_plugin_wrap_div {

    protected $special_pattern = '<block\b[^>\r\n]*?/>';
    protected $entry_pattern   = '<block\b.*?>(?=.*?</block>)';
    protected $exit_pattern    = '</block>';


}

