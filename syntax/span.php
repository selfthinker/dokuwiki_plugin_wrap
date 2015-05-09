<?php
/**
 * Span Syntax Component of the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_wrap_span extends DokuWiki_Syntax_Plugin {
    static protected $import = NULL;
    protected $entry_pattern = '<span.*?>(?=.*?</span>)';
    protected $exit_pattern  = '</span>';

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
        $this->Lexer->addEntryPattern($this->entry_pattern,$mode,'plugin_wrap_'.$this->getPluginComponent());
    }

    function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern, 'plugin_wrap_'.$this->getPluginComponent());
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler &$handler){
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $data = strtolower(trim(substr($match,strpos($match,' '),-1)));
                return array($state, $data);

            case DOKU_LEXER_UNMATCHED :
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT :
                return array($state, '');

        }
        return false;
    }

    /**
     * Create output
     */
    function render($mode, Doku_Renderer &$renderer, $indata) {

        if (empty($indata)) return false;
        list($state, $data) = $indata;

        if($mode == 'xhtml'){
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $wrap =& plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data);

                    $renderer->doc .= '<span'.$attr.'>';
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</span>";
                    break;
            }
            return true;
        }
        if($mode == 'odt'){
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    // Get attributes.
                    $wrap =& plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data);

                    // Get class content and add 'dokuwiki' to it
                    preg_match ('/class=".*"/', $attr, $matches);
                    $class = substr ($matches [0], 6);
                    $class = trim ($class, ' "');
                    $class = 'dokuwiki '.$class;

                    // Import Wrap-CSS.
                    if ( self::$import == NULL ) {
                        self::$import =& plugin_load('helper', 'odt_cssimport');
                        self::$import->importFrom(DOKU_PLUGIN.'wrap/all.css');
                        self::$import->importFrom(DOKU_PLUGIN.'wrap/style.css');
                        self::$import->loadReplacements(DOKU_INC.DOKU_TPL.'style.ini');
                    }

                    if ( self::$import != NULL && 
                         method_exists ($renderer, '_odtSpanOpenUseCSS') === true ) {
                        $renderer->_odtSpanOpenUseCSS (self::$import, $class, DOKU_PLUGIN.'wrap/');
                    }
                    break;

                case DOKU_LEXER_EXIT:
                    // Close the span.
                    if ( self::$import != NULL && 
                         method_exists ($renderer, '_odtSpanClose') === true ) {
                        $renderer->_odtSpanClose();
                    }
                    break;
            }
            return true;
        }
        return false;
    }


}

