<?
header("Content-Type:text/html; charset=utf-8");
include "../config.php";
include_once('../emplyee_dutylog/common.php');

$result = array('err_code'=>1, 'err_message'=>'權限逾時或沒有權限!!');
//http://mariaadm/api/facePunch.php?userID=V0045&location=經度,緯度
if(empty($_REQUEST['userID'])){
    $result['err_message']= '參數傳遞錯誤!!';
    echo json_encode($result);
    exit;
}
$userID = $_REQUEST['userID'];
$dType = (!empty($_REQUEST['dType']))? $_REQUEST['dType']: 'duty_in';
$dateTime = (!empty($_REQUEST['dateTime']))? $_REQUEST['dateTime']: date("Y/m/d H:i:s");
$location = (!empty($_REQUEST['location']))? $_REQUEST['location']: 0;

queryData($userID,$dType,$location,$dateTime);

function queryData($userID,$dType,$location,$dateTime){
    global $result;
    $isUser= dbempID($userID);  
    switch($isUser){
        case 0:
            $result['err_message']= '查無此人!!';
                echo json_encode($result);
                exit;
        case 1:
            $result['err_message']= '已經離職!!';
                echo json_encode($result);
                exit;
        case 2:
            $result['err_message']= '無須打卡!!';
                echo json_encode($result);
                exit;
    }
    
    $ip_lok=dbLocation($location); //$ip_lok=array('刷卡成功or失敗','地點','isGps');
    if($ip_lok['chkIP']== 0 ){
        $result['err_message']= '查無打卡點!!';
        echo json_encode($result);
        exit;
    }
    $dTYpeChk=dType_chk($dType);
    if($dTYpeChk == false){
        $result['err_message']= '班別錯誤!!';
        echo json_encode($result);
        exit;
    }
    switch($isUser['identy']){
        case 0: //志工                   
            $db = new db('volunteers_dutylog');
            $db->row['VID'] = "'".$isUser['ID']."'";
            $db->row['dType'] = "'".$dType."'";
            $db->row['ip'] = "'".$_SERVER['REMOTE_ADDR']."'";
            $db->row['place'] = "'".$ip_lok['place']."'";
            $db->row['d_datetime'] = "'".$dateTime."'";
            if($ip_lok['chkGPS']==1) $db->row['gps'] =  "'".$location."'"; //代表有gps
            $db->insert();
            include_once('../emplyee_dutylog/volunteers_duty.php');
            $vD = new vDuty();
            $data = array(
                'id'=>$isUser['no'], //傳id
                'dType'=>$dType,
                'place'=>$ip_lok['place'],
                'dateTime'=>$dateTime
            );
            $vD->insertDuty($data);
            break;
        case 1: //員工
            if($ip_lok['chkGPS']==1){
                $sql = ' insert into `emplyee_dutylog` (`empID`,`dType`,`ip`,`gps`,`place`,`d_datetime`) 
                values("'.$isUser['ID'].'","'.$dType.'","'.$_SERVER['REMOTE_ADDR'].'","'.$location.'"
                ,"'.$ip_lok['place'].'","'.$dateTime.'")';
            }else{
                $sql = ' insert into `emplyee_dutylog` (`empID`,`dType`,`ip`,`place`,`d_datetime`) 
                values("'.$isUser['ID'].'","'.$dType.'","'.$_SERVER['REMOTE_ADDR'].'","'.$ip_lok['place'].'",
                "'.$dateTime.'")';
            }
            db_query($sql);
            break;
    }
    $result['err_code'] = 0;
    $result['err_message'] = '請求作業成功';
    echo json_encode($result);
}
/* 員工 */
function dbempID($userID){    
    $isUser = 0;
    $sql = "select empID,empName,cardNo,isOnduty,noSignIn from emplyee where empID='$userID'";
    $rs = db_query($sql);    
    if(db_num_rows($rs)==1){
        $r = db_fetch_array($rs);
        if($r['isOnduty']<1){
            $isUser = 1;//'已經離職!!';
        }elseif($r['noSignIn']==1){
            $isUser = 2;//'不須打卡!!';
        }else{
            $isUser = array('identy'=>'1','ID'=>$r['empID']); 
        }   
    }else{
        $isUser = dbVID($userID);
    }
    return  $isUser;
}
/* 志工 */
function dbVID($userID){
    $isUser = 0;
    include_once("../config_volunteers.php");
    include_once("../system/db.php");
    $db = new db();
    $sql = "select id,VID,name,cardNo from volunteers where VID=? and status = 1";
    $rs = $db->query($sql, array($userID));
    $type = $db->num_rows($rs);
    if($type==1){ 
        $r = $db->fetch_array($rs);
        $isUser = array('identy'=>'0','ID'=>$r['VID'],'no'=>$r['id']);  
    }
    return  $isUser; 
}
function dType_chk($dType){
    switch ($dType) {
        case 'duty_in':
            break;
        case 'duty_out':
            break;
        case 'outoffice_out':
            break;
        case 'outoffice_back':
            break;
        default:
            $dType = false;    
            break;
    }
  return $dType;
}
/* 抓ip或gps */
function dbLocation($location){
    $setplace = array('chkIP'=>'0','place'=>''); //刷卡失敗
      //抓打卡機的位置
    $settingData = array();
    $isGps = 0;
    $data = array('type'=>'ip');
    $punchList = get_punch_card_setting($data);
    
    if(count($punchList) > 0) {
        $settingData = $punchList[key($punchList)];
    } elseif(count($punchList) == 0 && $location != '') {
        $isGps = 1;
        $data = array('type'=>'gps', 'val'=>$location);
        $punchList = get_punch_card_setting($data);
        if(count($punchList) > 0) {
        $settingData = $punchList[key($punchList)];
        }
    }
    if(isset($settingData['id'])) {
        $setplace = array('chkIP'=>'1','place'=>$settingData['place'],'chkGPS'=>$isGps); 
    }
    return $setplace;
}
?>