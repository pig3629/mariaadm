<?
session_start();
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include "../getEmplyeeInfo.php";
include "lib.php";
include "../emplyee_dutylog/volunteers_duty.php";

$token = $_GET['token'];
$userID = $_GET['userID'];
$dType = $_GET['dType'];
$temp='';
date_default_timezone_set("Asia/Taipei");//設成台灣時間
$result = array('severTime'=>date("Y/m/d H:i:s"), 'gap'=>0, 'msg'=>'刷卡失敗.!!', 'type'=>1);
queryData($token,$userID,$dType);
//queryData($token,$empID,$act);
function queryData($token,$userID,$dType){
    global $temp;
    $type = dbVID($userID); 
    if($temp == ''){
        $result['identy'] = '身分失敗'; 
        echo json_encode($result);
        exit;
    }else{
        $result['identy'] = $temp; 
        if(empty($dType)) $dType = '上班'; 
        $result['msg'] = dType_chk($dType);
        echo json_encode($result);
    }
    //echo json_encode($result);
}

function dbempID($userID){
    global $temp;
    $sql = "select empID,empName,cardNo,isOnduty,noSignIn from emplyee where empID='$userID'";
    $rs = db_query($sql);
    if(db_num_rows($rs)==1){
         $temp = '員工';
    }
    return  $temp; 
}
function dbVID($userID){
    global $temp;
    include_once("../config_volunteers.php");
    include_once("../system/db.php");
    $db = new db();
    $sql = "select id,VID,name,cardNo from volunteers where VID=? and status = 1";
    $rs = $db->query($sql, array($userID));
    $type = $db->num_rows($rs);
    if($type==1){
        $temp = '志工';
        return  $temp; //志工
    }else{
        $temp = dbempID($userID);
    }
}
function dType_chk($dType){
    switch ($dType) {
    case '上班':
        $dType = 'duty_in';
        break;
    case '下班':
        $dType = 'duty_out';
        break;
    case '外出':
        $dType = 'outoffice_out';
        break;
    case '返回':
        $dType = 'outoffice_back';
        break;
    }
    return $dType;
}


?>