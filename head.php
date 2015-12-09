<?php 

/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require "authorization.php";

$menu_tabs   = array
 (
  'upload.php'=>'Upload',
  'books.php'=>'Bibles / Books',
  'sync.php'=>'Synchronize / Edit',
  'viewParent.php'=>'View',
  'translations.php'=>'Translations',
  'reprocess.php'=>'Reprocess',
  'user.php'=>'Users'
 );

echo <<<END

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" dir="ltr">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>$system_title</title>
<LINK REL="SHORTCUT ICON" HREF="images/favicon.ico">
<link type="text/css" rel="stylesheet" href="menu.css">

<SCRIPT LANGUAGE=JavaScript>

function login_func()
{
 document.forms[0].devent.value="logout";
 document.forms[0].submit();
}

function check_date(objName)
{
 if (objName.value)
 {
  var wdate  = objName.value;
  var xdate  = '';
  var errmsg = '';

  for (i = 0; i < wdate.length; i++) 
  {
   if (wdate.substr(i,1) >= 0 && wdate.substr(i,1) <= 9) 
   {xdate = xdate + wdate.substr(i,1);}
  }

  if (xdate.length==5) {xdate = '0' + xdate;}
  if (xdate.length==7) {xdate = '0' + xdate.substr(0,3) + xdate.substr(5,2);}
  if (xdate.length==8) {xdate = xdate.substr(0,4) + xdate.substr(6,2);}
 
  if (xdate.length!=6) {errmsg = 'date is invalid - please try again';}

  var wmo = xdate.substr(0,2);
  var wda = xdate.substr(2,2);
  var wyr = xdate.substr(4,2);
 
  if (wmo<1 || wmo>12)                 {errmsg = 'date is invalid - please try again';}
  if (wda<1)                           {errmsg = 'date is invalid - please try again';}
  if (wmo==1  && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}
  if (wmo==2  && (wda<1 || wda > 29))  {errmsg = 'date is invalid - please try again';}
  if (wmo==2  && (wyr % 4) != 0 && wda > 28)  {errmsg = 'date is invalid - please try again';}
  if (wmo==3  && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}
  if (wmo==4  && (wda<1 || wda > 30))  {errmsg = 'date is invalid - please try again';}
  if (wmo==5  && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}
  if (wmo==6  && (wda<1 || wda > 30))  {errmsg = 'date is invalid - please try again';}
  if (wmo==7  && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}
  if (wmo==8  && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}
  if (wmo==9  && (wda<1 || wda > 30))  {errmsg = 'date is invalid - please try again';}
  if (wmo==10 && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}
  if (wmo==11 && (wda<1 || wda > 30))  {errmsg = 'date is invalid - please try again';}
  if (wmo==12 && (wda<1 || wda > 31))  {errmsg = 'date is invalid - please try again';}

  if (errmsg)
  {
   objName.focus();
   objName.value = '';
   alert(errmsg);
   return false;
  } 
  else
  {
   if (wmo.substr(0,1)==0) {wmo = wmo.substr(1,1);}
   xdate = wmo + '-' + wda + '-' + wyr;
   objName.value = xdate;
  }
 }  
}

function check_data_change(url)
{
 if(document.forms[0].data_change.value=='Y')
 {
  answer = confirm('you have unsaved data on this screen.\\nare you sure you want to leave without saving?');
  if(!answer) {return false;}
 } 
 window.location=url;
 return false;
}

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-28615935-2']);
    _gaq.push(['_trackPageview']);

    (function() {
	        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		      })();

			  
</SCRIPT>

</head>

<body>

<form id=form1 name=form1 action="" method=post enctype="multipart/form-data">
<a name="top"> </a>
<input type=hidden name=data_change>

<table id="wrapper" cellpadding="0" cellspacing="0">
 <tr id="banner">
  <td><img src="images/logo.bmp"></td>
 </tr>
 <tr>
  <td>
   <table id="topnav" cellspacing="0" cellpadding="0">
    <tr>
END;

foreach($menu_tabs as $code=>$desc)
{
 if($menu_tab_access[$code]=='on' or $menu_tab_access['everything']=='on')
 {echo "<td nowrap><a href=\"#top\" onclick=\"check_data_change('".$code."');\">  ".$desc." </a></td>";}
}


echo<<<END
     <td nowrap style="text-align:right">
      <a href='#top' onclick="check_data_change('default.php');">Login/Logout </a>
     </td>
    </tr>
   </table>
  </td>
 </tr>

 <tr>
  <td> 
   <div id="content">

END;

if ($script_name!='default.php' and $script_name!='granted.php')
{
 if ( (!$menu_tab_access[$script_name] and !$menu_tab_access['everything']) or $sec_password!=$myrow_us['us_pass'])
 {
  echo '<span class="errmsg"> Access denied or timed out. Please Login. Thank you. </span>';
  require "./foot.php";
  exit;
 }
}

?>
