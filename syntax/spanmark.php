<?php
/**
 * Mark (highlight) syntax component for the wrap plugin
 *
 * Defines  <mark> ... </mark> syntax
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 * @author     Sascha Leib <sascha.leib(at)kolmio.com>
 */

class syntax_plugin_wrap_spanmark extends syntax_plugin_wrap_span {

    protected $special_pattern = '<mark\b[^>\r\n]*?/>';
    protected $entry_pattern   = '<mark\b.*?>(?=.*?</mark>)';
    protected $exit_pattern    = '</mark>';
	protected $output_tag      = 'mark';

}