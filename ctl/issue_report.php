<?php
include_once DOKU_PLUGIN."bez/models/entities.php";
include_once DOKU_PLUGIN."bez/models/issuetypes.php";
include_once DOKU_PLUGIN."bez/models/users.php";
include_once DOKU_PLUGIN."bez/models/issues.php";
include_once DOKU_PLUGIN."bez/models/states.php";
include_once DOKU_PLUGIN."bez/models/tasks.php";

$issue_id = $params[0];
$action = $params[1];

if	(!$helper->user_editor() ||
	($issue_id != NULL && !$helper->user_coordinator($issue_id))) {
	$errors[] = $bezlang['error_issue_report'];
	$controller->preventDefault();
} 

$isso = new Issues();
$usro = new Users();

if (count($_POST) > 0) {

	if ($action == 'update') {
		$updated = $isso->update($_POST, array(), $issue_id);
		if (count($errors) == 0 && !in_array($updated['coordinator'], $isso->coord_special)) {
			$coord = $updated['coordinator'];

			$to = $usro->name($coord).' <'.$usro->email($coord).'>';
			$subject = "[$conf[title]] Zmiana w problemie: #".$isso->lastid();
			$body = "$uri".$this->helper->issue_uri($isso->lastid());
			$this->helper->mail($to, $subject, $body);
		}
	} else {
		$data = array('reporter' => $usro->get_nick(), 'date' => time());

		$stao = new States();
		if ($_POST['coordinator'] == NULL)
			$data['coordinator'] = $stao->proposal();
		$data['state'] = $stao->id('opened');

		$inserted = $isso->add($_POST, $data);
		if (count($errors) == 0 && !in_array($inserted['coordinator'], $isso->coord_special)) {
			$coord = $inserted['coordinator'];
			$to = $usro->name($coord).' <'.$usro->email($coord).'>';
			$subject = "[$conf[title]] Zostałeś przypisany do problemu: #".$isso->lastid();
			$body = "$uri".$this->helper->issue_uri($isso->lastid());
			$this->helper->mail($to, $subject, $body);
		}
	}
	$value = $_POST;
	if (count($errors) == 0)
		header('Location: ?id=bez:issue_show:'.$isso->lastid());
} elseif ($issue_id != NULL) {
	$value = $isso->get_clean($issue_id);
	$tasko = new Tasks();
	$template['any_task_open'] = $tasko->any_open($issue_id);
	$action = 'update';
} else {
	$action = 'add';
}

$template['action'] = $action;

$ento = new Entities();
$template['entities'] = $ento->get_list();
$isstyo = new Issuetypes();
$template['issue_types'] = $isstyo->get();

$stao = new States();
$template['issue_states'] = $stao->get();

$template['user_admin'] = $helper->user_admin();
if ($issue_id != NULL) 
	$template['user_coordinator'] = $helper->user_coordinator($issue_id);
$template['nicks'] = $usro->get();

$template['uri'] = $uri;
$template['issue_id'] = $issue_id;


