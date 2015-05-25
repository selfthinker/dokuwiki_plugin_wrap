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
    function handle($match, $state, $pos, Doku_Handler $handler){
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
    function render($mode, Doku_Renderer $renderer, $indata) {
        static $type_stack = array ();

        if (empty($indata)) return false;
        list($state, $data) = $indata;

        if($mode == 'xhtml'){
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $wrap = plugin_load('helper', 'wrap');
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
                    $wrap = plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data);

                    // Get class content and add 'dokuwiki' to it
                    preg_match ('/class=".*"/', $attr, $matches);
                    $class = substr ($matches [0], 6);
                    $class = trim ($class, ' "');
                    $class = 'dokuwiki '.$class;

                    // Get style content
                    preg_match ('/style=".*"/', $attr, $styles);
                    $style = substr ($styles [0], 6);
                    $style = trim ($style, ' "');

                    // Get language
                    preg_match ('/lang="([a-zA-Z]|-)+"/', $attr, $languages);
                    $language = substr ($languages [0], 6);
                    $language = trim ($language, ' "');

                    $is_indent = false;
                    $is_outdent = false;
                    if ( strpos ($class, 'wrap_indent') !== false ) {
                        $is_indent = true;
                    }
                    if ( strpos ($class, 'wrap_outdent') !== false ) {
                        $is_outdent = true;
                    }

                    if ( $is_indent === false && $is_outdent === false ) {
                        $this->renderODTOpenSpan ($renderer, $class, $style, $language);
                        array_push ($type_stack, 'span');
                    } else {
                        $this->renderODTOpenParagraph ($renderer, $class, $style, $language, $is_indent, $is_outdent);
                        array_push ($type_stack, 'paragraph');
                    }
                    break;

                case DOKU_LEXER_EXIT:
                    $type = array_pop ($type_stack);
                    
                    if ( $type == 'span' ) {
                        $this->renderODTCloseSpan($renderer);
                    }
                    if ( $type == 'paragraph' ) {
                        $this->renderODTCloseParagraph ($renderer);
                    }
                    break;
            }
            return true;
        }
        return false;
    }

    function renderODTOpenSpan ($renderer, $class, $style, $language) {
        $properties = array ();

        if ( method_exists ($renderer, 'getODTProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get CSS properties for ODT export.
        $renderer->getODTProperties ($properties, 'span', $class, $style);

        if ( empty($properties ['background-image']) === false ) {
            $properties ['background-image'] =
                $renderer->replaceURLPrefix ($properties ['background-image'], DOKU_INC);
        }

        if ( empty($language) === false ) {
            $properties ['lang'] = $language;
        }

        $renderer->_odtSpanOpenUseProperties($properties);
    }

    function renderODTCloseSpan ($renderer) {
        if ( method_exists ($renderer, '_odtSpanClose') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $renderer->_odtSpanClose();
    }

    function renderODTOpenParagraph ($renderer, $class, $style, $language, $is_indent, $is_outdent) {
        $properties = array ();

        if ( method_exists ($renderer, 'getODTProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get CSS properties for ODT export.
        $renderer->getODTProperties ($properties, 'p', $class, $style);

        if ( empty($properties ['background-image']) === false ) {
            $properties ['background-image'] =
                $renderer->replaceURLPrefix ($properties ['background-image'], DOKU_INC);
        }

        if ( empty($language) === false ) {
            $properties ['lang'] = $language;
        }

        if ( $is_indent === true ) {
            // FIXME: Has to be adjusted if test direction will be supported.
            // See all.css
            $properties ['text-indent'] = $properties ['padding-left'];
            $properties ['padding-left'] = 0;
        }
        if ( $is_outdent === true ) {
            // FIXME: Has to be adjusted if text (RTL, LTR) direction will be supported.
            // See all.css
            $properties ['text-indent'] = $properties ['margin-left'];
            $properties ['margin-left'] = 0;
        }

        $renderer->p_close();
        $renderer->_odtParagraphOpenUseProperties($properties);
    }

    function renderODTCloseParagraph ($renderer) {
        if ( method_exists ($renderer, 'p_close') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $renderer->p_close();
    }
}

