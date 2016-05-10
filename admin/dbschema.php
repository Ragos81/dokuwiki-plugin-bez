<?php
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
 
 // must be run within DokuWiki
if(!defined('DOKU_INC')) die();
 if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
 
$errors = array();
include_once DOKU_PLUGIN."bez/models/connect.php";
include_once DOKU_PLUGIN."bez/models/tasktypes.php";
include_once DOKU_PLUGIN."bez/models/tasks.php";
class admin_plugin_bez_dbschema extends DokuWiki_Admin_Plugin {

	private $exp = false;
	private $connect;
 
	function getMenuText($lang) {
		return 'Zaktualizuj schemat bazy BEZ';
	}
	
	function __construct() {
		$this->connect = new Connect();
	}
	
	function check_remove_action_from_tasks() {
		/*potential cause*/
		$schema = $this->connect->fetch_assoc("PRAGMA table_info(tasks)");
		foreach ($schema as $column)
			if ($column['name'] == 'action')
				return false;
		
		return true;
	}
	
	function do_remove_action_from_tasks() {
		$q = "
		BEGIN TRANSACTION;
		ALTER TABLE tasks RENAME to tasks_backup;
		CREATE TABLE IF NOT EXISTS tasks (
				id INTEGER PRIMARY KEY,
				task TEXT NOT NULL,
				state INTEGER NOT NULL,
				executor TEXT NOT NULL,
				cost INTEGER NULL,
				reason TEXT NULL,
				reporter TEXT NOT NULL,
				date INTEGER NOT NULL,
				close_date INTEGER NULL,
				cause INTEGER NULL,
				issue INTEGER NOT NULL
				);
		INSERT INTO tasks SELECT id, task, state, executor, cost, reason, reporter, date, close_date, cause, issue FROM tasks_backup;
		COMMIT;
		";
		
		$qa = explode(';', $q);
		$con = new Connect();
		$db = $con->open();
		foreach ($qa as $e)  {
			$db->query($e);
		}
		$db->close();
	}
	
	function check_potentials() {
		/*potential cause*/
		$schema = $this->connect->fetch_assoc("PRAGMA table_info(causes)");
		foreach ($schema as $column)
			if ($column['name'] == 'potential')
				return true;
		
		return false;
	}
	
	function do_potentials() {
		$this->connect->errquery("ALTER TABLE causes ADD COLUMN potential INTEGER DEFAULT 0");
	}
	
	function check_causetask() {
		/*cause in task*/
		$schema = $this->connect->fetch_assoc("PRAGMA table_info(tasks)");
		foreach ($schema as $column)
			if ($column['name'] == 'cause')
				return true;
		return false;
	}
	
	function do_causetask() {
			global $errors;
			$this->connect->errquery("ALTER TABLE tasks ADD COLUMN cause INTEGER NULL");
			$issues = $this->connect->fetch_assoc("SELECT * FROM issues");
			foreach($issues as $issue) {
				$id = $issue[id];
				$tasks = $this->connect->fetch_assoc("SELECT * FROM tasks WHERE issue=$id");
				$koryg = array();
				$zapo = array();
				foreach($tasks as $task) {
					if($task[action] == 1)
						$koryg[] = $task[id];
					else if($task[action] == 2)
						$zapo[] = $task[id];
				}
				$causes = $this->connect->fetch_assoc("SELECT * FROM causes WHERE issue=$id");
				
				if(count($causes) > 1 || count($causes) == 0) {
					if(count($koryg) > 0) {
						$lastid = $this->connect->ins_query("INSERT INTO 
								causes(potential, cause, rootcause, reporter, date, issue) VALUES
								(0, 'Zadania nie przypisane do przyczyn.', 8, '',  ".time().", $id)");
								
						foreach($koryg as $tid)
							$this->connect->errquery("UPDATE tasks SET cause=$lastid WHERE id=$tid");
					}
					if(count($zapo) > 0) {
						$lastid = $this->connect->ins_query("INSERT INTO 
								causes (potential, cause, rootcause, reporter, date, issue) VALUES
								(1, 'Zadania nie przypisane do potencjalnej przyczyn.', 8, '',  ".time().", $id)");
						foreach($zapo as $tid)
							$this->connect->errquery("UPDATE tasks SET cause=$lastid WHERE id=$tid");
					}
				} else {
					$cid = $causes[0][id];
					if(count($koryg) == 0 && count($zapo) > 0) {
						$this->connect->errquery("UPDATE causes SET potential=1 WHERE id=$cid");
						foreach(array_merge($koryg, $zapo) as $tid){
							$this->connect->errquery("UPDATE tasks SET cause=$cid WHERE id=$tid");
							
						}
					} else foreach(array_merge($koryg, $zapo) as $tid)
						$this->connect->errquery("UPDATE tasks SET cause=$cid WHERE id=$tid");
				}
			}
	}
	
	function check_rementity() {
		$q = "PRAGMA table_info(issues)";
		$a = $this->connect->fetch_assoc($q);
		$entity = false;
		foreach ($a as $r) 
			if ($r['name'] == 'entity')
				return false;
		return true;
	}
	
	function do_rementity() {
	$createq = "CREATE TABLE IF NOT EXISTS issues (
				id INTEGER PRIMARY KEY,
				priority INTEGER NOT NULL DEFAULT 0,
				title TEXT NOT NULL,
				description TEXT NOT NULL,
				state INTEGER NOT NULL,
				opinion TEXT NULL,
				type INTEGER NOT NULL,
				coordinator TEXT NOT NULL,
				reporter TEXT NOT NULL,
				date INTEGER NOT NULL,
				last_mod INTEGER)";
		$q = "	BEGIN TRANSACTION;
			CREATE TEMPORARY TABLE issues_backup
			(
					id INTEGER PRIMARY KEY,
					priority INTEGER NOT NULL DEFAULT 0,
					title TEXT NOT NULL,
					description TEXT NOT NULL,
					state INTEGER NOT NULL,
					opinion TEXT NULL,
					type INTEGER NOT NULL,
					coordinator TEXT NOT NULL,
					reporter TEXT NOT NULL,
					date INTEGER NOT NULL,
					last_mod INTEGER);
			INSERT INTO issues_backup SELECT
					id,
					priority,
					title,
					description,
					state,
					opinion,
					type,
					coordinator,
					reporter,
					date,
					last_mod
				FROM issues;
			DROP TABLE issues;
			$createq;
			INSERT INTO issues SELECT 
					id,
					priority,
					title,
					description,
					state,
					opinion,
					type,
					coordinator,
					reporter,
					date,
					last_mod
				FROM issues_backup;
			DROP TABLE issues_backup;
			COMMIT;
			";
			$qa = explode(';', $q);
			$con = new Connect();
			$db = $con->open();
			foreach ($qa as $e)  {
				$db->query($e);
			}
			$db->close();
	}
	
	function check_rootcause() {
		$q = "SELECT name FROM sqlite_master WHERE type='table' AND name='rootcauses'";
		$r = $this->connect->fetch_assoc($q);
		if (count($r) == 0)
			return false;
		return true;
	}
	
	function do_rootcause() {
		$q = "CREATE TABLE IF NOT EXISTS rootcauses (
				id INTEGER PRIMARY KEY,
				pl VARCHAR(100) NOT NULL,
				en VARCHAR(100) NOT NULL)";
		$this->connect->errquery($q);

			include DOKU_PLUGIN."bez/lang/en/lang.php";
			$en = $lang;
			include DOKU_PLUGIN."bez/lang/pl/lang.php";
			$pl = $lang;

			$types = array(	'manpower',
							'method',
							'machine',
							'material',
							'managment',
							'measurement',
							'money',
							'environment',
							'communication'
						);
			for ($i=0;$i<count($types);$i++){
				$data = array(
					'en' => $en[$types[$i]],
					'pl' => $pl[$types[$i]]
				);
				$this->connect->errinsert($data, 'rootcauses');
			}

			$this->connect->errquery("UPDATE causes SET rootcause=rootcause+1");
	}
	
	function check_add_plan_date_to_tasks() {
		$q = "PRAGMA table_info(tasks)";
		$a = $this->connect->fetch_assoc($q);
		$entity = false;
		foreach ($a as $r) 
			if ($r['name'] == 'all_day_event')
				return true;
		return false;
	}
	
	function do_add_plan_date_to_tasks() {
		$q = "ALTER TABLE tasks ADD COLUMN all_day_event INTEGER DEFAULT 0";
		$this->connect->errquery($q);
		$q = "ALTER TABLE tasks ADD COLUMN plan_date TEXT NULL";
		$this->connect->errquery($q);
		$q = "ALTER TABLE tasks ADD COLUMN start_time TEXT NULL";
		$this->connect->errquery($q);
		$q = "ALTER TABLE tasks ADD COLUMN finish_time TEXT NULL";
		$this->connect->errquery($q);
	}
	
	function check_add_type_to_tasks() {
		$q = "PRAGMA table_info(tasks)";
		$a = $this->connect->fetch_assoc($q);
		$entity = false;
		foreach ($a as $r) 
			if ($r['name'] == 'tasktype')
				return true;
		return false;
	}
	
	function do_add_type_to_tasks() {
		$q = "ALTER TABLE tasks ADD COLUMN tasktype INTEGER NULL";
		$this->connect->errquery($q);
	}
	
	
	function check_types() {
		$q = "SELECT name FROM sqlite_master WHERE type='table' AND name='issuetypes'";
		$r = $this->connect->fetch_assoc($q);
		if (count($r) == 0)
			return false;
		return true;
	}
	
	function do_types() {
	
		$q = "CREATE TABLE IF NOT EXISTS issuetypes (
				id INTEGER PRIMARY KEY,
				pl VARCHAR(100) NOT NULL,
				en VARCHAR(100) NOT NULL)";
		$this->connect->errquery($q);

			include DOKU_PLUGIN."bez/lang/en/lang.php";
			$en = $lang;
			include DOKU_PLUGIN."bez/lang/pl/lang.php";
			$pl = $lang;

			$types = array('type_noneconformity_internal',
							'type_noneconformity_customer',
							'type_noneconformity_supplier',
							'type_threat',
							'type_opportunity');
			$issuetypes = array_flip($types);

			/*mapowanie[type][enitiy id] = new type id*/
			$nist = array(array(), array(), array(), array(), array());

			$result = $this->connect->fetch_assoc("SELECT * FROM entities");
			foreach ($types as $type)
				foreach ($result as $entity) {
					$data = array(
						'en' => $en[$type].' '.$entity['entity'],
						'pl' => $pl[$type].' '.$entity['entity'],
					);
					$this->connect->errinsert($data, 'issuetypes');
					$nist[$issuetypes[$type]][$entity['entity']] = $this->connect->lastid;
				}
			$result = $this->connect->fetch_assoc("SELECT * FROM issues");
			foreach($result as $r) {
				$id = $r['id'];
				$type = $r['type'];
				$entity = $r['entity'];

				$newtype = $nist[$type][$entity];

				$this->connect->errquery("UPDATE issues SET type=$newtype WHERE id=$id");
			}
	}
	
	
	function check_task_issue_null() {
		$q = "PRAGMA table_info(tasks)";
		$a = $this->connect->fetch_assoc($q);
		$entity = false;
		foreach ($a as $r) 
			if ($r['name'] == 'issue' && $r['notnull'] == 1) {
				return false;
			}
		return true;
	}
	
	function do_task_issue_null() {
		$createq = "CREATE TABLE IF NOT EXISTS tasks (
				id INTEGER PRIMARY KEY,
				task TEXT NOT NULL,
				state INTEGER NOT NULL,
				tasktype INTEGER NULL,
				executor TEXT NOT NULL,
				cost INTEGER NULL,
				reason TEXT NULL,
				reporter TEXT NOT NULL,
				date INTEGER NOT NULL,
				close_date INTEGER NULL,
				cause INTEGER NULL,
				plan_date TEXT NULL,
				all_day_event INTEGET DEFAULT 0,
				start_time TEXT NULL,
				finish_time TEXT NULL,
				issue INTEGER NULL
				)";
				
		$q = "	BEGIN TRANSACTION;
			CREATE TEMPORARY TABLE tasks_backup
			(
				id INTEGER PRIMARY KEY,
				task TEXT NOT NULL,
				state INTEGER NOT NULL,
				tasktype INTEGER NULL,
				executor TEXT NOT NULL,
				cost INTEGER NULL,
				reason TEXT NULL,
				reporter TEXT NOT NULL,
				date INTEGER NOT NULL,
				close_date INTEGER NULL,
				cause INTEGER NULL,
				plan_date TEXT NULL,
				all_day_event INTEGET DEFAULT 0,
				start_time TEXT NULL,
				finish_time TEXT NULL,
				issue INTEGER NULL
				);
			INSERT INTO tasks_backup SELECT
					id,
					task,
					state,
					tasktype,
					executor,
					cost,
					reason,
					reporter,
					date,
					close_date,
					cause,
					plan_date,
					all_day_event,
					start_time,
					finish_time,
					issue
				FROM tasks;
			DROP TABLE tasks;
			$createq;
			INSERT INTO tasks SELECT
					id,
					task,
					state,
					tasktype,
					executor,
					cost,
					reason,
					reporter,
					date,
					close_date,
					cause,
					plan_date,
					all_day_event,
					start_time,
					finish_time,
					issue
				FROM tasks_backup;
			DROP TABLE tasks_backup;
			COMMIT;
			";
			$qa = explode(';', $q);
			$con = new Connect();
			$db = $con->open();
			foreach ($qa as $e)  {
				$db->query($e);
			}
			$db->close();
	}
	
	function check_proza_import() {
		$fname = DOKU_INC . 'data/proza_imported';
		return file_exists($fname);
	}
	
	function do_proza_import() {
			$con = new Connect();
			//$bez = $con->open();
			$tasko = new Tasktypes();
			$to = new Tasks();
			$proza = new SQLite3(DOKU_INC . 'data/proza.sqlite');
			
			//groupy
			$z_prozy_do_bezu = array();//mapownaie grup
			$r = $proza->query("SELECT * FROM groups");
			while ($w = $r->fetchArray(SQLITE3_ASSOC)) {
				$post = $w;
				unset($post['id']);
				$tasko->add($post);
				$lastid = $tasko->lastid();
				$z_prozy_do_bezu[$w['id']] = $lastid;
			}
			
			$r = $proza->query("SELECT * FROM events");
			while ($w = $r->fetchArray(SQLITE3_ASSOC)) {
				$rec = array(
					'task' => $w['assumptions'],
					'state' => $w['state'],
					'tasktype' => $z_prozy_do_bezu[$w['group_n']],
					'executor' => $w['coordinator'],
					'cost' => $w['cost'],
					'reason' => $w['summary'],
					'reporter'	=> $w['coordinator'],
					'date'	=> time(),
					'all_day_event' => 1,
					'plan_date' => $w['plan_date']
					
				);
				if ($w['finish_date'] != '')
					$rec['close_date'] = strtotime($w['finish_date']);
					
				$to->errinsert($rec, 'tasks');
			}
			
			$proza->close();
			//$bez->close();
			$fname = DOKU_INC . 'data/proza_imported';
			fopen($fname, "w");
	}
	
	private $actions = array(
				
				array('1. Słownik kategorii przyczyn', 'check_rootcause', 'do_rootcause'),
				array('2. Słownik typów problemów', 'check_types', 'do_types'),
				array('3. Usunięcie podmiotu w problemach', 'check_rementity', 'do_rementity'),
				array('4. Kolumna "potential" w zadaniach', 'check_potentials', 'do_potentials'),
				array('5. Zadania przypisane do przczyn', 'check_causetask', 'do_causetask'),
				array('6. Usunięcie kolumny "action" z tabeli zadań', 'check_remove_action_from_tasks', 'do_remove_action_from_tasks'),
				array('7. Dodanie planowanej daty do zadań w BEZie', 'check_add_plan_date_to_tasks', 'do_add_plan_date_to_tasks'),
				array('8. Dodanie typu dla zadań niezależnych w BEZie', 'check_add_type_to_tasks', 'do_add_type_to_tasks'),
				array('9. Zmiana kolumny issue w zadaniach na NULL.',
				'check_task_issue_null', 'do_task_issue_null'),
				array('10. Zaimportuj zadania z PROZY.',
				'check_proza_import', 'do_proza_import')
				);
	/**
	 * handle user request
	 */
	function handle() {
		global $errors;
 
	  if (!isset($_POST['applay'])) return;   // first time - nothing to do
	  if (!checkSecurityToken()) return;
	  //importuj
	  $this->exp = true;
	  $keys = array_keys($_POST[applay]);
	  $pr_id = $keys[0];
	  if (array_key_exists($pr_id, $this->actions)) {
	  	$fname = $this->actions[$pr_id][2];
	  	$this->$fname();
	  }
	}
	
	/**
	 * output appropriate html
	 */
	function html() {
		global $errors, $conf;
	  ptln('<h1>'.$this->getMenuText($conf['lang']).'</h1>');
	  if ($this->exp == true) {
	  		if (is_array($errors))
				foreach ($errors as $error) {
					echo '<div class="error">';
					echo $error;
					echo '</div>';
				}
	  }
	  	  ptln('<form action="'.wl($ID).'" method="post">');
	  // output hidden values to ensure dokuwiki will return back to this plugin
	  ptln('  <input type="hidden" name="do"   value="admin" />');
	  ptln('  <input type="hidden" name="page" value="bez_dbschema" />');
	  formSecurityToken();
	  ptln('<table>');
	  ptln('<tr><th>Akcja</th><th>Status</th></tr>');
	  $i = 0;
	  foreach ($this->actions as $action) {
	  	$name = $action[0];
	  	$is_applaied = call_user_func(array($this, $action[1]));
		if ($is_applaied) ptln('<tr style="background-color: #0f0">');
		else ptln('<tr style="background-color: #f00">');
		
	  	ptln("<td>$name</td>");
	  	
	  	if ($is_applaied) ptln("<td>Zastosowana</td>");
		else ptln('<td><input type="submit" name="applay['.$i.']"  value="Zastosuj" /></td>');
		
	  	ptln('</tr>');
	  	$i++;
	  }
	  ptln('</table>');
	  ptln('</form>');
	}
 
}

