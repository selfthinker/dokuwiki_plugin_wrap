<?php
/**
 * Div Syntax Component of the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_wrap_div extends DokuWiki_Syntax_Plugin {

    function getType(){ return 'formatting';}
    function getAllowedTypes() { return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs'); }
    function getPType(){ return 'stack';}
    function getSort(){ return 195; }
    // override default accepts() method to allow nesting - ie, to get the plugin accepts its own entry syntax
    function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<WRAP.*?>(?=.*?</WRAP>)',$mode,'plugin_wrap_div');
        $this->Lexer->addEntryPattern('<block.*?>(?=.*?</block>)',$mode,'plugin_wrap_div');
        $this->Lexer->addEntryPattern('<div.*?>(?=.*?</div>)',$mode,'plugin_wrap_div');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</WRAP>', 'plugin_wrap_div');
        $this->Lexer->addExitPattern('</block>', 'plugin_wrap_div');
        $this->Lexer->addExitPattern('</div>', 'plugin_wrap_div');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $data = strtolower(trim(substr($match,strpos($match,' '),-1)));
                return array($state, $data);

            case DOKU_LEXER_UNMATCHED:
                // check if $match is a == header ==
                $headerMatch = preg_grep('/([ \t]*={2,}[^\n]+={2,}[ \t]*(?=))/msSi', array($match));
                if (empty($headerMatch)) {
                    $handler->_addCall('cdata', array($match), $pos);
                } else {
                    // if it's a == header ==, use the core header() renderer
                    // (copied from core header() in inc/parser/handler.php)
                    $title = trim($match);
                    $level = 7 - strspn($title,'=');
                    if($level < 1) $level = 1;
                    $title = trim($title,'=');
                    $title = trim($title);

                    $handler->_addCall('header',array($title,$level,$pos), $pos);
                }
                return false;

            case DOKU_LEXER_EXIT:
                return array($state, '');
        }
        return false;
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $indata) {

        if (empty($indata)) return false;
        list($state, $data) = $indata;

        if($mode == 'xhtml'){
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $wrap =& plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data, 'plugin_wrap');

                    $renderer->doc .= '<div'.$attr.'>';
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</div>";
                    break;
            }
            return true;
        }
        return false;
    }


}

