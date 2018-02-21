<?php
require_once './conf/config.php';

function login($login, $password, $db){
	try {
    $q = $db->query('SELECT id, login, password FROM user');
    $row = $q->fetchAll(PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
	    print "Couldn't insert a row: " . $e->getMessage();
	}

    foreach($row as $regUser){
        if (($regUser['login'] == $login) && (hash_equals($regUser['password'], crypt($password, $regUser['password'])))) {
            unset($regUser['password']);
            $_SESSION['user'] = $regUser;

            return true;
        }
    }
    return false;
}

function isPost(){
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function getParam($name){
    return isset($_REQUEST[$name]) ? trim(htmlspecialchars($_REQUEST[$name])) : null;
}

function isAuthorized(){
    return !empty($_SESSION['user']);
}

function getAuthorizedUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function redirect($page) {
    header("Location: $page.php");
    die;
}

function logout() {
    if (isAuthorized()) {
        session_destroy();
    }
    redirect('index');
}

class ShowAllInformation
{
	public $data;
	private $db;

	public function __construct($data, $db)
	{
		$this->data = $data;
		$this->db = $db;
	}

	public function getMyInfo()
	{

		$db = $this->db;
		$data = $this->data;
		$res = 'SELECT user.login AS responsible, task.* FROM user JOIN task ON task.assigned_user_id = user.id AND user_id = ' . $data['id'];

		try {
		    $q = $db->query($res);
		    $row = $q->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    print "Couldn't insert a row: " . $e->getMessage();
		}

		return $row;
	}

	public function shiftResponsibility()
	{
		$db = $this->db;
		$data = $this->data;
		$res = 'SELECT id, login FROM user WHERE id <> ' . $data['id'];

		try {
		    $q = $db->query($res);
		    $executor = $q->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    print "Couldn't insert a row: " . $e->getMessage();
		}

		return $executor;

	}

	public function getOtherInfo()
	{

		$db = $this->db;
		$data = $this->data;
		$res = "SELECT user.login AS author, task.* FROM user JOIN task ON task.user_id = user.id
		        AND task.assigned_user_id = {$data['id']} AND task.user_id <> {$data['id']}";

		try {
		    $q = $db->query($res);
		    $row = $q->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
		    print "Couldn't insert a row: " . $e->getMessage();
		}

		return $row;
	}

}

class Registration
{
	public $login;
	public $password;
	private $db;

	public function __construct($login, $password, $db)
	{
		$this->login = $login;
		$this->password = $password;
		$this->db = $db;
	}

	public function createUser()
	{
		$db = $this->db;

		$login = trim(htmlspecialchars($this->login));
		$password = trim(htmlspecialchars($this->password));

		if((strlen($login) > 0) && (strlen($password) > 0)){
			$password = crypt($password);
			try {
				$q = $db->prepare('INSERT INTO user (login, password) VALUES (?,?)');
				$q->execute(array($login, $password));

			} catch (PDOException $e) {
				print "Couldn't create user: " . $e->getMessage();
			}

			try {
				$res = "SELECT id, login FROM user WHERE login = '" . $login . "'";
				$m = $db->query($res);
				$row = $m->fetchAll(PDO::FETCH_ASSOC);

				$_SESSION['user'] = $row;

			} catch (PDOException $e) {
				print "Couldn't select user: " . $e->getMessage();
			}

			return true;
		}else{
			return false;
		}
	}
}


class DoAct
{
	public $id;
	public $watToDo;
	private $db;

	public function __construct($id = '', $watToDo = '', $db)
	{
		$this->id = $id;
		$this->watToDo = $watToDo;
		$this->db = $db;
	}

	public function makeDone() 
	{
		$db = $this->db;
		$res = "UPDATE task SET is_done = 1 WHERE id = $this->id";

		try {
			$q = $db->exec($res);
		} catch (PDOException $e) {
			print "Couldn't update a row: " . $e->getMessage();
		}
	}

	public function deliteTask() 
	{
		$db = $this->db;
		$res = "DELETE FROM task WHERE id = $this->id";

		try {
			$q = $db->exec($res);
		} catch (PDOException $e) {
			print "Couldn't delite a row: " . $e->getMessage();
		}
	}
}

class DoAsExecutor
{
	public $id;
	private $db;

	public function __construct($id, $db)
	{
		$this->id = $id;
		$this->db = $db;
	}

	public function makeDone() 
	{
		$db = $this->db;
		$res = "UPDATE task SET is_done = 1 WHERE id = $this->id";

		try {
			$q = $db->exec($res);
		} catch (PDOException $e) {
			print "Couldn't update a row: " . $e->getMessage();
		}
	}

	public function deliteTask() 
	{
		$db = $this->db;
		$res = "UPDATE task SET assigned_user_id = user_id WHERE id = $this->id";

		try {
			$q = $db->exec($res);
		} catch (PDOException $e) {
			print "Couldn't delite a row: " . $e->getMessage();
		}
	}
}


class AddTask
{
	private $db;
	private $newTask;
	private $data;

	public function __construct($db, $newTask, $data)
	{
		$this->db = $db;
		$this->newTask = $newTask;
		$this->data = $data;
	}

	public function addNewTask()
	{
		$db = $this->db;
		$data = $this->data;
		$today = date("Y-m-d H:i:s");

		try {
			$q = $db->prepare('INSERT INTO task (user_id, assigned_user_id, description, is_done, date_added) VALUES (?,?,?,?,?)');
			$q->execute(array($data['id'], $data['id'], $this->newTask, 0, $today));
		} catch (PDOException $e) {
			print "Couldn't edit a row: " . $e->getMessage();
		}
	}
}

class SortBy
{
	
	private $db;
	private $sort_by;
	private $data;

	public function __construct($db, $sort_by, $data)
	{
		$this->db = $db;
		$this->sort_by = $sort_by;
		$this->data = $data;
	}

	public function sortingBy()
	{
		$db = $this->db;
		$data = $this->data;
		$res = "SELECT user.login AS responsible, task.* FROM user JOIN 
		task ON task.assigned_user_id = user.id AND user_id = {$data['id']} ORDER BY {$this->sort_by}";

		try {
			$nextView = $db->query($res);
    		$row = $nextView->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			print "Couldn't sort and present a row: " . $e->getMessage();
		}

		return $row;
	}
}

class ChangeExecutor
{

	private $db;
	private $newExecutor;

	public function __construct($db, $newExecutor)
	{
		$this->db = $db;
		$this->newExecutor = $newExecutor;
	}

	public function setNewExecutor()
	{
		$db = $this->db;
		$newExecutor = $this->newExecutor;
		$arr = explode(" ", $newExecutor);
		$res = "UPDATE task SET assigned_user_id = {$arr[0]} WHERE id = {$arr[1]}";

		try {
			$nextView = $db->exec($res);
		} catch (PDOException $e) {
			print "Couldn't sort and present a row: " . $e->getMessage();
		}

	}
}
