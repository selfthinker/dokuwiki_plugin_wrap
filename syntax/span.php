<?php
/**
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
include_once(dirname(__FILE__).'/../base.php');

class syntax_plugin_wrap_span extends syntax_plugin_wrap_base {

    function getInfo(){
        return array_merge(confToHash(dirname(__FILE__).'/../README'), array('name' => 'Span Component'));
    }

    function getType(){ return 'formatting';}
    function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    function getPType(){ return 'normal';}
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
        $this->Lexer->addEntryPattern('<wrap.*?>(?=.*?</wrap>)',$mode,'plugin_wrap_span');
        $this->Lexer->addEntryPattern('<inline.*?>(?=.*?</inline>)',$mode,'plugin_wrap_span');
        $this->Lexer->addEntryPattern('<span.*?>(?=.*?</span>)',$mode,'plugin_wrap_span');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</wrap>', 'plugin_wrap_span');
        $this->Lexer->addExitPattern('</inline>', 'plugin_wrap_span');
        $this->Lexer->addExitPattern('</span>', 'plugin_wrap_span');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $data = strtolower(trim(substr($match,5,-1)));
                return array($state, $data);

            case DOKU_LEXER_UNMATCHED :
                return array($state, $match);

            case DOKU_LEXER_EXIT :
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
                    $wrap = new syntax_plugin_wrap_base();
                    $attr = $wrap->buildAttributes($data);

                    $renderer->doc .= '<span'.$attr.'>';
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $renderer->_xmlEntities($data);
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</span>";
                    break;
            }
            return true;
        }
        return false;
    }


}

