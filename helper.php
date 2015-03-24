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
                $classIsInList = in_array(strtolower(trim($token)), $restrictedClasses);
                // either allow only certain classes
                if ($restrictionType) {
                    if (!$classIsInList) continue;
                // or disallow certain classes
                } else {
                    if ($classIsInList) continue;
                }
            }
            
            //get 6-digit hex color code to interpret that as background color
            if (preg_match('/[\dA-F]{6}$/A',$token)) {
                $attr['bgcolor'] = $token;
                continue;
            }
            
            $token = strtolower($token);
            $prefix = in_array($token, $noPrefix) ? '' : 'wrap_';
            $attr['class'] = (isset($attr['class']) ? $attr['class'].' ' : '').$prefix.$token;
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
            if($attr['width'] || $attr['bgcolor']) {
                $out .= ' style="';
                if($attr['width']) {
                    if (strpos($attr['width'],'%') !== false) {
                        $out .= 'width: '.hsc($attr['width']).';';
                    } else {
                        // anything but % should be 100% when the screen gets smaller
                        $out .= 'width: '.hsc($attr['width']).'; max-width: 100%;';
                    }
                }
                if($attr['bgcolor'])    $out .= 'background-color: #'.hsc($attr['bgcolor']).';';
                $out .= '"';
            }
            // only write lang if it's a language in lang2dir.conf
            if($attr['dir'])   $out .= ' lang="'.$attr['lang'].'" xml:lang="'.$attr['lang'].'" dir="'.$attr['dir'].'"';
        }

        return $out;
    }


}
