<?
session_start();
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include "lib.php";
//http://mariaadm/api/getToken.php?act=99998&pwd=99998 <-取得token
//http://mariaadm/api/cancelPunch.php?token
$result = array('err_code'=>1);

if(empty($_REQUEST['token'])){
    $result['err_message']= 'token錯誤';
    echo json_encode($result);
    exit;
}
/* 檢察 */
if(!checkToken($_REQUEST['token'])){ 
    $result['err_message']= '權限逾時或沒有權限';
    echo json_encode($result);
    exit;
}
if(empty($_REQUEST['userID'])){
    $result['err_message']= '員工參數傳遞錯誤';
    echo json_encode($result);
    exit;
}	
if(empty($_REQUEST['dateTime'])){
    $result['err_message']= '時間傳遞錯誤';
    echo json_encode($result);
    exit;
}	
/* 檢查是否為當日 */
$isToday=date("Y/m/d",strtotime($_REQUEST['dateTime']));
if(date("Y/m/d")!=$isToday){
    $result['err_message']= '時間逾期';
    echo json_encode($result);
    exit;
}
/* 員工 */
$sql = "select empID from emplyee where empID='". $_REQUEST['userID']."' and isOnduty >= 1 and noSignIn = 0 "; //順便查是否在職
$rs = db_query($sql); 
if(db_eof($rs)){
    $result['err_message']= '查無此人';
    echo json_encode($result);
    exit;
}
$r = db_fetch_array($rs);
$userID = $r['empID'];

/* 刪除 */
$sql = "select empID from emplyee_dutylog where empID='$userID' and d_datetime = '".$_REQUEST['dateTime']."'";
$rs = db_query($sql);
if(db_eof($rs)){
    $result['err_message']= '查無打卡日期紀錄';
    echo json_encode($result);
    exit;
}
$sql = "delete from emplyee_dutylog where empID='$userID' and d_datetime = '".$_REQUEST['dateTime']."'";
db_query($sql);
$result['err_message']= '請求作業成功';
$result['err_code']= 0;
echo json_encode($result);

?>