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
    function register(Doku_Event_Handler $controller){
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array ());
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'handle_secedit_button');
    }

    function handle_toolbar(&$event, $param) {
        $syntaxDiv = $this->getConf('syntaxDiv');
        $syntaxSpan = $this->getConf('syntaxSpan');

        $event->data[] = array (
            'type' => 'picker',
            'title' => $this->getLang('picker'),
            'icon' => DOKU_BASE.'lib/plugins/wrap/images/toolbar/picker.png',
            'list' => array(
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('column'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/toolbar/column.png',
                    'open'   => '<'.$syntaxDiv.' group>\n<'.$syntaxDiv.' half column>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n\n<'.$syntaxDiv.' half column>\n\n</'.$syntaxDiv.'>\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('box'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/toolbar/box.png',
                    'open'   => '<'.$syntaxDiv.' center round box 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('info'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/info.png',
                    'open'   => '<'.$syntaxDiv.' center round info 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('tip'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/tip.png',
                    'open'   => '<'.$syntaxDiv.' center round tip 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('important'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/important.png',
                    'open'   => '<'.$syntaxDiv.' center round important 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('alert'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/alert.png',
                    'open'   => '<'.$syntaxDiv.' center round alert 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('help'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/help.png',
                    'open'   => '<'.$syntaxDiv.' center round help 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('download'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/download.png',
                    'open'   => '<'.$syntaxDiv.' center round download 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('todo'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/note/16/todo.png',
                    'open'   => '<'.$syntaxDiv.' center round todo 60%>\n',
                    'close'  => '\n</'.$syntaxDiv.'>\n',
                ),
                array(
                    'type'   => 'insert',
                    'title'  => $this->getLang('clear'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/toolbar/clear.png',
                    'insert' => '<'.$syntaxDiv.' clear/>\n',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('em'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/toolbar/em.png',
                    'open'   => '<'.$syntaxSpan.' em>',
                    'close'  => '</'.$syntaxSpan.'>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('hi'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/toolbar/hi.png',
                    'open'   => '<'.$syntaxSpan.' hi>',
                    'close'  => '</'.$syntaxSpan.'>',
                ),
                array(
                    'type'   => 'format',
                    'title'  => $this->getLang('lo'),
                    'icon'   => DOKU_BASE.'lib/plugins/wrap/images/toolbar/lo.png',
                    'open'   => '<'.$syntaxSpan.' lo>',
                    'close'  => '</'.$syntaxSpan.'>',
                ),
            )
        );
    }

    /**
     * Handle section edit buttons, prevents section buttons inside the wrap plugin from being rendered
     *
     * @param Doku_Event $event The event object
     * @param array      $args Parameters for the event
     */
    public function handle_secedit_button($event, $args) {
        // counter of the number of currently opened wraps
        static $wraps = 0;
        $data = $event->data;

        if ($data['target'] == 'plugin_wrap_start') {
            ++$wraps;
        } elseif ($data['target'] == 'plugin_wrap_end') {
            --$wraps;
        } elseif ($wraps > 0 && $data['target'] == 'section') {
            $event->preventDefault();
            $event->stopPropagation();
            $event->result = '';
        }
    }
}

