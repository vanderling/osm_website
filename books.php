<?php

/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require "config.php";
require "head.php";

if($_POST['devent']=='save')
{
 foreach($_POST['bibleTitle'] as $id=>$bibleTitle)
 { 
  if($_POST['bibleTitleDefault']==$id) {$bibleTitleDefault=1;} else {$bibleTitleDefault=0;}
  $query =
  "UPDATE `bibleTitles` SET
   `title` = \"".mysql_real_escape_string($bibleTitle)."\",
   `displayDefault` = ".$bibleTitleDefault."
   WHERE `id`       = ".$id."
  ";
  mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 }

 foreach($_POST['bookName'] as $id=>$bookName)
 {
  if($_POST['bookNameDefault']==$id) {$bookNameDefault=1;} else {$bookNameDefault=0;}
  $query =
  "UPDATE `books` SET
   `name`           = \"".mysql_real_escape_string($bookName)."\",
   `displayOrder`   = ".$_POST['bookDisplayOrder'][$id].",
   `displayDefault` = ".$bookNameDefault."
   WHERE `id`       = ".$id."
  ";
  mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 }
}

function remove_book($docDir)
{
 $files = scandir($docDir."/thumbs");
 foreach($files as $file)
 {if($file!='.' and $file!='..') {unlink($docDir."/thumbs/".$file);}}
 rmdir($docDir."/thumbs");

 $files = scandir($docDir);
 foreach($files as $file)
 {if($file!='.' and $file!='..') {unlink($docDir."/".$file);}}
 rmdir($docDir);
}


if($_POST['devent']=='delete_title')
{
 mysql_query ("DELETE FROM `bibleTitles` WHERE id = \"".$_POST['d_bibleTitleId']."\"");
 mysql_query ("DELETE FROM `books` WHERE bibletitleId = \"".$_POST['d_bibleTitleId']."\"");
 remove_book($base."docs/".$_POST['d_bibleTitleId']);
}

if($_POST['devent']=='delete_book')
{
 remove_book($base."docs/".$bookId);
 mysql_query ("DELETE FROM `books` WHERE id = \"".$_POST['d_bookId']."\"");
 mysql_fetch_array($result);
}


// get bible title options
$detail = '';
$query =
"SELECT * FROM `bibleTitles`
 WHERE `code` = \"".mysql_real_escape_string($sec_code)."\"
 ORDER BY `title`
";
$result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
while($myrow=mysql_fetch_array($result))
{
 if($myrow['displayDefault']) {$displayDefault='checked';} else {$displayDefault='';}
 $detail .= "
 <fieldset>
 <table>
  <tr>
   <th colspan=2> Bible or Testament name </th>
   <th> Default display </th>
  </tr>
  <tr>
   <td colspan=2> <input name=\"bibleTitle[".$myrow['id']."]\" id=\"bibleTitle\" value=\"".$myrow['title']."\"> </td>
   <td> &nbsp; <input type=\"radio\" name=\"bibleTitleDefault\" value=\"".$myrow['id']."\" ".$displayDefault."> </td>
   <td> &nbsp; <img src=\"images/delete.png\" onclick=\"delete_title(".$myrow['id'].");\" title=\"delete Bible or Testament\"> </td>
  </tr>
  <tr>
   <th> Book name </th>
   <th> Display Order </th>
  </tr>
";


 $query =
 "SELECT * FROM `books`
  WHERE `bibleTitleId` = \"".$myrow['id']."\"
  ORDER BY `displayOrder`, `name`
 ";
 $result_b=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 while($myrow_b=mysql_fetch_array($result_b))
 {
  if($myrow_b['displayDefault']) {$displayDefault='checked';} else {$displayDefault='';}
  $detail .= "
  <tr>
   <td> <input name=\"bookName[".$myrow_b['id']."]\" id=\"bookName\" value=\"".$myrow_b['name']."\" size=40> </td>
   <td> <input name=\"bookDisplayOrder[".$myrow_b['id']."]\" id=\"bookDisplayOrder\" value=\"".$myrow_b['displayOrder']."\" size=2 style=\"text-align:right;\"> </td>
   <td> &nbsp; <input type=\"radio\" name=\"bookNameDefault\" value=\"".$myrow_b['id']."\" ".$displayDefault."> </td>
   <td> &nbsp; <img src=\"images/delete.png\" onclick=\"delete_book(".$myrow_b['id'].");\" title=\"delete book\"> </td>
  </tr>
";
 }

 $detail .= "
 </table>
 </fieldset>
";
}


echo "

 <script language=JavaScript>

  function delete_title(id)
  {
   if(document.getElementById('bibleTitle').value) 
   {
    var answer = confirm ('".translate('You are about to permanently delete this Title and all the books assigned to it', $st, 'sys').".\\n".translate('Are you sure?', $st, 'sys')."');
    if (!answer) {return false;}
    document.getElementById('devent').value='delete_title';
    document.getElementById('d_bibleTitleId').value=id;
    document.form1.submit();
   }
  }

  function delete_book(id)
  {
   if(document.getElementById('bookName').value) 
   {
    var answer = confirm ('".translate('You are about to permanently delete this book', $st, 'sys').".\\n".translate('Are you sure?', $st, 'sys')."');
    if (!answer) {return false;}
    document.getElementById('devent').value='delete_book';
    document.getElementById('d_bookId').value=id;
    document.form1.submit();
   }
  }
  
  function saveFunc()
  {
   document.getElementById('devent').value='save';
   document.form1.submit();
  }

 </script>

 <style>
  input {width:98%;}
  fieldset {margin-top:20px;}
  th {text-align:left;}
 </style>

 ".$detail."

 <p />
 <button onclick=\"saveFunc();\">Save</button>

 <input type=\"hidden\" id=\"d_bibleTitleId\" name=\"d_bibleTitleId\">
 <input type=\"hidden\" id=\"d_bookId\" name=\"d_bookId\">

";

require "foot.php";

?>