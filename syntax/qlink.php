<?php
/**
 * Plugin Now: Inserts a timestamp.
 * 
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 */
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bez_qlink extends DokuWiki_Syntax_Plugin {

    function getType() { return 'substition'; }
    function getSort() { return 34; }
	function connectTo($mode) {
		$this->Lexer->addSpecialPattern('#[0-9]+',$mode,'plugin_bez_qlink');
	}


    function handle($match, $state, $pos, &$handler) {
		$nr = substr($match, 1, strlen($match));
		return $nr;
    }

    function render($mode, &$renderer, $nr) {
		if ($mode == 'xhtml') {
			$helper = $this->loadHelper('bez');
			$renderer->doc .= $helper->html_issue_link($nr);

			return true;
		}
		return false;
    }
}
