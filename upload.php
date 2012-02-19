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
require "upload_functions.php";

if($_POST['bibleTitle'])
{
 $query =
 "SELECT * FROM `bibleTitles`
  WHERE `title` = \"".mysql_real_escape_string($_POST['bibleTitle'])."\"
  LIMIT 1 
 ";
 $result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 $myrow=mysql_fetch_array($result);
 if($myrow) {$bibleTitleId = $myrow['id'];} 
 else
 {
  $query =
  "INSERT INTO `bibleTitles` SET
   `title` = \"".mysql_real_escape_string($_POST['bibleTitle'])."\",
   `code`  = \"".$sec_code."\",
   `displayDefault` = 0
  ";
  mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
  $bibleTitleId = mysql_insert_id();
 }
}

if($_POST['bookName'])
{
 $query =
 "SELECT * FROM `books`
  WHERE `name`          = \"".mysql_real_escape_string($_POST['bookName'])."\"
  AND   `bibleTitleId`  = \"".$bibleTitleId."\"
  LIMIT 1 
 ";
 $result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 $myrow=mysql_fetch_array($result);
 if($myrow) 
 {
  $bookId = $myrow['id'];
  $_POST['bookDisplayOrder'] = $myrow['displayOrder'];
 } 
 else
 {
  $query =
  "INSERT INTO `books` SET
   `bibleTitleId` = \"".$bibleTitleId."\",
   `name`         = \"".mysql_real_escape_string($_POST['bookName'])."\",
   `displayOrder` = ".$_POST['bookDisplayOrder'].",
   `displayDefault` = 0
  ";
  mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
  $bookId = mysql_insert_id();
 }
}

if($_POST['devent']=='delete_title')
{
 $result=@mysql_query ("DELETE FROM `bibleTitles` WHERE id = \"".$bibleTitleId."\"");
 
 $query =
 "SELECT * FROM `books`
  WHERE `bibleTitleId` = \"".$bibleTitleId."\"
 ";
 $result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 while($myrow=mysql_fetch_array($result))
 {remove_book($base."docs/".$myrow['id']);}
 $result=@mysql_query ("DELETE FROM `books` WHERE bibletitleId = \"".$bibleTitleId."\"");
}

if($_POST['devent']=='delete_book')
{
 remove_book($base."docs/".$bookId);
 $result=@mysql_query ("DELETE FROM `books` WHERE id = \"".$bookId."\"");
 $myrow=@mysql_fetch_array($result);
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


if($_POST['devent']=='upload_files' and $_FILES)
{
 foreach($_FILES as $file=>$array)
 {
  if(strtolower(substr($array['name'],-4))=='.jpg'
  or strtolower(substr($array['name'],-5))=='.oxes'
  or strtolower(substr($array['name'],-4))=='.zip') {$uploadDir = upload_file($bookId, $file);}
 }
 processUpload($bookId, $bibleTitleId);
}


// get bible title options
$bibleTitle_options = '';
$query =
"SELECT * FROM `bibleTitles`
 WHERE `code` = \"".mysql_real_escape_string($sec_code)."\"
 ORDER BY `title`
";
$result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
while($myrow=mysql_fetch_array($result))
{$bibleTitle_options .= "<option value=\"".$myrow['title']."\" ".$selected.">".$myrow['title'];}

// get book name options
$bookName_options = '';
$query =
"SELECT * FROM `books`
 WHERE `bibleTitleId` = \"".$bibleTitleId."\"
 ORDER BY `displayOrder`, `name`
";
$result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
while($myrow=mysql_fetch_array($result))
{$bookName_options .= "<option value=\"".$myrow['name']."\" ".$selected.">".$myrow['name'];}



echo "


 <script language=JavaScript>

  function delete_title()
  {
   if(document.getElementById('bibleTitle').value) 
   {
    var answer = confirm ('".translate('You are about to permanently delete this Title and all the books assigned to it', $st, 'sys').".\\n".translate('Are you sure?', $st, 'sys')."');
    if (!answer) {return false;}
    document.getElementById('devent').value='delete_title';
    document.form1.submit();
   }
  }

  function delete_book()
  {
   if(document.getElementById('bookName').value) 
   {
    var answer = confirm ('".translate('You are about to permanently delete this book', $st, 'sys').".\\n".translate('Are you sure?', $st, 'sys')."');
    if (!answer) {return false;}
    document.getElementById('devent').value='delete_book';
    document.form1.submit();
   }
  }

  function upload_files()
  {
   errmsg = ''; 
   if(!document.getElementById('bibleTitle').value) {errmsg += '".translate('title is required', $st, 'sys')."\\n';}
   if(!document.getElementById('bookName').value)   {errmsg += '".translate('book name is required', $st, 'sys')."\\n';}
   if(!document.getElementById('bookDisplayOrder').value)   {errmsg += '".translate('book display order is required', $st, 'sys')."\\n';}
   if(!document.getElementById('docImages').value && !document.getElementById('oxesFile').value)
   {errmsg += '".translate('upload filename(s) is required', $st, 'sys')."\\n';}
   if(errmsg)
   {
    alert(errmsg);
    return false;
   }
   document.getElementById('devent').value='upload_files';
   document.form1.submit();
  } 

 </script>

 <p />
  ".translate('Bible or Testament name', $st, 'sys')." <br>
  <input name=\"bibleTitle\" id=\"bibleTitle\" value=\"".$_POST['bibleTitle']."\" size=40>
  <select onchange=\"document.getElementById('bibleTitle').value=this.value; submit();\">
   <option value=\"\"> -- ".translate('Select a current title', $st, 'sys')." -- 
   ".$bibleTitle_options."
  </select> 
  <img src=\"images/delete.png\" onclick=\"delete_title();\" title=\"delete Bible or Testament\">

 <p />
  ".translate('Book name', $st, 'sys')." <br />
  <input name=\"bookName\" id=\"bookName\" value=\"".$_POST['bookName']."\" size=40>
  <select onchange=\"document.getElementById('bookName').value=this.value; submit();\">
   <option value=\"\"> -- ".translate('Select a current name', $st, 'sys')." -- 
   ".$bookName_options."
  </select> 
  <img src=\"images/delete.png\" onclick=\"delete_book();\" title=\"delete book\">

  <br />
  ".translate('Book display order', $st, 'sys')." <br />
  <input name=\"bookDisplayOrder\" id=\"bookDisplayOrder\" value=\"".$_POST['bookDisplayOrder']."\" size=2>

 <p />
  ".translate('Document image file(s) to upload', $st, 'sys')." <br />
  <input type=\"file\" name=\"docImages\" id=\"docImages\" size=60> &nbsp;
  <span style=\"color:silver\">".translate('Only files ending in .jpg or .zip are allowed', $st, 'sys')."</span>
 
 <p />
  ".translate('OXES file to upload', $st, 'sys')." <br />
  <input type=\"file\" name=\"oxesFile\" id=\"oxesFile\" size=60> &nbsp; 
  <span style=\"color:silver\">".translate('Only files ending in .oxes or .zip are allowed', $st, 'sys')."</span>

 <p />
  <input type=button value=\"".translate('Upload files', $st, 'sys')."\" onclick=\"upload_files()\">

  <input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"20000000\">

";

$docDir = 'docs/'.$bookId;
if ($bookId && file_exists($docDir))
{
	echo '<table class=uploadFileTable><tr><th class="uploadFileName">'.translate('Uploaded Files', $st, 'sys').'</th>';
	echo '<th class=uploadFileDate>'.translate('Last Modified', $st, 'sys').'</th>';
	echo '<th class=uploadFileSize>'.translate('File Size', $st, 'sys').'</th></tr>';
	echo '<tr><td class=uploadFileName>OXES File</td><td class=uploadFileDate>';
	if (file_exists($docDir."/upload.oxes"))
	{
		echo gmdate("D, d M Y H:i T", filectime($docDir."/upload.oxes"));
		echo '</td><td class=uploadFileSize>'.filesize($docDir."/upload.oxes");
	}
	else
	{
		echo "Not Uploaded";
	}
	echo '</td></tr>';
	$files = scandir($docDir);
	$images = array();
	foreach($files as $file)
	{
		if(strtolower(substr($file,-4))=='.jpg')
		{
			$images[] = $file;
		}
	}
	if (count($images) > 0)
	{
		sort($images);
		foreach($images as $image)
		{
			echo '<tr><td class=uploadFileName>'.$image.'</td><td class=uploadFileDate>'.gmdate("D, d M Y H:i T", filectime($docDir.'/'.$image)).'</td>';
			echo '<td class=uploadFileSize>'.filesize($docDir.'/'.$image).'</tr>';
		}
	}
	echo '</table>';
}

require "foot.php";

?>