<?php
/**
 * Helper Component for the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_wrap extends DokuWiki_Plugin {
    public $known_odt_styles = array ("box", "info", "important", "alert", "tip", "help", "todo", "download",
                                      "danger", "warning", "caution", "notice", "safety", "hi", "lo", "em",
                                      "column", "spoiler");

    // For each style list the background color and the name of the corresponding image, if any.
    // Color values like #eee do not work in odt so they need to be expanded to #e0e0e0.
    public $odt_styles = array("default"   => array ("type"     => "mark",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ffffff",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "box"       => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#eeeeee",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "info"      => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#d1d7df",
                                                     "style"    => NULL,
                                                     "picture" => "info.png" ),
                               "important" => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ffd39f",
                                                     "style"    => NULL,
                                                     "picture" => "important.png" ),
                               "alert"     => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ffbcaf",
                                                     "style"    => NULL,
                                                     "picture" => "alert.png" ),
                               "tip"       => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#fff79f",
                                                     "style"    => NULL,
                                                     "picture" => "tip.png" ),
                               "help"      => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#dcc2ef",
                                                     "style"    => NULL,
                                                     "picture" => "help.png" ),
                               "todo"      => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#c2efdd",
                                                     "style"    => NULL,
                                                     "picture" => "todo.png" ),
                               "download"  => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#c2efdd",
                                                     "style"    => NULL,
                                                     "picture" => "download.png" ),
                               "danger"    => array ("type"     => "container",
                                                     "fo_color" => "#ffffff",
                                                     "bg_color" => "#cc0000",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "warning"   => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ff6600",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "caution"   => array ("type"     => "container",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ffff00",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "notice"    => array ("type"     => "container",
                                                     "fo_color" => "#ffffff",
                                                     "bg_color" => "#0066ff",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "safety"    => array ("type"     => "container",
                                                     "fo_color" => "#ffffff",
                                                     "bg_color" => "#009900",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "hi"        => array ("type"     => "mark",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ffff99",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "lo"        => array ("type"     => "mark",
                                                     "fo_color" => "#666666",
                                                     "bg_color" => "#ffffff",
                                                     "style"    => "subscript",
                                                     "picture" => NULL ),
                               "em"        => array ("type"     => "mark",
                                                     "fo_color" => "#cc0000",
                                                     "bg_color" => "#ffffff",
                                                     "style"    => "strong",
                                                     "picture" => NULL ),
                               "column"    => array ("type"     => "column",
                                                     "fo_color" => "#000000",
                                                     "bg_color" => "#ffffff",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               "spoiler"   => array ("type"     => "mark",
                                                     "fo_color" => "#ffffff",
                                                     "bg_color" => "#ffffff",
                                                     "style"    => NULL,
                                                     "picture" => NULL ),
                               );

    /**
     * get attributes (pull apart the string between '<wrap' and '>')
     *  and identify classes, width, lang and dir
     *
     * @author Anika Henke <anika@selfthinker.org>
     * @author Christopher Smith <chris@jalakai.co.uk>
     *   (parts taken from http://www.dokuwiki.org/plugin:box)
     */
    function getAttributes($data) {

        $attr = array();
        $tokens = preg_split('/\s+/', $data, 9);
        $noPrefix = array_map('trim', explode(',', $this->getConf('noPrefix')));
        $restrictedClasses = $this->getConf('restrictedClasses');
        if ($restrictedClasses) {
            $restrictedClasses = array_map('trim', explode(',', $this->getConf('restrictedClasses')));
        }
        $restrictionType = $this->getConf('restrictionType');

        foreach ($tokens as $token) {

            //get width
            if (preg_match('/^\d*\.?\d+(%|px|em|ex|pt|pc|cm|mm|in)$/', $token)) {
                $attr['width'] = $token;
                continue;
            }

            //get lang
            if (preg_match('/\:([a-z\-]+)/', $token)) {
                $attr['lang'] = trim($token,':');
                continue;
            }

            //get id
            if (preg_match('/#([A-Za-z0-9_-]+)/', $token)) {
                $attr['id'] = trim($token,'#');
                continue;
            }

            //get classes
            //restrict token (class names) characters to prevent any malicious data
            if (preg_match('/[^A-Za-z0-9_-]/',$token)) continue;
            if ($restrictedClasses) {
                $classIsInList = in_array(trim($token), $restrictedClasses);
                // either allow only certain classes
                if ($restrictionType) {
                    if (!$classIsInList) continue;
                // or disallow certain classes
                } else {
                    if ($classIsInList) continue;
                }
            }
            $prefix = in_array($token, $noPrefix) ? '' : 'wrap_';
            $attr['class'] = (isset($attr['class']) ? $attr['class'].' ' : '').$prefix.$token;

            // get odt style
            foreach ($this->known_odt_styles as $style) {
                if ( $style == $token ) {
                    $attr['odt_style'] = $token;
                }
            }

            // get odt alignment
            if ( $token == 'left' || $token == 'center' || $token == 'right' ) {
                $attr['odt_align'] = $token;
            }

            // get odt round corners
            if ( $token == 'round' ) {
                $attr['odt_round'] = 'true';
            }

            // get odt display setting
            if ( $token == 'hide' ) {
                $attr['odt_display'] = 'none';
            }
            if ( $token == 'noprint' ) {
                $attr['odt_display'] = 'screen';
            }
            if ( $token == 'onlyprint' ) {
                $attr['odt_display'] = 'printer';
            }
        }

        //get dir
        if($attr['lang']) {
            $lang2dirFile = dirname(__FILE__).'/conf/lang2dir.conf';
            if (@file_exists($lang2dirFile)) {
                $lang2dir = confToHash($lang2dirFile);
                $attr['dir'] = strtr($attr['lang'],$lang2dir);
            }
        }

        return $attr;
    }

    /**
     * build attributes (write out classes, width, lang and dir)
     */
    function buildAttributes($data, $addClass='', $mode='xhtml') {

        $attr = $this->getAttributes($data);
        $out = '';

        if ($mode=='xhtml') {
            if($attr['class']) $out .= ' class="'.hsc($attr['class']).' '.$addClass.'"';
            // if used in other plugins, they might want to add their own class(es)
            elseif($addClass)  $out .= ' class="'.$addClass.'"';
            if($attr['id'])    $out .= ' id="'.hsc($attr['id']).'"';
            // width on spans normally doesn't make much sense, but in the case of floating elements it could be used
            if($attr['width']) {
                if (strpos($attr['width'],'%') !== false) {
                    $out .= ' style="width: '.hsc($attr['width']).';"';
                } else {
                    // anything but % should be 100% when the screen gets smaller
                    $out .= ' style="width: '.hsc($attr['width']).'; max-width: 100%;"';
                }
            }
            // only write lang if it's a language in lang2dir.conf
            if($attr['dir'])   $out .= ' lang="'.$attr['lang'].'" xml:lang="'.$attr['lang'].'" dir="'.$attr['dir'].'"';
        }
        if ($mode=='odt') {
            if($attr['class']) $out .= ' class="'.hsc($attr['class']).' '.$addClass.'"';
            // if used in other plugins, they might want to add their own class(es)
            elseif($addClass)  $out .= ' class="'.$addClass.'"';
            if($attr['id'])    $out .= ' id="'.hsc($attr['id']).'"';
            // width on spans normally doesn't make much sense, but in the case of floating elements it could be used
            if($attr['width']) {
                if (strpos($attr['width'],'%') !== false) {
                    $out .= ' width="'.hsc($attr['width']).'"';
                } else {
                    // anything but % should be 100% when the screen gets smaller
                    $out .= ' width=" '.hsc($attr['width']).'"'; //; max-width: 100%;"';
                }
            }
            // only write lang if it's a language in lang2dir.conf
            if($attr['dir'])   $out .= ' lang="'.$attr['lang'].'" xml:lang="'.$attr['lang'].'" dir="'.$attr['dir'].'"';
            // if the style is known get it's properties otherwise use default style properties
            if($attr['odt_style']) {
                $out .= ' odt_style="'.$attr['odt_style'].'"';
                $out .= ' odt_type="'.$this->odt_styles[$attr['odt_style']]['type'].'"';
                $out .= ' odt_bg="'.$this->odt_styles[$attr['odt_style']]['bg_color'].'"';
                $out .= ' odt_fo="'.$this->odt_styles[$attr['odt_style']]['fo_color'].'"';
                if ( $this->odt_styles[$attr['odt_style']]['style'] != NULL )
                    $out .= ' odt_fo_style="'.$this->odt_styles[$attr['odt_style']]['style'].'"';
                if ( $this->odt_styles[$attr['odt_style']]['picture'] != NULL )
                    $out .= ' odt_pic="'.$this->odt_styles[$attr['odt_style']]['picture'].'"';
            } else {
                $out .= ' odt_style="default"';
                $out .= ' odt_type="'.$this->odt_styles['default']['type'].'"';
                $out .= ' odt_bg="'.$this->odt_styles['default']['bg_color'].'"';
                $out .= ' odt_fo="'.$this->odt_styles['default']['fo_color'].'"';
                if ( $this->odt_styles['default']['style'] != NULL )
                    $out .= ' odt_fo_style="'.$this->odt_styles['default']['style'].'"';
                if ( $this->odt_styles['default']['picture'] != NULL )
                    $out .= ' odt_pic="'.$this->odt_styles['default']['picture'].'"';
            }
            if($attr['odt_style']) {
                $out .= ' odt_align="'.$attr['odt_align'].'"';
            }
            if($attr['odt_round']) {
                $out .= ' odt_round="'.$attr['odt_round'].'"';
            }
            if($attr['odt_display']) {
                $out .= ' odt_display="'.$attr['odt_display'].'"';
            }
        }

        return $out;
    }


}
