<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_wrap extends DokuWiki_Action_Plugin {

    /**
     * return some info
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function getInfo(){
        return array_merge(confToHash(dirname(__FILE__).'/README'), array('name' => 'Toolbar Component'));
    }

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
            'icon' => '../../plugins/wrap/images/note/16/picker.png',
            'list' => array(
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('left'),
                    'icon'   => '../../plugins/wrap/images/note/16/left.png',
                    'open'   => '<WRAP left 30%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('right'),
                    'icon'   => '../../plugins/wrap/images/note/16/right.png',
                    'open'   => '<WRAP right 30%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('box'),
                    'icon'   => '../../plugins/wrap/images/note/16/box.png',
                    'open'   => '<WRAP center round box 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('info'),
                    'icon'   => '../../plugins/wrap/images/note/16/info.png',
                    'open'   => '<WRAP center round info 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('download'),
                    'icon'   => '../../plugins/wrap/images/note/16/download.png',
                    'open'   => '<WRAP center round download 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('help'),
                    'icon'   => '../../plugins/wrap/images/note/16/help.png',
                    'open'   => '<WRAP center round help 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('important'),
                    'icon'   => '../../plugins/wrap/images/note/16/important.png',
                    'open'   => '<WRAP center round important 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('tip'),
                    'icon'   => '../../plugins/wrap/images/note/16/tip.png',
                    'open'   => '<WRAP center round tip 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('todo'),
                    'icon'   => '../../plugins/wrap/images/note/16/todo.png',
                    'open'   => '<WRAP center round todo 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('warning'),
                    'icon'   => '../../plugins/wrap/images/note/16/warning.png',
                    'open'   => '<WRAP center round warning 60%>\n\n',
                    'close'  => '\n</WRAP>',
                ),
            )
        );
    }
}

