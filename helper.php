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
    static protected $boxes = array ('wrap_box', 'wrap_danger', 'wrap_warning', 'wrap_caution', 'wrap_notice', 'wrap_safety',
                                     'wrap_info', 'wrap_important', 'wrap_alert', 'wrap_tip', 'wrap_help', 'wrap_todo',
                                     'wrap_download', 'wrap_hi', 'wrap_spoiler');
    static protected $paragraphs = array ('wrap_leftalign', 'wrap_rightalign', 'wrap_centeralign', 'wrap_justify');
    static protected $column_count = 0;

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
        }
        if ($this->getConf('darkTpl')) {
            $attr['class'] = (isset($attr['class']) ? $attr['class'].' ' : '').'wrap__dark';
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

        return $out;
    }

    /**
     * render ODT element, Open
     * (get Attributes, select ODT element that fits, render it, return element name)
     */
    function renderODTElementOpen($renderer, $HTMLelement, $data) {

        $attr = $this->getAttributes($data);
        $classes = explode (' ', $attr['class']);

        // Get language
        $language = $attr['lang'];

        $is_indent    = in_array ('wrap_indent', $classes);
        $is_outdent   = in_array ('wrap_outdent', $classes);
        $is_column    = in_array ('wrap_column', $classes);
        $is_group     = in_array ('group', $classes);
        $is_pagebreak = in_array ('wrap_pagebreak', $classes);

        // Check for multicolumns
        $columns = 0;
        preg_match ('/wrap_col\d/', $attr ['class'], $matches);
        if ( empty ($matches [0]) === false ) {
            $columns = $matches [0] [strlen($matches [0])-1];
        }

        // Check for boxes
        $is_box = false;
        foreach (self::$boxes as $box) {
            if ( strpos ($attr ['class'], $box) !== false ) {
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
                if ( strpos ($attr ['class'], $paragraph) !== false ) {
                    $is_paragraph = true;
                    break;
                }
            }
        }

        $style = NULL;
        if ( empty($attr['width']) === false ) {
            $style = 'width: '.$attr['width'].';';
        }
        $attr ['class'] = 'dokuwiki '.$attr ['class'];

        // Call corresponding functions for current wrap class
        if ( $HTMLelement == 'span' ) {
            if ( $is_indent === false && $is_outdent === false ) {
                $this->renderODTOpenSpan ($renderer, $attr ['class'], $style, $language);
                return 'span';
            } else {
                $this->renderODTOpenParagraph ($renderer, $attr ['class'], $style, $language, $is_indent, $is_outdent, true);
                return 'paragraph';
            }
        } else if ( $HTMLelement == 'div' ) {
            if ( $is_box === true ) {
                $this->renderODTOpenBox ($renderer, $attr ['class'], $style);
                return 'box';
            } else if ( $columns > 0 ) {
                $this->renderODTOpenColumns ($renderer, $attr ['class'], $style);
                return 'multicolumn';
            } else if ( $is_paragraph === true || $is_indent === true || $is_outdent === true ) {
                $this->renderODTOpenParagraph ($renderer, $attr ['class'], $style, $language, $is_indent, $is_outdent, false);
                return 'paragraph';
            } else if ( $is_pagebreak === true ) {
                $renderer->pagebreak ();
                // Pagebreak hasn't got a closing stack so we return/push 'other' on the stack
                return 'other';
            } else if ( $is_column === true ) {
                $this->renderODTOpenColumn ($renderer, $attr ['class'], $style);
                return 'column';
            } else if ( $is_group === true ) {
                $this->renderODTOpenGroup ($renderer, $attr ['class'], $style);
                return 'group';
            }
        }
        return 'other';
    }

    /**
     * render ODT element, Close
     */
    function renderODTElementClose($renderer, $element) {
        switch ($element) {
            case 'box':
                $this->renderODTCloseBox ($renderer);
            break;
            case 'multicolumn':
                $this->renderODTCloseColumns($renderer);
            break;
            case 'paragraph':
                $this->renderODTCloseParagraph($renderer);
            break;
            case 'column':
                $this->renderODTCloseColumn($renderer);
            break;
            case 'group':
                $this->renderODTCloseGroup($renderer);
            break;
            case 'span':
                $this->renderODTCloseSpan($renderer);
            break;
            // No default by intention.
        }
    }

    function renderODTOpenBox ($renderer, $class, $style) {
        $properties = array ();

        if ( method_exists ($renderer, 'getODTProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get CSS properties for ODT export.
        $renderer->getODTProperties ($properties, 'div', $class, $style);

        if ( empty($properties ['background-image']) === false ) {
            $properties ['background-image'] =
                $renderer->replaceURLPrefix ($properties ['background-image'], DOKU_INC);
        }

        if ( empty($properties ['float']) === true ) {
            // If the float property is not set, set it to 'left' becuase the ODT plugin
            // would default to 'center' which is diffeent to the XHTML behaviour.
            if ( strpos ($class, 'wrap_center') === false ) {
                $properties ['float'] = 'left';
            } else {
                $properties ['float'] = 'center';
            }
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

        if ( method_exists ($renderer, 'getODTProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get CSS properties for ODT export.
        $renderer->getODTProperties ($properties, 'div', $class, $style);

        $renderer->_odtOpenMultiColumnFrame($properties);
    }

    function renderODTCloseColumns ($renderer) {
        if ( method_exists ($renderer, '_odtCloseMultiColumnFrame') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $renderer->_odtCloseMultiColumnFrame();
    }

    function renderODTOpenParagraph ($renderer, $class, $style, $language, $is_indent, $is_outdent, $indent_first) {
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

        if ( $indent_first === true ) {
            // Eventually indent or outdent first line only...
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
        } else {
            // Eventually indent or outdent the whole paragraph...
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

    function renderODTOpenColumn ($renderer, $class, $style) {
        $properties = array ();

        if ( method_exists ($renderer, 'getODTProperties') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }

        // Get CSS properties for ODT export.
        $renderer->getODTProperties ($properties, NULL, $class, $style);


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
}
