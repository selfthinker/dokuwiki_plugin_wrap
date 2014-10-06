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

    protected $entry_pattern = '<div.*?>(?=.*?</div>)';
    protected $exit_pattern  = '</div>';
    protected $columns_open = 0;
    protected $odt_type;
    protected $odt_fo_style;
    protected $odt_ignore;

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
    function handle($match, $state, $pos, Doku_Handler $handler){
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
    function render($mode, Doku_Renderer $renderer, $indata) {

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
                    $this->odt_ignore = $this->_odtVersionToOld($renderer);
                    if ( $this->odt_ignore == true ) {
                        $renderer->doc .= '<text:p>WRAP: ODT plugin version 2014-10-04 or newer required!</text:p>';
                        return false;
                    }

                    // Get attributes.
                    $wrap =& plugin_load('helper', 'wrap');
                    $attr = $wrap->buildAttributes($data, 'plugin_wrap',$mode);

                    // Parse attributes.
                    $token = explode(' ', $attr);
                    $width=$renderer->_getRelWidthMindMargins();
                    $round=false;
                    $odt_style='NOT FOUND!!!';
                    $odt_bg='#FFFFFF';
                    $odt_fo='#000000';
                    $picture=NULL;
                    $class=NULL;
                    $this->odt_type=NULL;
                    foreach ($token as $i => $value) {
                        if ( substr($value,0,6) == 'width=' )
                        {
                            $width = trim(substr($value,7), '"');
                            $width = trim($width, '%');
                            $width = $renderer->_getRelWidthMindMargins($width);
                            continue;
                        }
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
                        if ( substr($value,0,9) == 'odt_type=' )
                        {
                            $this->odt_type = trim(substr($value,10), '"');
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
                        if ( substr($value,0,10) == 'odt_round=' )
                        {
                            $round = trim(substr($value,11), '"');
                            continue;
                        }
                        if ( substr($value,0,10) == 'odt_align=' )
                        {
                            $horiz_pos = trim(substr($value,11), '"');
                            continue;
                        }
                        if ( substr($value,0,8) == 'odt_pic=' )
                        {
                            $picture = trim(substr($value,9), '"');
                            continue;
                        }
                    }                

                    if ( $this->odt_type == 'container' || $this->odt_type == 'mark' ) {
                        $this->_odtContainerOpen ($renderer, $odt_style, $picture, $width, $horiz_pos, $odt_bg, $odt_fo, $round);
                    }
                    if ( $this->odt_type == 'column' ) {
                        // FIXME columns could be implemented using tables.
                        //       But actually the parser does not know what the first
                        //       and last columns are. As long as this info is missing,
                        //       a table can't be used because it needs a closing tag.
                        //$this->_odtColumnOpen ($renderer, $odt_bg, $odt_fo);
                    }
                    break;

                case DOKU_LEXER_EXIT:
                    if ( $this->odt_ignore == true ) {
                        return false;
                    }
                    if ( $this->odt_type == 'container' || $this->odt_type == 'mark' ) {
                        $this->_odtContainerClose ($renderer);
                    }
                    if ( $this->odt_type == 'column' ) {
                        // FIXME columns could be implemented using tables.
                        //       But actually the parser does not know what the first
                        //       and last columns are. As long as this info is missing,
                        //       a table can't be used because it needs a closing tag.
                        //$this->_odtColumnClose ($renderer);
                    }
                    break;
            }
            return true;
        }
        return false;
    }

    function _odtContainerOpen (Doku_Renderer &$renderer, $odt_style, $picture, $width, $horiz_pos, $odt_bg, $odt_fo, $round) {
        if ( $picture != NULL )
        {
            $picture=DOKU_PLUGIN.'wrap/images/note/48/'.$picture;
            $pic_link=$renderer->_odtAddImageAsFileOnly($picture);
            list($pic_width, $pic_height) = $renderer->_odtGetImageSize($picture);
            $min_height = trim($pic_height, 'cm');
            $margin_left = '70';
        }
        else
        {
            $min_height = 1;
            $margin_left = '10';
        }
        $width_abs = ($renderer->_getPageWidth() * $width)/100;

        // Add our styles.
        $style_name='pluginwrap_DIV'.$odt_style.($width%100).$horiz_pos.$margin_left.trim($odt_bg,'#').trim($odt_fo,'#');
        $renderer->autostyles[$style_name] =
         '<style:style style:name="'.$style_name.'_text_frame" style:family="graphic">
             <style:graphic-properties svg:stroke-color="'.$odt_bg.'"
                 draw:fill="solid" draw:fill-color="'.$odt_bg.'"
                 draw:textarea-horizontal-align="left"
                 draw:textarea-vertical-align="center"
                 style:horizontal-pos="'.$horiz_pos.'"
                 fo:padding-top="0.5cm" fo:padding-bottom="0.5cm"
                 fo:min-height="'.$min_height.'cm"
                 style:rel-width="'.$width.'%"
                 style:wrap="none"/>
         </style:style>
         <style:style style:name="'.$style_name.'_image_frame" style:family="graphic">
             <style:graphic-properties svg:stroke-color="'.$odt_bg.'"
                 draw:fill="none" draw:fill-color="'.$odt_bg.'"
                 draw:textarea-horizontal-align="left"
                 draw:textarea-vertical-align="center"
                 style:wrap="none"/>
         </style:style>
         <style:style style:name="'.$style_name.'_text_box" style:family="paragraph">
             <style:text-properties fo:color="'.$odt_fo.'"/>
             <style:paragraph-properties
              fo:margin-left="'.$margin_left.'pt" fo:margin-right="10pt" fo:text-indent="0cm"/>
         </style:style>';

        // Group the frame so that they are stacked one on each other.
        $renderer->doc .= '<text:p>';
        $renderer->doc .= '<draw:g>';

        // Draw a frame with the image in it, if required.
        // FIXME: the image will not be centered vertically.
        //        This could be achiebed by using a table but then
        //        it is not possible to have round corners (...as far as I know).
        if ( $picture != NULL )
        {
            $renderer->doc .= '<draw:frame draw:style-name="'.$style_name.'_image_frame" draw:name="Bild1"
                                text:anchor-type="paragraph"
                                svg:x="0.5cm" svg:y="0.5cm"
                                svg:width="'.$pic_width.'" svg:height="'.$pic_height.'"
                                draw:z-index="1">
                               <draw:image xlink:href="'.$pic_link.'"
                                xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>
                                </draw:frame>';
        }

        // Draw a frame with a text box in it. the text box will be left opened
        // to grow with the content (requires fo:min-height in $style_name).
        $renderer->doc .= '<draw:frame draw:style-name="'.$style_name.'_text_frame" draw:name="Bild1"
                            text:anchor-type="paragraph"
                            svg:x="0" svg:y="0"
                            svg:width="'.$width_abs.'cm" svg:height="10cm" ';
        $renderer->doc .= 'draw:z-index="0">';
        $renderer->doc .= '<draw:text-box ';

        // If required use round corners.
        if ( $round == true )
            $renderer->doc .= 'draw:corner-radius="0.5cm" ';

        $renderer->doc .= '>';
        $renderer->p_open($style_name.'_text_box');
    }

    function _odtContainerClose (Doku_Renderer &$renderer) {
        $renderer->p_close();
        $renderer->doc .= '</draw:text-box></draw:frame>';
        $renderer->doc .= '</draw:g>';
        $renderer->doc .= '</text:p>';
    }

    function _odtVersionToOld (Doku_Renderer &$renderer) {
        $info=$renderer->getInfo();
        $date = explode('-', $info['date']);
        if ( $date [0] < 2014 )
            return true;
        if ( $date [0] == 2014 && $date [1] < 10 )
            return true;
        if ( $date [0] == 2014 && $date [1] == 10 && $date [2] < 4 )
            return true;
        return false;
    }
}

