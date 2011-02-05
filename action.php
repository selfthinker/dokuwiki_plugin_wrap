<?php
/**
 * Action Component for the Wrap Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_wrap extends DokuWiki_Action_Plugin {

    /**
     * register the eventhandlers
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function register(&$controller){
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array ());
    }

    function handle_toolbar(&$event, $param) {
        $event->data[] = array (
            'type' => 'picker',
            'title' => $this->getLang('picker'),
            'icon' => '../../plugins/wrap/images/toolbar/picker.png',
            'list' => array(
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('column'),
                    'icon'   => '../../plugins/wrap/images/toolbar/column.png',
                    'open'   => '<WRAP column 30%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('box'),
                    'icon'   => '../../plugins/wrap/images/toolbar/box.png',
                    'open'   => '<WRAP center round box 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('info'),
                    'icon'   => '../../plugins/wrap/images/note/16/info.png',
                    'open'   => '<WRAP center round info 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('tip'),
                    'icon'   => '../../plugins/wrap/images/note/16/tip.png',
                    'open'   => '<WRAP center round tip 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('important'),
                    'icon'   => '../../plugins/wrap/images/note/16/important.png',
                    'open'   => '<WRAP center round important 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('alert'),
                    'icon'   => '../../plugins/wrap/images/note/16/alert.png',
                    'open'   => '<WRAP center round alert 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('help'),
                    'icon'   => '../../plugins/wrap/images/note/16/help.png',
                    'open'   => '<WRAP center round help 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('download'),
                    'icon'   => '../../plugins/wrap/images/note/16/download.png',
                    'open'   => '<WRAP center round download 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('todo'),
                    'icon'   => '../../plugins/wrap/images/note/16/todo.png',
                    'open'   => '<WRAP center round todo 60%>\n',
                    'close'  => '\n</WRAP>\n',
                ),
                array(
                    'type'   => 'insert',
                    'title'  => $this->getLang('clear'),
                    'icon'   => '../../plugins/wrap/images/toolbar/clear.png',
                    'insert' => '<WRAP clear></WRAP>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('em'),
                    'icon'   => '../../plugins/wrap/images/toolbar/em.png',
                    'open'   => '<wrap em>',
                    'close'  => '</wrap>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('hi'),
                    'icon'   => '../../plugins/wrap/images/toolbar/hi.png',
                    'open'   => '<wrap hi>',
                    'close'  => '</wrap>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('lo'),
                    'icon'   => '../../plugins/wrap/images/toolbar/lo.png',
                    'open'   => '<wrap lo>',
                    'close'  => '</wrap>',
                ),
            )
        );
    }
}

