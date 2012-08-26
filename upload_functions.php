<?php

/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/


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

function processAnnotation(&$book, $annotation)
{
	$akey = $annotation['attr']['oxesRef'];
	list($b, $c, $v) = explode(".", $akey);
	$akey = $b.".".str_pad($c,3,"0",STR_PAD_LEFT).".".str_pad($v,3,"0",STR_PAD_LEFT);  
	$book[$akey]['notationCategory'][]       = str_replace('"', "''", $annotation['notationCategories']['category']['value']);
	$book[$akey]['notationQuote'][]          = str_replace('"', "''", $annotation['notationQuote']['para']['span']['value']);
	$book[$akey]['notationDiscussion'][]     = str_replace('"', "''", $annotation['notationDiscussion']['para']['span']['value']);
	if (isset($annotation['notationRecommendation']['para']['a'][0]))
	{
		$recommend=str_replace('"', "''", $annotation['notationRecommendation']['para']['a'][0]['value']);
		for($r=1; $r<count($annotation['notationRecommendation']['para']['a']); $r++)
		{
			// use tab character to separate links so they can be parsed for display
			$recommend .= "\t" . str_replace('"', "''", $annotation['notationRecommendation']['para']['a'][$r]['value']);
		}
		$book[$akey]['notationRecommendation'][] = $recommend;
	}
	else
	{
		$recommend = str_replace('"', "''", $annotation['notationRecommendation']['para']['a']['value']);
		// separate combined references by replacing ; with tab
		$recommend = preg_replace('/\s*;\s*http/', "\thttp", $recommend);
		$book[$akey]['notationRecommendation'][] = $recommend;
	}
}

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

   $b = $contents['oxes']['oxesText']['canon']['book']['attr']['ID'];
   $key = $b.".000.000"; 

   $book[$key]['notation'][] = "<center><span class=\"title\">".$contents['oxes']['oxesText']['canon']['book']['titleGroup']['title']['trGroup']['tr']['value']."</span></center>";

   // extract title information
   $annotation = $contents['oxes']['oxesText']['canon']['book']['titleGroup']['title']['annotation'];
   if (isset($annotation[0]))
   {
       for($a=0; $a<count($annotation); $a++)
       {
       	processAnnotation($book, $annotation[$a]);
       }
   }
   elseif (isset($annotation))
   {
	processAnnotation($book, $annotation);
   }


   // extract book contents
   $psection = $contents['oxes']['oxesText']['canon']['book']['section'];

   foreach($psection as $sections)
   {

    foreach($sections as $psType=>$ps) 
    {

     if(!$ps[0]) {$ps = array($ps);}

     foreach($ps as $p)
     {
	  // skip empty paragraphs
	  if(!isset($p['trGroup'])) {continue;}
      $paragraph = "Y";

      // extract verseStart, verseEnd, notation
	  if (!$p['trGroup'][0]) {$p['trGroup'] = array($p['trGroup']);}
       for($pp=0; $pp<count($p['trGroup']); $pp++)
       {
        if($p['verseEnd'][$pp]['attr']['ID']) {$key = $p['verseEnd'][$pp]['attr']['ID'];}
        elseif($pp == 0 && $p['verseEnd']['attr']['ID']) {$key = $p['verseEnd']['attr']['ID'];}
        elseif($p['verseStart'][$pp]['attr']['ID']) {$key = $p['verseStart'][$pp]['attr']['ID'];}
        elseif($p['verseStart'][$pp-1]['attr']['ID']) {$key = $p['verseStart'][$pp-1]['attr']['ID'];}
        elseif($p['verseStart']['attr']['ID'])      {$key = $p['verseStart']['attr']['ID'];}

        list($b, $c, $v) = explode(".", $key);
        $key = $b.".".str_pad($c,3,"0",STR_PAD_LEFT).".".str_pad($v,3,"0",STR_PAD_LEFT); 

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

      // extract Category, Quote, Discussion, Discussion
      if(isset($p['annotation'][0]))
      {
       for($pp=0; $pp<count($p['annotation']); $pp++)
       {
       	processAnnotation($book, $p['annotation'][$pp]);
       }
      }
      elseif (isset($p['annotation']))
      {
       	processAnnotation($book, $p['annotation']);
      }
     }
    }
   }

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
          `paragraph`      = '".serialize($data['paragraph'])."',
		  `coordinates`    = \"\"
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
   {echo "<pre>".translate('Problem uploading file', $st, 'sys')." source: ".$_FILES[$name]['tmp_name']." dest: ".$uploadfile."/".$_FILES[$name]['name']."</pre>";} 
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
        $zzname = $zname;
		$pos = strrpos($zzname, "/");
		if ($pos !== FALSE)
		{
			$zzname = substr($zzname, $pos + 1);
		}
		correctExistingFiles($bookId, $zzname);
        $zip->renameName($zname, $zzname);
        $znames[] = $zzname;
       }
      }
      $zip->extractTo($uploadfile."/", $znames);
      $zip->close();
      unlink($uploadfile."/".$_FILES[$name]['name']);
     } else {echo "<pre>".translate('file unzip failed', $st, 'sys');}    
    }
	else
	{
		correctExistingFiles($bookId, $_FILES[$name]['name']);
	}
   }
  }
 } 
}

// correct error in previous uploads where first letter of file name was
// being dropped when a zip file was used.
function correctExistingFiles($bookId, $filename)
{
	$bookDir = "docs/".$bookId."/";
	$badFileName = substr($filename, 1);
	if (file_exists($bookDir.$badFileName))
	{
		unlink($bookDir.$badFileName);
		unlink($bookDir."thumbs/".$badFileName);
		$update = "Update notations
			set `coordinates` = replace(`coordinates`, \"".$badFileName."\", \"".$filename."\")
			where `coordinates` like \"%/".$bookId."/".$badFileName."%\"";
		mysql_query($update) or die ("<pre>Updated Failed: ".$update.mysql_error()."</pre>");
	}
}

?>