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
    static protected $column_count = 0;
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
        $this->Lexer->addPattern('[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)', 'plugin_wrap_'.$this->getPluginComponent());
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $conf;
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $data = strtolower(trim(substr($match,strpos($match,' '),-1)));
                return array($state, $data);

            case DOKU_LEXER_UNMATCHED:
                $handler->_addCall('cdata', array($match), $pos);
                break;

            case DOKU_LEXER_MATCHED:
                // we have a == header ==, use the core header() renderer
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
                break;

            case DOKU_LEXER_EXIT:
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

                    $wrap = plugin_load('helper', 'wrap');
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
                        self::$import = plugin_load('helper', 'odt_cssimport');
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
                    $wrap = plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data, 'plugin_wrap','xhtml');

                    // Get class content and add 'dokuwiki' to it
                    preg_match ('/class=".*"/', $attr, $matches);
                    $class = substr ($matches [0], 6);
                    $class = trim ($class, ' "');
                    $class = 'dokuwiki '.$class;

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

                    // Check for multicolumns
                    $columns = 0;
                    preg_match ('/wrap_col\d/', $attr, $matches);
                    if ( empty ($matches [0]) === false ) {
                        $columns = $matches [0] [strlen($matches [0])-1];
                    }

                    // Check for column (single column, part of a table)
                    $is_column = false;
                    if ( strpos ($class, 'wrap_column') !== false ) {
                        $is_column = true;
                    }

                    // Check for group
                    $is_group = false;
                    if ( strpos ($class, 'group') !== false ) {
                        $is_group = true;
                    }

                    // Check for boxes
                    $is_box = false;
                    foreach (self::$boxes as $box) {
                        if ( strpos ($class, $box) !== false ) {
                            $is_box = true;
                            break;
                        }
                    }

                    // Check for paragraphs
                    $is_paragraph = false;
                    if ( empty($language) === false ) {
                        $is_paragraph = true;
                    } else {
                        foreach (self::$paragraphs as $paragraph) {
                            if ( strpos ($class, $paragraph) !== false ) {
                                $is_paragraph = true;
                                break;
                            }
                        }
                    }

                    // Check for pagebreak
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
                    $done = false;
                    if ( $done === false && $is_box === true ) {
                        $this->renderODTOpenBox ($renderer, $class, $style);
                        array_push ($type_stack, 'box');
                        $done = true;
                    }
                    if ( $done === false && $columns > 0 ) {
                        $this->renderODTOpenColumns ($renderer, $class, $style);
                        array_push ($type_stack, 'multicolumn');
                        $done = true;
                    }
                    if ( $done === false && 
                         ($is_paragraph === true || $is_indent === true || $is_outdent === true) ) {
                        $this->renderODTOpenParagraph ($renderer, $class, $style, $language, $is_indent, $is_outdent);
                        array_push ($type_stack, 'p');
                        $done = true;
                    }
                    if ( $done === false && $is_pagebreak === true ) {
                        $renderer->pagebreak ();
                        // Pagebreak hasn't got a closing stack so we push 'other' on the stack
                        array_push ($type_stack, 'other');
                        $done = true;
                    }
                    if ( $done === false && $is_column === true ) {
                        $this->renderODTOpenColumn ($renderer, $class, $style);
                        array_push ($type_stack, 'column');
                        $done = true;
                    }
                    if ( $done === false && $is_group === true ) {
                        $this->renderODTOpenGroup ($renderer, $class, $style);
                        array_push ($type_stack, 'group');
                        $done = true;
                    }
                    if ( $done === false ) {
                        array_push ($type_stack, 'other');
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
                    if ( $type == 'column' ) {
                        $this->renderODTCloseColumn($renderer);
                    }
                    if ( $type == 'group' ) {
                        $this->renderODTCloseGroup($renderer);
                    }
                    // Do nothing for 'other'!
                    break;
            }
            return true;
        }
        return false;
    }

    function renderODTOpenBox ($renderer, $class, $style) {
        $properties = array ();

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

        // The display property has differing usage in CSS. So we better overwrite it.
        $properties ['display'] = 'always';
        if ( stripos ($class, 'wrap_noprint') !== false ) {
            $properties ['display'] = 'screen';
        }
        if ( stripos ($class, 'wrap_onlyprint') !== false ) {
            $properties ['display'] = 'printer';
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
        $properties = array ();

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

    function renderODTOpenParagraph ($renderer, $class, $style, $language, $is_indent, $is_outdent) {
        $properties = array ();

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

        if ( empty($language) === false ) {
            $properties ['lang'] = $language;
        }

        if ( $is_indent === true ) {
            // FIXME: Has to be adjusted if test direction will be supported.
            // See all.css
            $properties ['margin-left'] = $properties ['padding-left'];
            $properties ['padding-left'] = 0;
        }
        if ( $is_outdent === true ) {
            // Nothing to change: keep left margin property.
            // FIXME: Has to be adjusted if text (RTL, LTR) direction will be supported.
            // See all.css
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

    function renderODTOpenColumn ($renderer, $class, $style) {
        $properties = array ();

        if ( method_exists ($renderer, '_odtTableAddColumnUseProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get properties for our class/element from imported CSS
        self::$import->getPropertiesForElement($properties, NULL, $class);

        // Interpret and add values from style to our properties
        $renderer->_processCSSStyle($properties, $style);

        // Adjust values for ODT
        foreach ($properties as $property => $value) {
            $properties [$property] = self::$import->adjustValueForODT ($value, 14);
        }

        // Frames/Textboxes still have some issues with formatting (at least in LibreOffice)
        // So as a workaround we implement columns as a table.
        // This is why we now use the margin of the div as the padding for the ODT table.
        $properties ['padding-left'] = $properties ['margin-left'];
        $properties ['padding-right'] = $properties ['margin-right'];
        $properties ['padding-top'] = $properties ['margin-top'];
        $properties ['padding-bottom'] = $properties ['margin-bottom'];
        $properties ['margin-left'] = NULL;
        $properties ['margin-right'] = NULL;
        $properties ['margin-top'] = NULL;
        $properties ['margin-bottom'] = NULL;

        // Percentage values are not supported for the padding. Convert to absolute values.
        $length = strlen ($properties ['padding-left']);
        if ( $length > 0 && $properties ['padding-left'] [$length-1] == '%' ) {
            $properties ['padding-left'] = trim ($properties ['padding-left'], '%');
            $properties ['padding-left'] = $renderer->_getAbsWidthMindMargins ($properties ['padding-left']).'cm';
        }
        $length = strlen ($properties ['padding-right']);
        if ( $length > 0 && $properties ['padding-right'] [$length-1] == '%' ) {
            $properties ['padding-right'] = trim ($properties ['padding-right'], '%');
            $properties ['padding-right'] = $renderer->_getAbsWidthMindMargins ($properties ['padding-right']).'cm';
        }
        $length = strlen ($properties ['padding-top']);
        if ( $length > 0 && $properties ['padding-top'] [$length-1] == '%' ) {
            $properties ['padding-top'] = trim ($properties ['padding-top'], '%');
            $properties ['padding-top'] = $renderer->_getAbsWidthMindMargins ($properties ['padding-top']).'cm';
        }
        $length = strlen ($properties ['padding-bottom']);
        if ( $length > 0 && $properties ['padding-bottom'] [$length-1] == '%' ) {
            $properties ['padding-bottom'] = trim ($properties ['padding-bottom'], '%');
            $properties ['padding-bottom'] = $renderer->_getAbsWidthMindMargins ($properties ['padding-bottom']).'cm';
        }

        $this->column_count++;
        if ( $this->column_count == 1 ) {
            // If this is the first column opened since the group was opened
            // then we have to open the table and a (single) row first.
            $column_width = $properties ['width'];
            $properties ['width'] = '100%';
            $renderer->_odtTableOpenUseProperties($properties);
            $renderer->_odtTableRowOpenUseProperties($properties);
            $properties ['width'] = $column_width;
        }

        // Convert rel-width to absolute width.
        // The width in percentage works strange in LibreOffice, this is a workaround.
        $length = strlen ($properties ['width']);
        if ( $length > 0 && $properties ['width'] [$length-1] == '%' ) {
            $properties ['width'] = trim ($properties ['width'], '%');
            $properties ['width'] = $renderer->_getAbsWidthMindMargins ($properties ['width']).'cm';
        }

        // We did not specify any max column value when we opened the table.
        // So we have to tell the renderer to add a column just now.
        $renderer->_odtTableAddColumnUseProperties($properties);

        // Open the cell.
        $renderer->_odtTableCellOpenUseProperties($properties);
    }

    function renderODTCloseColumn ($renderer) {
        if ( method_exists ($renderer, '_odtTableAddColumnUseProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        $renderer->tablecell_close();
    }

    function renderODTOpenGroup ($renderer, $class, $style) {
        // Nothing to do for now.
    }

    function renderODTCloseGroup ($renderer) {
        // If a table has been opened in the group we close it now.
        if ( $this->column_count > 0 ) {
            // At last we need to close the row and the table!
            $renderer->tablerow_close();
            //$renderer->table_close();
            $renderer->_odtTableClose();
        }
        $this->column_count = 0;
    }
}

