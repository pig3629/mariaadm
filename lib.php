<?

function checkToken($value){
	$dt = time() -  $_SESSION['token_time'];
	if($_SESSION['token']==$value && $dt<60) return true; else return false;
}	


?>