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
    static protected $import = NULL;
    static protected $boxes = array ('wrap_box', 'wrap_danger', 'wrap_warning', 'wrap_caution', 'wrap_notice', 'wrap_safety',
                                     'wrap_info', 'wrap_important', 'wrap_alert', 'wrap_tip', 'wrap_help', 'wrap_todo',
                                     'wrap_download', 'wrap_hi', 'wrap_spoiler');
    static protected $paragraphs = array ('wrap_leftalign', 'wrap_rightalign', 'wrap_centeralign', 'wrap_justify');
    protected $entry_pattern = '<div.*?>(?=.*?</div>)';
    protected $exit_pattern  = '</div>';
    protected $odt_ignore = false;

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
        $this->Lexer->addEntryPattern($this->entry_pattern,$mode,'plugin_wrap_'.$this->getPluginComponent());
    }

    function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern, 'plugin_wrap_'.$this->getPluginComponent());
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler &$handler){
        global $conf;
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
                    // close the section edit the header could open
                    if ($title && $level <= $conf['maxseclevel']) {
                        $handler->addPluginCall('wrap_closesection', array(), DOKU_LEXER_SPECIAL, $pos, '');
                    }
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
    function render($mode, Doku_Renderer &$renderer, $indata) {
        static $type_stack = array ();

        if (empty($indata)) return false;
        list($state, $data) = $indata;

        if($mode == 'xhtml'){
            /** @var Doku_Renderer_xhtml $renderer */
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    // add a section edit right at the beginning of the wrap output
                    $renderer->startSectionEdit(0, 'plugin_wrap_start');
                    $renderer->finishSectionEdit();
                    // add a section edit for the end of the wrap output. This prevents the renderer
                    // from closing the last section edit so the next section button after the wrap syntax will
                    // include the whole wrap syntax
                    $renderer->startSectionEdit(0, 'plugin_wrap_end');

                    $wrap =& plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data, 'plugin_wrap');

                    $renderer->doc .= '<div'.$attr.'>';
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= "</div>";
                    $renderer->finishSectionEdit();
                    break;
            }
            return true;
        }
        if($mode == 'odt'){
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    // Does CSS-Import exist?
                    if ( $this->odt_ignore === false && self::$import == NULL ) {
                        self::$import =& plugin_load('helper', 'odt_cssimport');
                        if ( self::$import != NULL ) {
                            self::$import->importFrom(DOKU_PLUGIN.'wrap/all.css');
                            self::$import->importFrom(DOKU_PLUGIN.'wrap/style.css');
                            self::$import->loadReplacements(DOKU_INC.DOKU_TPL.'style.ini');
                        } else {
                            // No specific ODT export, just plain content export.
                            $this->odt_ignore = true;
                        }
                    }

                    if ( $this->odt_ignore === true ) {
                        return false;
                    }

                    // Get attributes. Use the same mode as for XHTML.
                    $wrap =& plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data, 'plugin_wrap','xhtml');

                    // Get class content and add 'dokuwiki' to it
                    preg_match ('/class=".*"/', $attr, $matches);
                    $class = substr ($matches [0], 6);
                    $class = trim ($class, ' "');
                    $class = 'dokuwiki '.$class;

                    $columns = 0;
                    preg_match ('/wrap_col./', $attr, $matches);
                    if ( empty ($matches [0]) === false ) {
                        $columns = $matches [0] [strlen($matches [0])-1];
                    }

                    $is_box = false;
                    foreach (self::$boxes as $box) {
                        if ( strpos ($class, $box) !== false ) {
                            $is_box = true;
                            break;
                        }
                    }

                    $is_paragraph = false;
                    foreach (self::$paragraphs as $paragraph) {
                        if ( strpos ($class, $paragraph) !== false ) {
                            $is_paragraph = true;
                            break;
                        }
                    }

                    $is_pagebreak = false;
                    if ( strpos ($class, 'wrap_pagebreak') !== false ) {
                        $is_pagebreak = true;
                    }

                    // Is there support for ODT?
                    if ( $is_box === true || $columns > 0 || $is_paragraph === true) {
                        // Yes, import CSS data

                        // Get style content
                        preg_match ('/style=".*"/', $attr, $styles);
                        $style = substr ($styles [0], 6);
                        $style = trim ($style, ' "');
                    }

                    // Call corresponding functions for current wrap class
                    if ( $is_box === true ) {
                        $this->renderODTOpenBox ($renderer, $class, $style);
                        array_push ($type_stack, 'box');
                    } else {
                        if ( $columns > 0 ) {
                            $this->renderODTOpenColumns ($renderer, $class, $style);
                            array_push ($type_stack, 'multicolumn');
                        } else {
                            if ( $is_paragraph === true ) {
                                $this->renderODTOpenParagraph ($renderer, $class, $style);
                                array_push ($type_stack, 'p');
                            } else {
                                if ( $is_pagebreak === true ) {
                                    $renderer->pagebreak ();
                                } // No else here --> pagebreak has got no closing tag!
                                array_push ($type_stack, 'other');
                            }
                        }
                    }
                    break;

                case DOKU_LEXER_EXIT:
                    if ( $this->odt_ignore == true ) {
                        return false;
                    }
                    $type = array_pop ($type_stack);
                    if ( $type == 'box' ) {
                        $this->renderODTCloseBox ($renderer);
                    }
                    if ( $type == 'multicolumn' ) {
                        $this->renderODTCloseColumns($renderer);
                    }
                    if ( $type == 'p' ) {
                        $this->renderODTCloseParagraph($renderer);
                    }
                    break;
            }
            return true;
        }
        return false;
    }

    function renderODTOpenBox ($renderer, $class, $style) {
        if ( method_exists ($renderer, '_odtDivOpenAsFrameUseProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get properties for our class/element from imported CSS
        self::$import->getPropertiesForElement($properties, 'div', $class);

        // Interpret and add values from style to our properties
        $renderer->_processCSSStyle($properties, $style);

        // Adjust values for ODT
        foreach ($properties as $property => $value) {
            $properties [$property] = self::$import->adjustValueForODT ($value, 14);
        }
        if ( empty($properties ['background-image']) === false ) {
            $properties ['background-image'] =
                self::$import->replaceURLPrefix ($properties ['background-image'], DOKU_PLUGIN.'wrap/');
        }

        if ( empty($properties ['float']) === true ) {
            // If the float property is not set, set it to 'left' becuase the ODT plugin
            // would default to 'center' which is diffeent to the XHTML behaviour.
            $properties ['float'] = 'left';
        }

        if ( $properties ['display'] == 'none' ) {
            // Simulate onlyprint
            $properties ['display'] = 'printer';
        } else {
            $properties ['display'] = 'always';
        }

        $renderer->_odtDivOpenAsFrameUseProperties ($properties);
    }

    function renderODTCloseBox ($renderer) {
        if ( method_exists ($renderer, '_odtDivCloseAsFrame') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $renderer->_odtDivCloseAsFrame ();
    }

    function renderODTOpenColumns ($renderer, $class, $style) {
        if ( method_exists ($renderer, '_odtOpenMultiColumnFrame') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get properties for our class/element from imported CSS
        self::$import->getPropertiesForElement($properties, 'div', $class);

        // Interpret and add values from style to our properties
        $renderer->_processCSSStyle($properties, $style);

        // Adjust values for ODT
        foreach ($properties as $property => $value) {
            $properties [$property] = self::$import->adjustValueForODT ($value, 14);
        }

        $renderer->_odtOpenMultiColumnFrame($properties);
    }

    function renderODTCloseColumns ($renderer) {
        if ( method_exists ($renderer, '_odtCloseMultiColumnFrame') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $renderer->_odtCloseMultiColumnFrame();
    }

    function renderODTOpenParagraph ($renderer, $class, $style) {
        if ( method_exists ($renderer, '_odtParagraphOpenUseProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get properties for our class/element from imported CSS
        self::$import->getPropertiesForElement($properties, 'p', $class);

        // Interpret and add values from style to our properties
        $renderer->_processCSSStyle($properties, $style);

        // Adjust values for ODT
        foreach ($properties as $property => $value) {
            $properties [$property] = self::$import->adjustValueForODT ($value, 14);
        }

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

