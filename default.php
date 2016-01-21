<?php 

/******************************************************************************/
/* default login screen                                                       */
/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require "config.php";

$dname   =($_POST['dname']);
$dpass   =($_POST['dpass']);

if (($_POST['ssubmit'])=="Login") 
{
 $query = "SELECT * FROM user WHERE us_name = \"$dname\" LIMIT 1";
 $result=mysql_query($query) or die (mysql_error());
 $myrow=mysql_fetch_array($result);
 $dcoidno=$myrow['us_coidno'];
 $sec_flag='';

 if($myrow['us_name'])
 { 
  if ($myrow['us_pass']==md5($dpass) or !$myrow['us_pass'])
  {
   $cookie_data =$dname;
   $cookie_data.='-';
   $cookie_data.=md5($dpass);
   $time = time(); 
   setcookie("cookie_info",$cookie_data);
   $sec_flag='Y';
  }
 }

 $derrmsg='';
 if ($sec_flag!='Y')
 {
  $derrmsg='Invalid username/password - Please try again';
 }  
}

else
{ 
 $time = time(); 
 setcookie ("cookie_info", ""); 
 $sec_flag='';
} 

if (($_POST['devent'])=="logout") 
{ 
 $time = time(); 
 setcookie ("cookie_info", ""); 
 $sec_flag='';
} 

require "head.php";

if ($sec_flag=='Y')
{

echo <<<END

   <br>
   <br>
   <p><b> $dname </b></p>  
   <p> Please select an option from the tabs above to proceed. </p>

END;
}
else
{

echo <<<END

   
   <p> <h2> $system_title </h2>

   <table>
    <tr>
     <td> User name: </td>
     <td> <INPUT name=dname value="$dname"  maxLength=20 size=21> </td>
    </tr>
    <tr>
     <td> Password: </td>
     <td> <INPUT name=dpass value="$dpass"  maxLength=20 size=21 type=password></td>
     <td> <INPUT name="ssubmit" type=submit value="Login"> </td>
    </tr>
   </table>

   <div class="errmsg"> $derrmsg </div>

END;

}

require "foot.php";

?>
