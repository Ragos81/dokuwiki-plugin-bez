<?php
/**
 * Plugin Now: Inserts a timestamp.
 * 
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Szymon Olewniczak <szymon.olewniczak@rid.pl>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';
include_once DOKU_PLUGIN."bez/models/issues.php";
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bez_nav extends DokuWiki_Syntax_Plugin {
	private $value = array();

    function getPType() { return 'block'; }
    function getType() { return 'substition'; }
    function getSort() { return 99; }


    function connectTo($mode) {
		$this->Lexer->addSpecialPattern('~~BEZNAV~~',$mode,'plugin_bez_nav');
    }

	function __construct() {

		$ex = explode(':', $_GET['id']);
		for ($i = 0; $i < count($ex); $i += 2)
			$this->value[urldecode($ex[$i])] = urldecode($ex[$i+1]);
	}

    function handle($match, $state, $pos, &$handler)
    {
		return true;
    }

    function render($mode, &$R, $pass) {
		global $INFO;

		$helper = $this->loadHelper('bez');
		if ($mode != 'xhtml' || !$helper->user_viewer()) return false;

        $R->info['cache'] = false;

		$data = array(
			'bez:start' => array('id' => 'bez:start', 'type' => 'd', 'level' => 1, 'title' => $this->getLang('bez')),
		);

		if ($helper->user_editor())
			$data['bez:issue_report'] = array('id' => 'bez:issue_report', 'type' => 'f', 'level' => 2, 'title' => $this->getLang('bds_issue_report'));

		$data['bez:issues'] = array('id' => 'bez:issues:year:'.date('Y'), 'type' => 'f', 'level' => 2, 'title' => $this->getLang('bds_issues'));
		$data['bez:tasks'] = array('id' => 'bez:tasks:year:'.date('Y'), 'type' => 'f', 'level' => 2, 'title' => $this->getLang('bez_tasks'));

		$data['bez:report'] = array('id' => 'bez:report', 'type' => 'd', 'level' => 2, 'title' => $this->getLang('report'));



		
		$isso = new Issues();
		if ($this->value['bez'] == 'report') {
			$data['bez:report']['open'] = true;

			$oldest = $isso->get_oldest_close_date();
			$year_old = (int)date('Y', $oldest);
			$mon_old = (int)date('n', $oldest);
			$year_now = (int)date('Y');
			$mon_now = (int)date('n');

			$entity = '';
			if (array_key_exists('entity', $this->value)) {
				$entity = ':entity:'.urlencode($this->value['entity']);
			}

			$mon = $mon_old;
			for ($year = $year_old; $year <= $year_now; $year++) {

				$y_key = 'bez:report:year:'.$year;
				$data[$y_key] = array('id' => $y_key.$entity, 'type' => 'd', 'level' => 3, 'title' => $year);

				if (isset($this->value['year']) && (int)$this->value['year'] == $year) {
					$data['bez:report:year:'.$year]['open'] = true;

					if ($year == $year_now)
						$mon_max = $mon_now;
					else
						$mon_max = 12;
					for ( ; $mon <= $mon_max; $mon++) {
						$m_key = $y_key.':month:'.$mon;
						$data[$m_key] = array('id' => $m_key.$entity, 'type' => 'f', 'level' => 4,
						'title' => $mon < 10 ? '0'.$mon : $mon);
					}	
				}
				$mon = 1;
			}
		}



		if (isset($this->value['bez'])) {
			$data['bez:start']['open'] = true;
		} else {
			$data['bez:start']['open'] = false;
			array_splice($data, 1);
		}

		if ($helper->user_admin() && $data['bez:start']['open'] == true)
			$data['bez:entity'] = array('id' => 'bez:entity', 'type' => 'f', 'level' => 2, 'title' => $this->getLang('entity_manage'));

        $R->doc .= '<div class="plugin__bez">';
        $R->doc .= html_buildlist($data,'idx',array($this,'_list'),array($this,'_li'));
        $R->doc .= '</div>';

		return true;
	}

	function _bezlink($id, $title) {
		//$uri = wl($id);
		$uri = DOKU_URL . 'doku.php?id='.$id;
		return '<a href="'.$uri.'">'.($title).'</a>';
	}

    function _list($item){

		$ex = explode(':', $item['id']);
		for ($i = 0; $i < count($ex); $i += 2)
			$item_value[urldecode($ex[$i])] = urldecode($ex[$i+1]);

		//pola brane pod uwagę przy określaniu aktualnej strony
		$fields = array('bez');
		if ($item_value['bez'] == 'report') {
			$fields[] = 'month';
			$fields[] = 'year';
		}

		$actual_page = true;
		foreach ($fields as $field)
			if ($item_value[$field] != $this->value[$field])
				$actual_page = false;



        if(($item['type'] == 'd' && $item['open']) ||  $actual_page) {
            return '<strong>'.$this->_bezlink($item['id'], $item['title']).'</strong>';
        }else{
            return $this->_bezlink($item['id'], $item['title']);
        }

    }

    function _li($item){
        if($item['type'] == "f"){
            return '<li class="level'.$item['level'].'">';
        }elseif($item['open']){
            return '<li class="open">';
        }else{
            return '<li class="closed">';
        }
    }
}
