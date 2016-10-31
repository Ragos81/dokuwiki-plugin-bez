<?php
 
if(!defined('DOKU_INC')) die();

/*
 * Task coordinator is taken from tasktypes
 */
require_once 'entity.php';

class BEZ_mdl_Task extends BEZ_mdl_Entity {
	//if errors = true we cannot save task
	
	//meta
	protected $reporter, $date, $close_date;
	
	//acl
	//coordinator is defined by issue or tasktype
	protected $tasktype, $issue;
	
	//data
	protected $cause, $executor, $task, $plan_date, $cost, $all_day_event, $start_time, $finish_time;
	
	//state
	protected $state, $reason;
	
	//virtual
	protected $coordinator, $program_coordinator, $action;
	
	public function get_columns() {
		return array('id', 'reporter', 'date', 'close_date', 'cause',
					'executor', 'tasktype', 'issue',
					'task', 'plan_date', 'cost', 'all_day_event',
					'start_time', 'finish_time',
					'state', 'reason', 'task_cache', 'reason_cache');
	}
	
	public function get_virtual_columns() {
		return array('coordinator', 'program_coordinator', 'action');
	}
	
	private function set_defaults($defaults) {
		//meta
		$this->reporter = $this->auth->get_user();
		$this->date = time();
		
		$val_data = $this->validator->validate($defaults, array('cause', 'tasktype', 'issue', 'coordinator', 'program_coordinator'));
			
		//~ if ($val_data === false) {
			//~ $this->errors = true;
			//~ echo 'BEZ_mdl_Task: error when setting defaults '.var_export($this->get_errors(), true);
			//~ return false;
		//~ }
		
		if (isset($val_data['cause'])) {
			$this->cause = $val_data['cause'];
		}
		if (isset($val_data['tasktype'])) {
			$this->tasktype = $val_data['tasktype'];
		}
		if (isset($val_data['issue'])) {
			$this->issue = $val_data['issue'];
		}

		if (isset($val_data['coordinator'])) {
			$this->coordinator = $val_data['coordinator'];
		}
		
		if (isset($val_data['program_coordinator'])) {
			$this->program_coordinator = $val_data['program_coordinator'];
		}
		
		$this->state = '0';
		
		
		if ($val_data === false) {
			$this->errors = true;
			return false;
		}
	}
	
	//~ private function set_coordinator() {
		//~ if ($this->issue !== NULL) {
			//~ $issue = $this->model->issues->get_one($this->issue);
			//~ $this->coordinator = $issue->coordinator;
		//~ } else if ($this->tasktype !== NULL) {
			//~ $tasktype = $this->model->tasktypes->get_one($this->tasktype);
			//~ $this->coordinator = $tasktype->coordinator;
		//~ }
		
	//~ }
	
	//by defaults you can set: cause, tasktype and issue
	//tasktype is required
	public function __construct($model, $defaults=array()) {
		parent::__construct($model);
				
		//array(filter, NULL)
		$this->validator->set_rules(array(
			'reporter' => array(array('dw_user'), 'NOT NULL'),
			'date' => array(array('unix_timestamp'), 'NOT NULL'),
			'close_date' => array(array('unix_timestamp'), 'NULL'),
			'cause' => array(array('numeric'), 'NULL'),
			
			'executor' => array(array('dw_user'), 'NOT NULL'),
			'tasktype' => array(array('numeric'), 'NOT NULL'),
			'issue' => array(array('numeric'), 'NULL'),
			
			'task' => array(array('length', 1000), 'NOT NULL'),
			'plan_date' => array(array('iso_date'), 'NOT NULL'),
			'cost' => array(array('numeric'), 'NULL'),
			'all_day_event' => array(array('select', array('0', '1')), 'NOT NULL'), 
			'start_time' => array(array('time'), 'NULL'), 
			'finish_time' => array(array('time'), 'NULL'), 
			
			'state' => array(array('select', array('0', '1', '2')), 'NULL'),
			'reason' => array(array('length', 1000), 'NULL'),
			
			'coordinator' => array(array('dw_user'), 'NOT NULL'),
			'program_coordinator' => array(array('dw_user'), 'NOT NULL'),
		));
		
		//we've created empty object
		if ($this->id === NULL) {
			$this->set_defaults($defaults);
		}
		
		$this->auth->set_coordinator($this->coordinator);
		$this->auth->set_programm_coordinator($this->program_coordinator);
		$this->auth->set_executor($this->executor);
	}
	
	public function set_meta($data) {
		if ($this->auth->get_level() < 20) {
			return false;
		}
		
		$val_data = $this->validator->validate($data, array('reporter', 'date', 'close_date'));
		if ($val_data === false) {
			$this->errors = true;
			return false;
		}
		$this->errors = false;
		
		foreach ($val_data as $k => $v) {
			$this->$k = $v;
		}
		
		return true;
	}
	
	public function set_acl($data) {
		if ($this->auth->get_level() < 20) {
			return false;
		}
		
		$val_data = $this->validator->validate($data, array('tasktype', 'issue'));
		if ($val_data === false) {
			$this->errors = true;
			return false;
		}
		$this->errors = false;
		
		foreach ($val_data as $k => $v) {
			$this->$k = $v;
		}
		
		return true;
	}
	
	public function set_data($data) {
		if ($this->auth->get_level() < 15) {
			return false;
		}
			
		$val_data = $this->validator->validate($data, array('executor',
		'cause', 'task', 'plan_date', 'cost', 'all_day_event', 'start_time', 'finish_time'));
		if ($val_data === false) {
			$this->errors = true;
			return false;
		}
		$this->errors = false;

		foreach ($val_data as $k => $v) {
				$this->$k = $v;
		}
		$this->auth->set_executor($this->executor);
		
		//set parsed
		$this->task_cache = $this->helper->wiki_parse($this->task);
			
		return true;
	}
	
	public function set_state($data) {
		if ($this->auth->get_level() < 10) {
			return false;
		}
		//reason is required while changing state
		if ($data['state'] == '1' || $data['state'] == '2') {
			$this->validator->set_rules(array(
				'reason' => array(array('length', 1000), 'NOT NULL')
			));
		}
		
		$val_data = $this->validator->validate($data, array('state', 'reason'));
		if ($val_data === false) {
			$this->errors = true;
			return false;
		}
		$this->errors = false;
		

		
		foreach ($val_data as $k => $v) {
			$this->$k = $v;
		}
		$this->reason_cache = $this->helper->wiki_parse($this->reason);
		$this->close_date = time();
		
		
		return true;
	}
	
	public function get_states() {
		return array(	
				'0' => 'task_opened',
				'-outdated' => 'task_outdated',
				'1' => 'task_done',
				'2' => 'task_rejected'
			);
	}
	
	public function state_string($state) {
		$states = $this->get_states();
		return $states[$state];
	}
	
	public function action_string($action) {
		switch($action) {
			case '0': return 'correction'; break;
			case '1': return 'corrective_action'; break;
			case '2': return 'preventive_action'; break;
			case '3': return 'programme'; break;
		}
	}
}