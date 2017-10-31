<?
session_start();
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include "../getEmplyeeInfo.php";
include "lib.php";
include "../emplyee_dutylog/volunteers_duty.php";

$token = $_GET['token'];
$userID = $_GET['userID'];
			//action

queryData($token,$userID);
//queryData($token,$empID,$act);
function queryData($token,$userID){
    $result = dbVID($userID); 
    if($result =='1') echo '失敗';

}

function dbempID($userID){
    $sql = "select empID,empName,cardNo,isOnduty,noSignIn from emplyee where empID='$userID'";
    $rs = db_query($sql);
    $result = db_num_rows($rs);
    if($result==1){
        echo 'B';
    }else{
        echo 1;
    }
}
function dbVID($userID){
    include_once("../config_volunteers.php");
    include_once("../system/db.php");
    $db = new db();
    $sql = "select id,VID,name,cardNo from volunteers where VID=? and status = 1";
    $rs = $db->query($sql, array($userID));
    $result = $db->num_rows($rs);
    if($result==1){
        echo  'A';
    }else{
        dbempID($userID);
    }
}
?>