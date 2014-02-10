<?php
/**
 * Clear Float Syntax Component of the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Sahara Satoshi <sahara.satoshi@gmail.com>
 */

if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_wrap_clearfloat extends DokuWiki_Syntax_Plugin {

    function getType()  { return 'formatting';}
    function getPType() { return 'stack'; }
    function getSort()  { return 195; }
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('<WRAP clear *?\/>',$mode,'plugin_wrap_'.$this->getPluginComponent());
      $this->Lexer->addSpecialPattern('<block clear *?\/>',$mode,'plugin_wrap_'.$this->getPluginComponent());
      $this->Lexer->addSpecialPattern('<div clear *?\/>',$mode,'plugin_wrap_'.$this->getPluginComponent());
    }

 /**
  * Handle the match
  */
    public function handle($match, $state, $pos, &$handler){
        return array($state, $match);
    }

 /**
  * Create output
  */
    public function render($mode, &$renderer, $indata) {

        if (empty($indata)) return false;
        list($state, $data) = $indata;

        if ($mode == 'xhtml') {
            $renderer->doc.='<div class="wrap_clear plugin_wrap"><hr /></div>';
            return true;
        }
        return false;
    }
}