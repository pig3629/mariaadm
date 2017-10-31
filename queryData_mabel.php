<?
session_start();
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include "../getEmplyeeInfo.php";
include "lib.php";

$token = $_GET['token'];
$depID = $_GET['depID'];
$act = $_GET['act'];
queryData($token,$depID,$act);

function queryData($token,$depID,$act){
	global $emps;
	if(!checkToken($token)){
		echo '{"err_code":1,"err_message":"權限逾時或沒有權限","token":"'.$token.'"}';	
		exit;
	}
	$items = db2items($depID);
	$emps = db2emps($depID);
	$tree = items2treeN($items); 
	if($depID=='%') {
		$object = tree2view($tree,$depID,$act);
	} else {
		foreach($tree as $k=>$v) break;
		$object = tree2view($v,$depID,$act);
	}
	echo '{"departments":'.json_encode($object).'}';
}
class Departments {
	public $sub_departments;	//Object Array
	public $name;
	public $dept_id;
}

class Employees {
	public $sub_departments;	//Object Array
	public $name;
	public $dept_id;
	public $employees;			//Object Array
}

function db2items($depID){
	$sql="select * from department where depID like '$depID%' order by depID ";
	$rs=db_query($sql);
	$items=array();
	if(!db_eof($rs)){
		while($r=db_fetch_array($rs)){
			$items[$r[depID]]=$r;
		}
	}
	return $items;
}
function db2emps($depID){
	$sql="select * from emplyee where depID like '$depID%' order by depID ";
	$rs=db_query($sql);
	$items=array();
	if(!db_eof($rs)){
		while($r=db_fetch_array($rs)){
			if(!is_array($items[$r[depID]]))	$items[$r[depID]]=array();
			array_push($items[$r[depID]],array('empID'=>$r[empID],'name'=>$r[empName],'cardNo'=>$r[cardNo],'pic'=>'http://'.$_SERVER['HTTP_HOST'].'/data/emplyee/'.$r[empID].'.jpg'));
		}
	}
	return $items;
}

function items2treeN($items){
	$tree=array();
	//$reftab用來記錄item在tree裏位置的reference.
	$reftab=array();
	$rootID = null;
	foreach($items as $key => $row) {
	 	$pkey=$row['PID'];
		if(!isset($rootID)) $rootID = $pkey; 
		if($pkey==$rootID){ //Initial
			$tree[$key]=array();
			$reftab[$key]=&$tree[$key];
		}else {//一般節點
		    if(!isset($reftab[$pkey])){
		    	$reftab[$pkey]=array();
		    }
		    if(!isset($reftab[$key])){
		    	$reftab[$key]=array();
		    }
		    $reftab[$pkey][$key]=&$reftab[$key];
		}
	}
	return $tree;
}


function tree2view(&$tree,$depID,$act){
	global $departmentinfo,$emps;
	$object=new Departments();
	$object->dept_id = $depID;
	$object->name = $departmentinfo[$depID]?$departmentinfo[$depID]:'所有部門';
	foreach($tree as $k=>$v) {	
		$object->sub_departments[]=tree2view($tree[$k],$k,$act); 
		
	}
	if($act=="emp"){
		$object->employess=$emps[$depID];
	}
	return $object;
}

?>