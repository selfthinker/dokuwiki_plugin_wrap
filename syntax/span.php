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
    protected $odt_fo_style;

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
                    $attr = $wrap->buildAttributes($data, 'plugin_wrap',$mode);

                    // Parse attributes.
                    $token = explode(' ', $attr);
                    $odt_style='NOT FOUND!!!';
                    $odt_bg='#FFFFFF';
                    $odt_fo='#000000';
                    $class=NULL;
                    $this->odt_fo_style=NULL;
                    foreach ($token as $i => $value) {
                        if ( substr($value,0,6) == 'class=' )
                        {
                            $class = trim(substr($value,7), '"');
                            continue;
                        }
                        if ( substr($value,0,10) == 'odt_style=' )
                        {
                            $odt_style = trim(substr($value,10), '"');
                            continue;
                        }
                        if ( substr($value,0,7) == 'odt_bg=' )
                        {
                            $odt_bg = trim(substr($value,8), '"');
                            continue;
                        }
                        if ( substr($value,0,7) == 'odt_fo=' )
                        {
                            $odt_fo = trim(substr($value,8), '"');
                            continue;
                        }
                        if ( substr($value,0,12) == 'odt_fo_style' )
                        {
                            $this->odt_fo_style = trim(substr($value,13), '"');
                            continue;
                        }
                    }                

                    // Add our styles.
                    $style_name='pluginwrap_SPAN'.$odt_style.$margin_left.trim($odt_bg,'#').trim($odt_fo,'#');
                    $renderer->autostyles[$style_name] =
                    '<style:style style:name="'.$style_name.'_text_box" style:family="text">
                         <style:text-properties fo:color="'.$odt_fo.'" fo:background-color="'.$odt_bg.'"/>
                         <style:paragraph-properties
                          fo:margin-left="'.$margin_left.'pt" fo:margin-right="0pt" fo:text-indent="0cm"/>
                     </style:style>';

                    // FIXME: I did not find a way to place a small image between the text and
                    //        letting the text flow normal around it as if like the picture is just a letter.
                    // So we not use an image and use a simple text span to color the text with the wanted
                    // font and background color.
                    $renderer->doc .= '<text:span text:style-name="'.$style_name.'_text_box">';

                    // If a font style was found then set a nested span.
                    // FIXME: allow a list of font styles e.g. 'odt_fo_style="strong underline"'
                    if ( $this->odt_fo_style != NULL ) {
                        if ( $this->odt_fo_style == 'strong' ) {
                            $renderer->strong_open ();
                        }
                        if ( $this->odt_fo_style == 'subscript' ) {
                            $renderer->subscript_open ();
                        }
                    }
                    break;

                case DOKU_LEXER_EXIT:
                    // If a font style was found then close the nested span.
                    if ( $this->odt_fo_style != NULL ) {
                        if ( $this->odt_fo_style == 'strong' ) {
                            $renderer->strong_close ();
                        }
                        if ( $this->odt_fo_style == 'subscript' ) {
                            $renderer->subscript_close ();
                        }
                    }

                    $renderer->doc .= '</text:span>';
                    break;
            }
            return true;
        }
        return false;
    }


}

