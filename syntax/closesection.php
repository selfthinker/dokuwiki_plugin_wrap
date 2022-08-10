<?php
/**
 * Section close helper of the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Hamann <michael@content-space.de>
 */

class syntax_plugin_wrap_closesection extends DokuWiki_Syntax_Plugin {

    function getType(){ return 'substition';}
    function getPType(){ return 'block';}
    function getSort(){ return 195; }

    /**
     * Dummy handler, this syntax part has no syntax but is directly added to the instructions by the div syntax
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        if($format == 'xhtml'){
            /** @var Doku_Renderer_xhtml $renderer */
            $renderer->finishSectionEdit();
            return true;
        }
        return false;
    }


}

