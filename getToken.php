<?
session_start();
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include "../getEmplyeeInfo.php";
include "lib.php";

$act = $_GET['act'];
$pwd = $_GET['pwd'];


getToken($act,$pwd);

function getToken($act,$pwd){
	$rztAry = array();
	$sql="select empName from emplyee ";
	$sql.="where empID='$act' and password='$pwd'";
	$rs=db_query($sql);
	if(!db_eof($rs)){
		$token = genToken();
		$_SESSION['token'] = $token;
		$_SESSION['token_time'] = time();
		echo '{"err_code":0,"err_message":"請求作業成功","token":"'.$token.'"}';		
	} else {
		echo '{"err_code":1,"err_message":"帳號或密碼錯誤","act":"'.$act.'"}';
	}
}

function genToken(){
	$sid = session_id();	

	$digits = 4;
	$rndnums=rand(pow(10, $digits-1), pow(10, $digits)-1);
	return substr($sid,0,4).substr($sid,-4,4).$rndnums;
}
?>