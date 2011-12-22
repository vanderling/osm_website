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


/** 
 * xml2array() will convert the given XML text to an array in the XML structure.
 * Link: http://www.bin-co.com/php/scripts/xml2array/ 
 * Arguments : $contents - The XML text 
 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
 *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
 * Examples: $array =  xml2array(file_get_contents('feed.xml')); 
 *              $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));
**/ 

function xml2array($contents, $get_attributes=1, $priority = 'attribute') { 
    if(!$contents) return array(); 

    if(!function_exists('xml_parser_create')) { 
        //print "'xml_parser_create()' function not found!"; 
        return array(); 
    } 

    //Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create(''); 
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
    xml_parse_into_struct($parser, trim($contents), $xml_values); 
    xml_parser_free($parser); 

    if(!$xml_values) return;//Hmm... 

    //Initializations 
    $xml_array = array(); 
    $parents = array(); 
    $opened_tags = array(); 
    $arr = array(); 

    $current = &$xml_array; //Refference 

    //Go through the tags. 
    $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
     foreach($xml_values as $data) { 
        unset($attributes,$value);//Remove existing values, or there will be trouble
 
        //This command will extract these variables into the foreach scope 
        // tag(string), type(string), level(int), attributes(array). 
        extract($data);//We could use the array by itself, but this cooler. 

        $result = array(); 
        $attributes_data = array(); 
         
        if(isset($value)) { 
            if($priority == 'tag') $result = $value; 
            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
         } 

        //Set the attributes too. 
        if(isset($attributes) and $get_attributes) { 
            foreach($attributes as $attr => $val) { 
                if($priority == 'tag') $attributes_data[$attr] = $val; 
                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
             } 
        } 

        //See tag status and do the needed. 
        if($type == "open") {//The starting of the tag '<tag>' 
            $parent[$level-1] = &$current; 
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                 $current[$tag] = $result; 
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                 $repeated_tag_index[$tag.'_'.$level] = 1; 

                $current = &$current[$tag]; 

            } else { //There was another element with the same tag name 

                if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                     $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                     $repeated_tag_index[$tag.'_'.$level]++; 
                } else {//This section will make the value an array if multiple tags with the same name appear together
                     $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                     $repeated_tag_index[$tag.'_'.$level] = 2; 
                     
                    if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                         $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
                        unset($current[$tag.'_attr']); 
                    } 

                } 
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1; 
                $current = &$current[$tag][$last_item_index]; 
            } 

        } elseif($type == "complete") { //Tags that ends in 1 line '<tag />' 
            //See if the key is already taken. 
            if(!isset($current[$tag])) { //New Key 
                $current[$tag] = $result; 
                $repeated_tag_index[$tag.'_'.$level] = 1; 
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
 
            } else { //If taken, put all things inside a list(array) 
                if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
 
                    // ...push the new element into that array. 
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                      
                    if($priority == 'tag' and $get_attributes and $attributes_data) {
                         $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                     } 
                    $repeated_tag_index[$tag.'_'.$level]++; 

                } else { //If it is not an array... 
                    $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                     $repeated_tag_index[$tag.'_'.$level] = 1; 
                    if($priority == 'tag' and $get_attributes) { 
                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                              
                            $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
                            unset($current[$tag.'_attr']); 
                        } 
                         
                        if($attributes_data) { 
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                         } 
                    } 
                    $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                 } 
            } 

        } elseif($type == 'close') { //End of tag '</tag>' 
            $current = &$parent[$level-1]; 
        } 
    } 
     
    return($xml_array); 
}  
/**
******************************************************************************/


function processUpload($bookId, $bibleTitleId)
{
 $docDir = 'docs/'.$bookId;
 $docSelections = '';
 $annotations = '';

 $files = scandir($docDir);
 foreach($files as $file)
 {
  list($filename, $ext) = explode('.', $file);

  // create thumbnail
  if($ext=='jpg')
  {
   if(!file_exists($docDir."/thumbs")) {mkdir($docDir."/thumbs");}
   if(!file_exists($docDir."/thumbs/".$file))
   {
    $src_img=imagecreatefromjpeg($docDir."/".$file);
    $old_x=imageSX($src_img);
    $old_y=imageSY($src_img);
    $thumb_w = 80;
    $thumb_h = ($thumb_w/$old_x)*$old_y;
	$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
	imagecopyresampled($dst_img,$src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y); 
    imagejpeg($dst_img, $docDir."/thumbs/".$file); 
    imagedestroy($src_img);
   }
  }

  // extract data base information
  if($ext=='oxes')
  {
   // flag current entries as inactive
   $query =
   "UPDATE `notations` SET
    `inactive`       = \"Y\"
    WHERE `bibleTitleId` = \"".$bibleTitleId."\"
    AND   `bookId`       = \"".$bookId."\"
   ";
   mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error()."</pre>");

   $contents = xml2array(file_get_contents($docDir."/".$file));
   $book = array();



/*
if(getenv('REMOTE_ADDR')=='24.35.151.122')
{
 echo "<pre>";
 print_r($contents);
 echo "</pre>";
 exit;
}
*/

   // extract title information
   $annotation = $contents['oxes']['oxesText']['canon']['book']['titleGroup']['title']['annotation'];

   $key = $annotation['attr']['oxesRef'];
   list($b, $c, $v) = explode(".", $key);
   $key = $b.".".str_pad($c,3,"0",STR_PAD_LEFT).".".str_pad($v,3,"0",STR_PAD_LEFT); 

   $book[$key]['notationCategory'][]       = str_replace('"', '\"', $annotation['notationCategories']['category']['value']);
   $book[$key]['notationQuote'][]          = str_replace('"', '\"', $annotation['notationQuote']['para']['span']['value']);
   $book[$key]['notationDiscussion'][]     = str_replace('"', '\"', $annotation['notationDiscussion']['para']['span']['value']);
   $book[$key]['notationRecommendation'][] = str_replace('"', '\"', $annotation['notationRecommendation']['para']['a']['value']);

   $book[$key]['notation'][] = "<center><span class=\"title\">".$contents['oxes']['oxesText']['canon']['book']['titleGroup']['title']['trGroup']['tr']['value']."</span></center>";



   // extract book contents
   $psection = $contents['oxes']['oxesText']['canon']['book']['section'];

   foreach($psection as $sections)
   {

    foreach($sections as $psType=>$ps) 
    {

     if(!$ps[0]) {$ps = array($ps);}


/*
if(getenv('REMOTE_ADDR')=='24.35.151.122')
{
echo "<hr>".$psType."<hr>";
echo "<pre>";
print_r($ps);
echo "</pre>";
//exit;
}
*/


     foreach($ps as $p)
     {
      $paragraph = "Y";


      // extract verseStart, verseEnd, notation
       for($pp=0; $pp<count($p['trGroup']); $pp++)
       {
        if($p['verseEnd'][$pp]['attr']['ID']) {$key = $p['verseEnd'][$pp]['attr']['ID'];}
        elseif($p['verseStart'][$pp]['attr']['ID']) {$key = $p['verseStart'][$pp]['attr']['ID'];}
        elseif($p['verseStart']['attr']['ID'])      {$key = $p['verseStart']['attr']['ID'];}
//        else{$key = $last_verseEnd;}

//        if($p['verseEnd'][$pp]['attr']['ID']) {$last_verseEnd = $p['verseEnd'][$pp]['attr']['ID'];}
//        elseif($p['verseEnd']['attr']['ID'])  {$last_verseEnd = $p['verseEnd']['attr']['ID'];}
//        else {$last_verseEnd = '';}

        list($b, $c, $v) = explode(".", $key);
        $key = $b.".".str_pad($c,3,"0",STR_PAD_LEFT).".".str_pad($v,3,"0",STR_PAD_LEFT); 

/*
if($key=='RUT.002.010' or $key=='RUT.002.011' or $key=='RUT.002.012')
{
    echo "<pre>(1)".$key;
    print_r($p);
    echo "</pre>";
}
*/

        if($p['trGroup'][$pp]['tr']['value'])
        {
         // add subheader class 
         if($psType==='sectionHead')
         {
          $book[$key]['notation'][]  = "<center><span class=\"subHeader\">".str_replace('"', '\"', $p['trGroup'][$pp]['tr']['value'])."</span></center>";
         }
         else
         {
          $book[$key]['notation'][] = str_replace('"', '\"', $p['trGroup'][$pp]['tr']['value']); 
          $book[$key]['paragraph'][] = $paragraph;
          $paragraph = '';
         }
        }
       }


/** this code may not logically get used **/
       if($p['trGroup']['tr']['value'])
       {
        // add subheader class 
        if($psType=='sectionHead')
        {
         $book[$key]['notation'][]  = "<center><span class=\"subHeader\">".str_replace('"', '\"', $p['trGroup']['tr']['value'])."</span></center>";
        }
        else
        {
         $book[$key]['notation'][] = str_replace('"', '\"', $p['trGroup']['tr']['value']); 
         $book[$key]['paragraph'][] = $paragraph;
         $paragraph = '';
        }
       }
/** this code may not logically get used **/

      // extract Category, Quote, Discussion, Discussion
      if(isset($p['annotation'][0]))
      {
       for($pp=0; $pp<count($p['annotation']); $pp++)
       {
        $akey = $p['annotation'][$pp]['attr']['oxesRef'];

//        if($p['verseEnd'][$pp]['attr']['ID']) {$akey = $p['verseEnd'][$pp]['attr']['ID'];}
//        elseif($p['verseStart'][$pp]['attr']['ID']) {$akey = $p['verseStart'][$pp]['attr']['ID'];}
//        elseif($p['verseStart']['attr']['ID'])      {$akey = $p['verseStart']['attr']['ID'];}


//        else{$akey = $last_verseEnd;}

//        if($p['verseEnd'][$pp]['attr']['ID']) {$last_verseEnd = $p['verseEnd'][$pp]['attr']['ID'];}
//        elseif($p['verseEnd']['attr']['ID'])  {$last_verseEnd = $p['verseEnd']['attr']['ID'];}
//        else {$last_verseEnd = '';}

        list($b, $c, $v) = explode(".", $akey);
        $akey = $b.".".str_pad($c,3,"0",STR_PAD_LEFT).".".str_pad($v,3,"0",STR_PAD_LEFT);  

/*
if($akey=='RUT.002.010' or $akey=='RUT.002.011' or $akey=='RUT.002.012')
{
    echo "<pre>(1)".$akey;
    print_r($p);
    echo "</pre>";
}
*/
         $book[$akey]['notationCategory'][]       = str_replace('"', "''", $p['annotation'][$pp]['notationCategories']['category']['value']);
         $book[$akey]['notationQuote'][]          = str_replace('"', "''", $p['annotation'][$pp]['notationQuote']['para']['span']['value']);
         $book[$akey]['notationDiscussion'][]     = str_replace('"', "''", $p['annotation'][$pp]['notationDiscussion']['para']['span']['value']);
         $book[$akey]['notationRecommendation'][] = str_replace('"', "''", $p['annotation'][$pp]['notationRecommendation']['para']['a']['value']);
       }
      }
      elseif (isset($p['annotation']))
      {
       $akey = $p['annotation']['attr']['oxesRef'];
       list($b, $c, $v) = explode(".", $akey);
       $akey = $b.".".str_pad($c,3,"0",STR_PAD_LEFT).".".str_pad($v,3,"0",STR_PAD_LEFT); 

       $book[$akey]['notationCategory'][]       = str_replace('"', "''", $p['annotation']['notationCategories']['category']['value']);
       $book[$akey]['notationQuote'][]          = str_replace('"', "''", $p['annotation']['notationQuote']['para']['span']['value']);
       $book[$akey]['notationDiscussion'][]     = str_replace('"', "''", $p['annotation']['notationDiscussion']['para']['span']['value']);
       $book[$akey]['notationRecommendation'][] = str_replace('"', "''", $p['annotation']['notationRecommendation']['para']['a']['value']);
      }

     }
    }
   }


/*
if(getenv('REMOTE_ADDR')=='24.35.151.122')
{
 echo "<pre>";
 print_r($book);
 echo "</pre>";
 exit;
}
*/

   $sav_c = '000';
   foreach($book as $key=>$data) 
   {

       if($data['notation'])
       { 
        $query =
        "SELECT * FROM `notations`
         WHERE `key`          = \"".$key."\"
         AND   `bibleTitleId` = \"".$bibleTitleId."\"
         AND   `bookId`       = \"".$bookId."\"
         LIMIT 1 
        ";
        $result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
        $myrow=mysql_fetch_array($result);
        if($myrow) 
        {
         $query =
         "UPDATE `notations` SET
          `category`       = '".str_replace("'","\'",serialize($data['notationCategory']))."',
          `quote`          = '".str_replace("'","\'",serialize($data['notationQuote']))."',
          `discussion`     = '".str_replace("'","\'",serialize($data['notationDiscussion']))."',
          `notation`       = '".str_replace("'","\'",serialize($data['notation']))."',
          `recommendation` = '".str_replace("'","\'",serialize($data['notationRecommendation']))."',
          `inactive`       = \"\",
          `paragraph`      = '".serialize($data['paragraph'])."'
          WHERE `key`          = \"".$key."\"
          AND   `bibleTitleId` = \"".$bibleTitleId."\"
          AND   `id`           = \"".$myrow['id']."\"
          LIMIT 1 
         ";
        mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error()."</pre>");
        } 
        else
        {
         $query =
         "INSERT INTO `notations` SET
          `key`            = \"".$key."\",
          `bibleTitleId`   = \"".$bibleTitleId."\",
          `bookId`         = \"".$bookId."\",
          `category`       = '".str_replace("'","\'",serialize($data['notationCategory']))."',
          `quote`          = '".str_replace("'","\'",serialize($data['notationQuote']))."',
          `discussion`     = '".str_replace("'","\'",serialize($data['notationDiscussion']))."',
          `notation`       = '".str_replace("'","\'",serialize($data['notation']))."',
          `recommendation` = '".str_replace("'","\'",serialize($data['notationRecommendation']))."',
          `inactive`       = \"\",
          `paragraph`      = '".serialize($data['paragraph'])."'
         ";
         mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
       }
      }

      // insert start of chapter markers 
      list($b, $c, $v) = explode('.',$key);
      if($c!=$sav_c) 
      {
       $ckey = $b.".".$c.".000";
       $query =
       "INSERT INTO `notations` SET
        `key`            = \"".$ckey."\",
        `bibleTitleId`   = \"".$bibleTitleId."\",
        `bookId`         = \"".$bookId."\",
        `inactive`       = \"\"
       ";
       mysql_query($query); // or die ("<pre>".$query.mysql_error()."</pre>");
         
       // update inactive flag
       $query =
       "UPDATE `notations` SET
        `inactive`       = \"\"
        WHERE `key`          = \"".$ckey."\"
        AND   `bibleTitleId` = \"".$bibleTitleId."\"
        AND   `bookId`       = \"".$bookId."\"
        LIMIT 1 
       ";
       mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error()."</pre>");
      }
     $sav_c = $c;
    }

  }
 }
}


function upload_file($bookId, $name)
{
 global $base;
 global $st; 

 $uploadfile = $base."docs/".$bookId;
 if(!mkdir($uploadfile) and !file_exists($uploadfile))
 {echo "<pre>".translate('Problem creating directory', $st, 'sys')." ".$uploadfile."</pre>";}
 else
 { 
  if(!chmod($uploadfile, 0777))
  {echo "<pre>".translate('Problem setting permissions', $st, 'sys')." ".$uploadfile."</pre>";} 
  else
  {
   $ext = substr($_FILES[$name]['name'], strrpos($_FILES[$name]['name'],'.'));
   if($ext=='.oxes' or $ext=='.OXES') {$_FILES[$name]['name']='upload.oxes';}

   if(!move_uploaded_file($_FILES[$name]['tmp_name'], $uploadfile."/".$_FILES[$name]['name']))
   {echo "<pre>".translate('Problem uploading file', $st, 'sys')." ".$uploadfile."/".$_FILES[$name]['name']."</pre>";} 
   else
   {
    if(strtolower(substr($_FILES[$name]['name'],-4))=='.zip')
    {
     $znames = array();
     $zip = new ZipArchive;
     $res = $zip->open($uploadfile."/".$_FILES[$name]['name']);
     if ($res === TRUE)
     {
      for($i=0; $i<$zip->numFiles; $i++)
      {
       $zname = $zip->getNameIndex($i);
       if(strtolower(substr($zname,-4))=='.jpg'
       or strtolower(substr($zname,-5))=='.oxes')
       {
        $zzname = substr($zname, strrpos($zname, "/")+1); 
        $zip->renameName($zname, $zzname);
        $znames[] = $zzname;
       }
      }
      $zip->extractTo($uploadfile."/", $znames);
      $zip->close();
      unlink($uploadfile."/".$_FILES[$name]['name']);
     } else {echo "<pre>".translate('file unzip failed', $st, 'sys');}    
    }
   }
  }
 } 
}



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
   `code`  = \"".$sec_code."\"
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
   `displayOrder` = ".$_POST['bookDisplayOrder']."
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
  ".translate('Book display order')." <br />
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

require "foot.php";

?>