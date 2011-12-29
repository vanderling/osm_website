<?php 

/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

$script_name = $_SERVER['SCRIPT_NAME'];
$pos = strrpos($script_name,'/');
if ($pos >= 0)
{
	$script_name = substr($script_name,$pos+1);
}

if($script_name=='default.php')
 {list($sec_name, $sec_password)   = explode("-", $cookie_data);}
 else
 {list($sec_name, $sec_password)   = explode("-", $_COOKIE['cookie_info']);}

$query            = "SELECT * FROM user WHERE us_name = \"".$sec_name."\" LIMIT 1";
$result_us        = mysql_query($query) or die (mysql_error());
$myrow_us         = mysql_fetch_array($result_us);
$sec_admin        = $myrow_us['us_admin'];
$sec_readonly     = $myrow_us['us_readonly'];
$area_access      = unserialize($myrow_us['us_area_access']);
$country_access   = unserialize($myrow_us['us_country_access']);
$eth_code_access  = unserialize($myrow_us['us_eth_code_access']);
$menu_tab_access  = unserialize($myrow_us['us_menu_tab_access']);
$sec_code         = $eth_code_access;

?>
