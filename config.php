<?php

/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

//ini_set('error_reporting', 'E_ALL & ~E_NOTICE');

//$sec_flag='N';
//if (isset($_COOKIE['cookie_info'])) {$sec_flag='Y';}

// Test values
$temp = 'temp/';
$base = 'C:/Temp/OSM/';

$webhost = "http://localhost";
$hostname = "localhost";
$username = "root";
$password = "tester1";
$dbname   = "osm";

// Real values
/*
$temp = 'temp/';
$base = '/home/kalaam.org/ancientScripture/htdocs/';

$hostname = "localhost";
$username = "ancientScripture";
$password = "BWueZvLHVdBDcPKz";
$dbname   = "ancientScripture";
*/

$link     = mysql_connect($hostname, $username, $password) or die("unable to connect to database");
$db       = mysql_select_db($dbname) or die("Unable to select database");

function translate($phrase, $st)
{
 if(!$st) {$st=$_GET['st'];}
 $myrow = '';
 if($st and $st!='eng')
 {
  $query  = "SELECT * FROM translations_eng WHERE phrase = \"".$phrase."\" ";
  $result = mysql_query($query) or die (mysql_error());
  $myrow  = mysql_fetch_array($result);
 
  $query  = "SELECT * FROM translations_".$st." WHERE id = \"".$myrow['id']."\" ";
  $result = mysql_query($query);// or die (mysql_error());
  $myrow  = mysql_fetch_array($result);
 }

 if($myrow['phrase']) {$phrase = $myrow['phrase'];}
 $phrase = str_replace("'", "`", $phrase);

 return $phrase;
}    

?>