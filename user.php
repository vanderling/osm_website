<?php 

/******************************************************************************/
/* setup new user                                                             */
/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require "config.php";
require "head.php";

$dname      =($_POST['dname']);
$dpass      =($_POST['dpass']);
$dreadonly  =($_POST['dreadonly']);
$spass      =($_POST['spass']);
$sname      =($_POST['sname']);

if($_POST['devent']=='sname')
{
 $query  = "SELECT * FROM user WHERE us_name = \"$sname\" LIMIT 1";
 $result = mysql_query($query) or die (mysql_error());
 $myrow  = mysql_fetch_array($result);
 $dname               = $myrow['us_name'];
 $dpass               = $myrow['us_pass'];
 $dreadonly           = $myrow['us_readonly'];
 $us_area_access      = unserialize($myrow['us_area_access']);
 $us_country_access   = unserialize($myrow['us_country_access']);
 $us_eth_code_access  = unserialize($myrow['us_eth_code_access']);
 $us_menu_tab_access  = unserialize($myrow['us_menu_tab_access']);
}

if ($_POST['devent']=="delete") 
{
 $result=@mysql_query ("DELETE FROM user WHERE us_name = \"$dname\" LIMIT 1");
 $myrow=@mysql_fetch_array($result);
 $_POST['ssubmit']="Clear Form";  

 $submit_message = 'Record deleted successfully';
}

if (($_POST['ssubmit'])=="Clear Form") 
 {
  $dname     ='';
  $dpass     ='';
  $dreadonly ='N';
 }

if ($dname!=null)
  {
   if (($_POST['devent'])=="accept") 
   {
    $derrmsg='';
    $query  = "SELECT * FROM user WHERE us_name = \"$dname\" LIMIT 1";
     $result = mysql_query($query) or die (mysql_error());
     $myrow  = mysql_fetch_array($result);
     if ($myrow)
     {
      if ($dpass!=$spass)  {$mpass=md5($dpass);} else {$mpass=$dpass;}
      if ($dname=='admin') {$_POST['menu_tab_access']['everything'] = 'on';}
      $query = 
      "UPDATE user SET 
       us_name             ='".$dname."',
       us_pass             ='".$mpass."',
       us_readonly         ='".$dreadonly."',
       us_area_access      ='".serialize($_POST['area_access'])."',
       us_country_access   ='".serialize($_POST['country_access'])."',
       us_eth_code_access  ='".serialize($_POST['eth_code_access'])."',
       us_menu_tab_access  ='".serialize($_POST['menu_tab_access'])."'
      WHERE us_name   = \"$dname\"
      LIMIT 1"; 
      mysql_query($query) or die (mysql_error());
     }
 
    if(!$myrow)
    {
     $mpass = md5($dpass); 
     $query = "INSERT INTO user SET
       us_name             ='".$dname."',
       us_pass             ='".$mpass."',
       us_readonly         ='".$dreadonly."',
       us_area_access      ='".serialize($_POST['area_access'])."',
       us_country_access   ='".serialize($_POST['country_access'])."',
       us_eth_code_access  ='".serialize($_POST['eth_code_access'])."',
       us_menu_tab_access  ='".serialize($_POST['menu_tab_access'])."' ";
     mysql_query($query) or die (mysql_error()); 
    }

    $submit_message = 'Record updated successfully';
 
    $dname     ='';
    $dpass     ='';
    $dreadonly ='N';
   }
  }

 $sname_options="";
 $query ="SELECT * FROM user";
 $result=mysql_query($query) or die (mysql_error());
 while ($myrow=mysql_fetch_array($result))
 {
  $sname_options.= "<OPTION VALUE=\"" . $myrow['us_name'] . "\">" . $myrow['us_name'];
 }
   
 $codes     = array();
 $countries = array();
 $areaa     = array();

 $query  = "SELECT eth_code FROM media ORDER BY eth_code";
 $result = mysql_query($query);
 while($myrow=mysql_fetch_array($result))
 {
  $query    = "SELECT * FROM eth_codes WHERE code = \"".$myrow['eth_code']."\" LIMIT 1";
  $result_c = mysql_query($query);
  $myrow_c  = mysql_fetch_array($result_c);
  $codes[$myrow['eth_code']] = $myrow_c['description'];

  $query    = "SELECT * FROM eth_countries WHERE eth_code = \"".$myrow['eth_code']."\" LIMIT 1";
  $result_e = mysql_query($query);
  $myrow_e  = mysql_fetch_array($result_e);
  $countries[$myrow_e['country_code']] = $myrow_e['country_name'];
  $areas[$myrow_e['area_name']] = $myrow_e['area_name'];
 }

 // load menu tab options
 if($us_menu_tab_access['everything']) {$checked='checked';} else {$checked = '';}
 $menu_tab_options = "<tr><td colspan=\"2\"> <input type=\"checkbox\" name=\"menu_tab_access[everything]\" ".$checked.">everything</td></tr>";
 $c = 0;
 foreach($menu_tabs as $code=>$desc)
 {
  if($us_menu_tab_access[$code]) {$checked='checked';} else {$checked = '';}
  if($c>5) {$menu_tab_options .= "</tr><tr>"; $c=0;}
  $menu_tab_options .= "<td> <input type=\"checkbox\" name=\"menu_tab_access[".$code."]\" ".$checked.">".$desc."</td>";  
  $c++;
 }

// load eth code options
$eth_code_options = 
"ISO code <input name=\"eth_code_access\" value=\"".$us_eth_code_access."\" size=10>
";


echo "

<SCRIPT LANGUAGE=JavaScript>

f = document.forms[0];

function sname_func()
{
 f.devent.value=\"sname\";
 f.submit();
}

function delete_func()
{
 var answer = confirm (\"You are about to permanently delete this record.\\r\\rAre you sure? \");
 if (!answer) {return false;}
 f.devent.value=\"delete\";
 f.submit();
}

function accept_func()
{
 errmsg = ''; 

 if(!f.dname.value)    {errmsg += 'name is required\\n';}
 if(!f.dpass.value)    {errmsg += 'password is required\\n';}

 if(f.mediafile && !f.mediafile.value && !f.media_id.value)
 {errmsg += 'must select file to upload\\n';}

 if(errmsg)
 {
  alert(errmsg);
  return false;
 }

 f.devent.value=\"accept\";
 f.submit();
}

</SCRIPT>



  <p>
  <table>
   <tr>
    <td> Name: </td>
    <td nowrap>
     <INPUT name=dname value=\"".$dname."\"  maxLength=20 size=21>
     <SELECT NAME=\"sname\" onchange=sname_func();>
      <OPTION VALUE=\"select\">--select current user --
      ".$sname_options."
     </SELECT>
    </td>
   </tr> 

   <tr>
    <td> Password: </td>
    <td>
     <INPUT name=dpass value=\"".$dpass."\"  maxLength=20 size=21 type=password>
     <span class=\"errmsg\"> ".$derrmsg." </span>
    </td>
   </tr>
  </table>

  <p> 
  <div class=tan style=\"width:700\"> &nbsp;Access authority </div>

  <p>
   <table style=\"width:90%\">
    <tr>  
     <td> <!--Read only user?:--> </td>
     <td>
     </td>
     <td style=\"width:70%; text-align:right\">
      <span style=\"color:brown\"> ".$submit_message." </span>  
      <INPUT name=\"ssubmit\" type=button value=\"Accept Record\" onclick=accept_func()>
      <INPUT name=\"ssubmit\" type=submit value=\"Clear Form\">
      <INPUT name=\"ssubmit\" type=button value=\"Delete Record\" onclick=delete_func()>
     </td>
    </tr>
   </table>

  <p>
  <fieldset style=\"width:95%\">
  <legend> Access code authority </legend>
   <table style=\"width:95%\" cellspacing=\"0\" cellpadding=\"0\">
    <tr>
     ".$eth_code_options."
    </tr> 
   </table>
  </fieldset>

  <p>
  <fieldset>
  <legend> Menu tab access authority </legend>
   <table cellspacing=\"0\" cellpadding=\"5\">
    <tr>
     ".$menu_tab_options."
    </tr> 
   </table>
  </fieldset>

  <INPUT name=\"spass\"  type=\"hidden\" maxLength=50 value=\"".$dpass."\">

 
";

require "foot.php";

?>