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
queryData($token,$depID,$empID,$act);
(is_numeric($empID))?$type = 1:$type = 0;   // 若為1，代表empID 是數字開頭


function queryData($token,$depID,$empID,$act){
	global $emps,$items;
	/*if(!checkToken($token)){
		echo '{"err_code":1,"err_message":"權限逾時或沒有權限","token":"'.$token.'"}';	
		exit;
	}*/
	$items = db2items($depID,$empID);   // [0] department為depID資料,[1] 為leadID資料
	$emps = db2emps($depID,$empID);		
	$tree = items2treeN($items); 
	if($depID=='%') {
		$object = tree2view($tree,$depID,$empID,$act);
	} else {
		foreach($tree as $k=>$v) break;
		$object = tree2view($v,$depID,$empID,$act);
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


function db2items($depID,$empID,$type){
	$sql[0]="select * from department where depID like '$depID%' order by depID ";
	$sql[1]="select * from department where leadID like '$empID' order by depID"; //找上司的部門
	$rs=array();$r=array();
	$rs[0]=db_query($sql[0]);
	$rs[1]=db_query($sql[1]);
	if($empID!=''){
	if(!db_eof($rs[1])){
			while($r[1]=db_fetch_array($rs[1])){
				$depName[]=$r[1][depName];   	//上司管理的有那些部門
				}
			}
	}
	if(!db_eof($rs[0])){
		while($r[0]=db_fetch_array($rs[0])){
			$leadID[]=$r[0][leadID]; 			//透過depID找出對應的上司
			
		}
	}	
	$leadID=array("leadID"=>$leadID);
	return array($depName,$leadID);
}
function db2emps($depID,$empID,$type){
	global $items;
	//if((!is_null($items[1][leadID]))&& $depID=''){ $empID=$items[1][leadID];}
	$sql[0]="select * from emplyee where depID like '$depID' order by depID "; //找部門有甚麼員工
	$sql[1]="select * from emplyee where empID like '$empID' order by depID "; //找emplyee所屬的員工編號
	$rs=array();$r=array();
	$rs[0]=db_query($sql[0]);
	$rs[1]=db_query($sql[1]);
	if($empID!=''){
		if(!db_eof($rs[1])){
			while($r[1]=db_fetch_array($rs[1])){
					 $emplyeeName=$r[1][empName];  
					 $emplyeeID=$r[1][empID];  
					 $emplyeeCard=$r[1][CardNo];  
					 $emplyeeDepID=$r[1][depID]; 
				}
			}
		}
	if(!db_eof($rs[0])){
		while($r[0]=db_fetch_array($rs[0])){
			$EmplyeeName[]=$r[0][empName];  
			$EmplyeeID[]=$r[0][empID];  
		}
	
	}	
	$sql[2]="select * from department where depID like '$emplyeeDepID'";  //得到員工資料後，再透過員工的部門去查所屬上司
	$rs[2]=db_query($sql[2]);
	if(!db_eof($rs[2])){
		while($r[2]=db_fetch_array($rs[2])){
			$emplyeeDep=$r[2][depName];
			$emplyeeLead=$r[2][leadID];
			}
		}
		
	$sql[3]="select * from emplyee where empID like '$emplyeeLead'"; 	//將上司的資料印出來
	$rs[3]=db_query($sql[3]);
	if(!db_eof($rs[3])){
		while($r[3]=db_fetch_array($rs[3])){
			$emplyeeName2=$r[3][empName];  
			$emplyeeID2=$r[3][empID];  
			$emplyeeCard2=$r[3][CardNo];  
			$emplyeeDepID2=$r[3][depID]; 
	  	 }
		}
	$employes=array("name"=>$EmplyeeName,"empID"=>$EmplyeeID); //找部門有甚麼員工，當depID 有值可以印出這個結果
	$empDetail=array("name"=>$emplyeeName,"empID"=>$emplyeeID,"cardNo"=> $emplyeeCard,"DepID"=>$emplyeeDepID,"pic"=>'http://'.$_SERVER['HTTP_HOST'].'/data/emplyee/'.$emplyeeID.'.jpg');
	$empDetail2=array("name"=>$emplyeeName2,"empID"=>$emplyeeID2,"cardNo"=> $emplyeeCard2,"DepID"=>$emplyeeDepID2,"pic"=>'http://'.$_SERVER['HTTP_HOST'].'/data/emplyee/'.$emplyeeID2.'.jpg');
	return array($empDetail,$employes,$emplyeeDep,$empDetail2);
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


function tree2view(&$tree,$depID,$empID,$act){
	global $departmentinfo,$emps,$items;
	$object=new Departments();
	$object->dept_id = $depID;
	$object->dep_manger_name = $emps[3]; 
	if(!is_null($empID)){	
		$object->emp_manger = $items[0]; //當empID有值，若是主管會印出管理部門的名子
		$object->emp_detail = $emps[0]; //當empID有值，會印出員工的名字
	}
	if(!is_null($depID)){
	$object->dep_employes = $emps[1]; 
	$object->dep_lead = $items[1][leadID];
	} 
	if($departmentinfo[$depID]){
		$object->name =$departmentinfo[$depID];
	}elseif($emps[3]==$emps[0][empID]){
		$object->name='';
	}else{$object->name=$emps[2];}
	foreach($tree as $k=>$v) {	
		$object->sub_departments[]=tree2view($tree[$k],$k,$act); 
		
	}
	if($act=="emp"){
		$object->employess=$emps[$depID];
	}
	return $object;
}

?>