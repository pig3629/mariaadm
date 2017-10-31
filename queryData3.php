<?
session_start();
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include "../getEmplyeeInfo.php";
include "lib.php";

$token = $_GET['token'];
$depID = $_GET['depID'];
$empID = $_GET['empID'];
$act = $_GET['act'];			//action

(is_numeric($empID))?$type = 1:$type = 0;   // 若為1，代表empID 是數字開頭
(is_numeric($depID))?$type2 = 1:$type2 = 0;   // 若為1，代表depID 是數字開頭

queryData($token,$depID,$empID,$act);
//queryData($token,$empID,$act);
function queryData($token,$depID,$empID,$act){
	global $emps;
	/*if(!checkToken($token)){
		echo '{"err_code":1,"err_message":"權限逾時或沒有權限","token":"'.$token.'"}';	
		exit;
	}*/
	$items = db2items($depID,$empID);
	$emps = db2emps($depID,$empID);
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
function db2emps($depID,$empID,$type,$type2){
	$sql=array();$items=array();$r=array();
		if($depID=='' or $type2==1){$sql[0]='';}else{$sql[0]="select * from emplyee where depID like '$depID%' order by depID ";} //找emplyee的所屬depID
		$sql[1]="select * from emplyee where empID like '$empID%' order by depID "; //找emplyee所屬的員工編號
		$sql[2]="select * from department where leadID like '$empID%' order by depID"; //找上司 
		foreach( $sql as $v=>$k){
		$rs[$v]=db_query($sql[$v]);
		if(!db_eof($rs[$v])){
			while($r[$v]=mysql_fetch_assoc($rs[$v])){
				if(!is_array($items[$v][$r[$v][depID]]))	$items[$v][$r[$v][depID]]=array();
				if($items[$v]==$items[1]){
				array_push($items[$v][$r[$v][depID]],array('empID'=>$r[$v][empID],'name'=>$r[$v][empName],'cardNo'=>$r[$v][cardNo],'pic'=>'http://'.$_SERVER['HTTP_HOST'].'/data/emplyee/'.$r[$v][empID].'.jpg'));
				}
			}
		}	
	
	$aa=array_keys($items); //判斷是否有所屬的部門depID
	$answer=empty($aa[0])? 'Y': 'N'; //判斷所屬部門是否為空,Y代表[]為空
	
	//return $items;
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


function tree2view(&$tree,$depID,$act,$empID){
	global $departmentinfo,$emps;
	$object=new Departments();
	$object->dept_id = $depID;
	$object->name = $departmentinfo[$depID]?$departmentinfo[$depID]:'所有部門';
	foreach($tree as $k=>$v) {	
		$object->sub_departments[]=tree2view($tree[$k],$k,$act); 
		//$object->name=
	}
	if($act=="emp"){
		$object->employess=$emps[$k][$depID];
	}
	return $object;
}

?>