<?php

/******************************************************************************/
/* Ottoman Turkish project - create XML to JPEG index                         */
/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require 'config.php';

if($_POST['ssubmit']) 
{

 foreach($_POST['notation'] as $k=>$notation)
 {
  if(!$_POST['notation'][$k] and !$_POST['paragraph'][$k]) 
  {
   unset($_POST['paragraph'][$k]);
   unset($_POST['notation'][$k]);
  }
  elseif(!$_POST['paragraph'][$k]) {$_POST['paragraph'][$k] = 'N';} 
 }
 ksort($_POST['paragraph']);

 foreach($_POST['quote'] as $k=>$quote)
 {
  if(!$_POST['quote'][$k] and !$_POST['category'][$k] and !$_POST['discussion'][$k] and !$_POST['recommendation'][$k]) 
  {
   unset($_POST['quote'][$k]);
   unset($_POST['category'][$k]);
   unset($_POST['discussion'][$k]);
   unset($_POST['recommendation'][$k]);
  } 
 }


 $_POST['paragraph']      = array_values($_POST['paragraph']);
 $_POST['notation']       = array_values($_POST['notation']);
 $_POST['quote']          = array_values($_POST['quote']);
 $_POST['category']       = array_values($_POST['category']);
 $_POST['discussion']     = array_values($_POST['discussion']);
 $_POST['recommendation'] = array_values($_POST['recommendation']);

/*
echo "<pre style='text-align:left'>";

print_r($_POST['paragraph']);
print_r($_POST['notation']);

print_r($_POST['quote']);
print_r($_POST['category']);
print_r($_POST['discussion']);
print_r($_POST['recommendation']);

echo "</pre>";
*/


 $query =
 "UPDATE `notations` SET
  `category`        = '".mysql_real_escape_string(serialize($_POST['category']))."',
  `quote`           = '".mysql_real_escape_string(serialize($_POST['quote']))."',
  `discussion`      = '".mysql_real_escape_string(serialize($_POST['discussion']))."',
  `notation`        = '".mysql_real_escape_string(serialize($_POST['notation']))."',
  `recommendation`  = '".mysql_real_escape_string(serialize($_POST['recommendation']))."',
  `paragraph`       = '".mysql_real_escape_string(serialize($_POST['paragraph']))."'
  WHERE `id` = \"".$_GET['id']."\"
  LIMIT 1
 ";
 mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error()."</pre>");
 $msg = translate('Record saved', $st, 'sys');
}



$js_coordinates = '';
$query =
"SELECT * FROM `notations`
 WHERE `id` = \"".$_GET['id']."\"
 LIMIT 1
";
$result = mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
$myrow  = mysql_fetch_array($result, MYSQL_ASSOC);

$categories      = unserialize($myrow['category']);
$quotes          = unserialize($myrow['quote']);
$discussions     = unserialize($myrow['discussion']);
$notations       = unserialize($myrow['notation']);
$recommendations = unserialize($myrow['recommendation']);
$paragraphs      = unserialize($myrow['paragraph']);

$notationDetail = '';
$notationKey='0';
foreach($notations as $k=>$notation)
{
 if($paragraphs[$k]=='Y') {$checked='checked';} else {$checked='';}    
 $notationDetail .= "
 <tr>
  <td><input type=\"checkbox\" name=\"paragraph[".$k."]\" value=\"Y\" ".$checked."></td>
  <td colspan=2><textarea name=\"notation[".$k."]\" cols=118>".$notation."</textarea></td>
 </tr>
";
 $notationKey = $k;
}

$quoteDetail = '';
$quoteKey='0';
foreach($quotes as $k=>$quote)
{
 $quoteDetail .= "
 <tr>
  <td><textarea name=\"quote[".$k."]\" rows=4>".$quote."</textarea></td>
  <td><textarea name=\"category[".$k."]\" rows=4>".$categories[$k]."</textarea></td>
  <td><textarea name=\"discussion[".$k."]\" cols=40 rows=4>".$discussions[$k]."</textarea></td>
  <td colspan=2><textarea name=\"recommendation[".$k."]\" cols=40 rows=4>".$recommendations[$k]."</textarea></td>
 </tr>
";
 $quoteKey = $k;
}



echo "
<!DOCTYPE html>
<html>
<head>
 <meta content=\"text/html; charset=UTF-8\" http-equiv=\"content-type\">
 <title>".translate('Edit verse', $st, 'sys')."</title>
 <link REL=\"SHORTCUT ICON\" HREF=\"images/favicon.ico\">
 <link type=\"text/css\" rel=\"stylesheet\" href=\"style.css\">

 <script language=JavaScript>

  var notationKey = ".$notationKey.";
  var quoteKey = ".$quoteKey.";

  function addNotationDetail()
  {
   notationKey++;
   var newContent = 
   '<tr>'+
   ' <td><input type=\"checkbox\" name=\"paragraph['+notationKey+']\" value=\"Y\" ></td>'+
   ' <td colspan=2><textarea name=\"notation['+notationKey+']\" cols=118></textarea></td>'+
   '</tr>';
   document.getElementById('notationDetail').innerHTML += newContent;
  }


  function addQuoteDetail()
  {
   quoteKey++;
   var newContent = 
   '<tr>'+
   ' <td><textarea name=\"quote['+quoteKey+']\" rows=4></textarea></td>'+
   ' <td><textarea name=\"category['+quoteKey+']\" rows=4></textarea></td>'+
   ' <td><textarea name=\"discussion['+quoteKey+']\" cols=40 rows=4></textarea></td>'+
   ' <td colspan=2><textarea name=\"recommendation['+quoteKey+']\" cols=40 rows=4></textarea></td>'+
   '</tr>';
   document.getElementById('quoteDetail').innerHTML += newContent;
  }


//  function onload_func()
//  {
//   var head = document.getElementById('editHeading'); 
//   head.innerHTML  = self.opener.document.form1.bibleTitle.value;
//  }
//  window.onload = onload_func;


 </script>

<style>

body {
    padding:20px;
}

</style>

</head>

<form id=\"form1\" name=\"form1\" action=\"\" method=post>

<body oncontextmenu=\"return false;\">

<div id=\"editHeading\">".$_GET['key']."</div>

<table id=\"notationDetail\" border>
 <tr>
  <th>".translate('Paragraph', $st, 'sys')."</th>
  <th>".translate('Notation', $st, 'sys')."</th>
  <th style=\"width:40px;\"><img src=\"images/b_plus.png\" onclick=\"addNotationDetail();\" title=\"Create new section\" /></th>
 </tr>
 ".$notationDetail."
</table>

<p />
<table id=\"quoteDetail\" border>
 <tr>
  <th>".translate('Quote', $st, 'sys')."</th>
  <th>".translate('Category', $st, 'sys')."</th>
  <th>".translate('Discussion', $st, 'sys')."</th>
  <th>".translate('Recommendation', $st, 'sys')."</th>
  <th style=\"width:40px;\"><img src=\"images/b_plus.png\" onclick=\"addQuoteDetail();\" title=\"Create new section\" /></th>
 </tr>
 ".$quoteDetail."
</table>

<p style=\"text-align:left; color:brown;\"/>
<input type=\"submit\" name=\"ssubmit\" value=\"".translate('Save', $st, 'sys')."\">
".$msg."

</body>
</form>
</html>
";

?>