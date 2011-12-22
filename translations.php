<?php 

/******************************************************************************/
/* system translations                                                        */
/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require "config.php";
require "head.php";

if($_POST['devent']=='sname')
{
 $query  = 
 "SELECT * FROM translations
  WHERE code = \"".$_POST['scode']."\"
  LIMIT 1";
 $result_st = mysql_query($query) or die (mysql_error());
 $myrow_st  = mysql_fetch_array($result_st);
 $_POST['dcode'] = $myrow_st['code'];
 $_POST['dname'] = $myrow_st['name'];
 $_POST['dgoogle_code'] = $myrow_st['google_code'];
 $_POST['dgoogle_keyboard'] = $myrow_st['google_keyboard'];
 $_POST['dlanguage_direction'] = $myrow_st['language_direction'];

 // language index
 $query  = 
 "SELECT * FROM translations_".$_POST['dcode']."
  WHERE id = 99999
 ";
 $result = mysql_query($query) or die (mysql_error());
 $myrow  = mysql_fetch_array($result);
 $_POST['dcharIndex'] = $myrow['phrase'];
}

if ($_POST['devent']=="delete" and $_POST['dcode']) 
{
 $result=@mysql_query ("DELETE FROM translations WHERE code = \"".$_POST['dcode']."\" LIMIT 1");
 $myrow=@mysql_fetch_array($result);
 $_POST['devent']="clear";  

 $query = "DROP TABLE `translations_".$_POST['dcode']."`";
 mysql_query($query) or die (mysql_error());

 $submit_message = translate('Record deleted successfully', $st, 'sys');
}

if (($_POST['devent'])=="clear") 
{
 $_POST['dcode'] = '';
 $_POST['dname'] = '';
 $_POST['dcharIndex'] = '';
 $_POST['dgoogle_code'] = '';
 $_POST['dgoogle_keyboard'] = '';
 $_POST['dlanguage_direction'] = '';
}

if ($_POST['dname'])
{
 if (($_POST['devent'])=="accept") 
 {
  $query  = "SELECT * FROM translations WHERE code = \"".$_POST['dcode']."\" LIMIT 1";
  $result = mysql_query($query) or die (mysql_error());
  $myrow  = mysql_fetch_array($result);
  if ($myrow)
  {
   $query = 
   "UPDATE translations SET
     name                = '".$_POST['dname']."',
     google_code         = '".$_POST['dgoogle_code']."',
     google_keyboard     = '".$_POST['dgoogle_keyboard']."',
     language_direction  = '".$_POST['dlanguage_direction']."'
    WHERE code = \"".$_POST['dcode']."\"
    LIMIT 1";
   mysql_query($query) or die (mysql_error());

   // language index
   $query = 
   "UPDATE translations_".$_POST['dcode']." SET
     phrase = '".$_POST['dcharIndex']."'
     WHERE id = 99999
   ";
   mysql_query($query) or die (mysql_error()); 
  }
 
  if(!$myrow)
  {
   $query = 
   "INSERT INTO translations SET
    code                = '".$_POST['dcode']."',
    name                = '".$_POST['dname']."',
    google_code         = '".$_POST['dgoogle_code']."',
    google_keyboard     = '".$_POST['dgoogle_keyboard']."',
    language_direction  = '".$_POST['dlanguage_direction']."'
   ";
   mysql_query($query) or die (mysql_error()); 

   $query =
   "CREATE TABLE `translations_".$_POST['dcode']."` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,   
    `phrase` TEXT NOT NULL ) TYPE = MYISAM ;
   ";
   mysql_query($query) or die (mysql_error()); 
  }


  // update translated phrases
  foreach($_POST['phrase'] as $id=>$phrase)
  {
    $query  = "SELECT * FROM translations_".$_POST['dcode']." WHERE id = \"".$id."\" ";
    $result = mysql_query($query) or die (mysql_error());
    $myrow  = mysql_fetch_array($result);
    if($myrow)
    {
     if($phrase)
     {
      $query  = "UPDATE translations_".$_POST['dcode']." SET phrase = \"".$phrase."\" WHERE id = \"".$id."\" ";
      $result = mysql_query($query) or die (mysql_error());
     }
     else
     {
      $query = "DELETE FROM translations_".$_POST['dcode']." WHERE id = \"".$id."\" LIMIT 1";
      $result = mysql_query ($query);
     }
    }
    else
    {
     if($phrase and $phrase!=$myrow['phrase'])
     {
      $query  = 
      "INSERT INTO translations_".$_POST['dcode']." SET 
        phrase = \"".$phrase."\", 
        id = \"".$id."\" ";
      $result = mysql_query($query);
     }
    }
  }
  $submit_message = translate('Record updated successfully', $st, 'sys');
  $_POST['dcode'] = '';
  $_POST['dname'] = '';
  $_POST['dcharIndex'] = '';
  $_POST['dgoogle_code'] = '';
  $_POST['dgoogle_keyboard'] = '';
  $_POST['dlanguage_direction'] = '';
 }
}

// create translation_eng table if it does not exist
$query =
"CREATE TABLE `translations_eng` (
 `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,   
 `phrase` TEXT NOT NULL,   
 `active` VARCHAR(1) NOT NULL ) TYPE = MYISAM ;
";
mysql_query($query);// or die (mysql_error()); 

// initialize english phrases to be translated
$query  = "UPDATE translations_eng SET active = 0";
$result = mysql_query($query) or die (mysql_error());

// find translate phrases in scripts
$scripts = scandir('./');
foreach($scripts as $script)
{
// if(strtolower(substr($script,-4))=='.php')
 if($script=='view.php')
 {
  $strings = explode("translate(", file_get_contents($script));

  foreach($strings as $string)
  {
   if(strpos($string, '$st, '."'sys'")!==false)
   {
    list($phrase, $nu) =  explode(', $st,', $string); 
    {
     $phrase = trim($phrase, "'");
     
     // skip language names
     $skip = '';
     if(stripos($phrase, '$myrow')!==false) {$skip = 'Y';}
     if(stripos($phrase, '$_POST')!==false) {$skip = 'Y';}
     if(stripos($phrase, '$name')!==false) {$skip = 'Y';}
     if(stripos($phrase, '$translation[')!==false) {$skip = 'Y';}
    
     if(!$skip)
     {
      $query  = "SELECT * FROM translations_eng WHERE phrase = \"".str_replace("\"", "\\\"", $phrase)."\" ";
      $result = mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error());
      $myrow  = mysql_fetch_array($result);
      if($myrow)
      {
       $query  = "UPDATE translations_eng SET active = 1 WHERE phrase = \"".str_replace("\"", "\\\"", $phrase)."\" ";
       mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error());
      }
      else
      {
       $query  = "INSERT INTO translations_eng SET active = 1, phrase = \"".str_replace("\"", "\\\"", $phrase)."\" ";
       mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error());;
      }
     }
    }
   }
  }   
 }
}



/*
// additional phrases
$aPhrases = array('Video', 'Audio', 'Text');
foreach($aPhrases as $aPhrase)
{
 $query  = "SELECT * FROM translations_eng WHERE phrase = \"".$aPhrase."\" ";
 $result = mysql_query($query) or die (mysql_error());
 $myrow  = mysql_fetch_array($result);
 if($myrow)
 {
  $query  = "UPDATE translations_eng SET active = 1 WHERE phrase = \"".$aPhrase."\" ";
  mysql_query($query) or die (mysql_error());
 }
 else
 {
  $query  = "INSERT INTO translations_eng SET active = 1, phrase = \"".$aPhrase."\" ";
  mysql_query($query);
 }
}
// end additional phrases
*/

$query  = "SELECT * FROM eth_codes WHERE code = \"".$_POST['dcode']."\" LIMIT 1";
$result_eth = mysql_query($query) or die (mysql_error());
$myrow_eth  = mysql_fetch_array($result_eth);
if(!$_POST['dname']) {$_POST['dname'] = $myrow_eth['description'];}


// add language names to translations
$t = array();
$query  = "SELECT * FROM translations ORDER BY name";
$result_t = mysql_query($query) or die (mysql_error());
while ($myrow_t=mysql_fetch_array($result_t))
{$t[] = $myrow_t['name'];}
$t[] = $_POST['dname'];

foreach($t as $name) 
{
 $query  = "SELECT * FROM translations_eng WHERE phrase = \"".$name."\" ";
 $result = mysql_query($query) or die (mysql_error());
 $myrow  = mysql_fetch_array($result);
 if($myrow)
 {
  $query  = "UPDATE translations_eng SET active = 1 WHERE phrase = \"".$name."\" ";
  mysql_query($query) or die (mysql_error());
 }
 else
 {
  $query  = "INSERT INTO translations_eng SET active = 1, phrase = \"".$name."\" ";
  mysql_query($query);
 }
}


// build translation table entries
if($_POST['dcode'])
{
 $tr_translations = '';
 $kbd_fields = '';
 if($_POST['dcode']=='eng')
 {
  $tr_translations = translate('English is the base language, no translation is required.', $_POST['dcode'], 'sys');  
 }
 else
 { 
  $query  = "SELECT * FROM translations_eng WHERE active = 1";
  $result = mysql_query($query) or die (mysql_error());
  while ($myrow=mysql_fetch_array($result))
  {
   if($_POST['dcode']!='eng')
   {
    $query  = "SELECT * FROM translations_".$_POST['dcode'] ." WHERE id = \"".$myrow['id']."\" ";
    $result_alt = mysql_query($query); // or die (mysql_error());
    $myrow_alt  = mysql_fetch_array($result_alt);

    if($_POST['devent']=='translate' and !$myrow_alt['phrase'])
    {
     $content = file_get_contents('http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q='.rawurlencode($myrow['phrase']).'&langpair=en%7C'.$_POST['dgoogle_code']);
     $content = json_decode($content);
     $myrow_alt['phrase'] = $content->{'responseData'}->{'translatedText'};
    }
    $tr_translations .= "
    <tr>
     <td>".$myrow['phrase']."</td>
     <td>
      <input id=\"phrase[".$myrow['id']."]\" name=\"phrase[".$myrow['id']."]\" value=\"".$myrow_alt['phrase']."\" size=50 dir=\"".$_POST['dlanguage_direction']."\">
    ";
 
    if($_POST['dgoogle_code'])
    {
     $tr_translations .= "
      <INPUT type=\"button\" value=\"".translate('Translate', $st, 'sys')."\" onclick=\"translate_this('".$myrow['phrase']."', 'phrase[".$myrow['id']."]', '".$_POST['dgoogle_code']."')\">
     ";
    }

    $tr_translations .= "
     </td>
    </tr>
    ";
    $kbd_fields .= "'phrase[".$myrow['id']."]',";
   }
   else
   {
    $tr_translations .= "<tr><td>". $myrow['phrase']."</td><td>&nbsp;</td></tr>";
   }
  }
  if($tr_translations)
  {
   $tr_translations = " 
   <table style=\"width:100%\" border>
    <tr>
     <th style=\"width:50%\"> ".translate('English', $st, 'sys')." </th>
     <th style=\"width:50%\"> ".translate($_POST['dname'], $st, 'sys')." </th>
    </tr>
    $tr_translations
   </table> 
   ";
  }   
 }
 if($_POST['devent']=='translate') {$submit_message = translate('Translation finished', $st, 'sys');}
}


$sname_options = "";
$query  = "SELECT * FROM translations ORDER BY name";
$result = mysql_query($query) or die (mysql_error());
while ($myrow=mysql_fetch_array($result))
{$sname_options .= "<OPTION VALUE=\"". $myrow['code']."\">".$myrow['code']." ~ ".translate($myrow['name'], $st, 'sys');}

$output = "


<SCRIPT LANGUAGE=JavaScript>

f = document.forms[0];

function sname_func()
{
 f.devent.value='sname';
 f.submit();
}

function clear_func()
{
 f.devent.value='clear';
 f.submit();
}

function translate_func()
{
 if(f.dcode.value=='eng') {alert('".translate('can not translate the base language', $st, 'sys')."'); return false;}
 var answer = confirm ('".translate('This function will translate all the blank fields using Google translate api', $st, 'sys').".\\r\\r".translate('Are you sure?', $st, 'sys')."');
 if (!answer) {return false;}
 f.devent.value='translate';
 f.submit();
}

function delete_func()
{
 if(f.dcode.value=='eng') {alert('".translate('can not delete the base language', $st, 'sys')."'); return false;}
 var answer = confirm ('".translate('You are about to permanently delete this record and all translations for the selected language', $st, 'sys').".\\r\\r".translate('Are you sure?', $st, 'sys')."');
 if (!answer) {return false;}
 f.devent.value='delete';
 f.submit();
}

function accept_func()
{
 errmsg = ''; 

 if(!f.dcode.value) {errmsg += '".translate('language code is required', $st, 'sys')."\\n';}
 if(!f.dname.value) {errmsg += '".translate('language name is required', $st, 'sys')."\\n';}

 if(errmsg)
 {
  alert(errmsg);
  return false;
 }

 f.devent.value='accept';
 f.submit();
}

function dcode_change()
{
 f.devent.value='dcode';
 f.submit();
}
";

$output .= "
function onload_func()
{
 f.dlanguage_direction.value = '".$_POST['dlanguage_direction']."';
 f.dgoogle_code.value = '".$_POST['dgoogle_code']."';
 f.dgoogle_keyboard.value = '".$_POST['dgoogle_keyboard']."';
";

 if($_POST['dgoogle_keyboard'])
 {
$output .= "
  var kbd = new google.elements.keyboard.Keyboard(
  [google.elements.keyboard.LayoutCode.".strtoupper($_POST['dgoogle_keyboard'])."],
  ['dcharIndex',".$kbd_fields."]);
";
 }

$output .= "
}
window.onload = onload_func;

function translate_this(phrase, fld, lc)
{
 google.language.translate(phrase, \"en\", lc, function(result) {
  if (!result.error) {
   var translated = document.getElementById(fld);
   translated.value = result.translation;   
  }
 });
}

</SCRIPT>

<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"> </script> 
<script type=\"text/javascript\">
 google.load(\"language\", \"1\");
 google.load(\"elements\", \"1\", {packages: \"keyboard\"});
</script> 

<style type=\"text/css\">
 div#kbd { position: fixed !important; }
</style>


  <p><h2>".translate('Manage Available Interface Translations', $st, 'sys')."</h2>
  <table>
   <tr>
    <td> ".translate('Translation language code', $st, 'sys')." </td>
    <td> <INPUT name=\"dcode\" value=\"".$_POST['dcode']."\"  maxLength=3 size=4 onchange=\"dcode_change(); data_change.value='Y';\"> 
     <SELECT name=\"scode\" onchange=sname_func();>
      <OPTION VALUE=\"select\">--".translate('select current language', $st, 'sys')." --
      ".$sname_options."
     </SELECT>
    </td>
   </tr> 
   <tr>
    <td> ".translate('Translation language name', $st, 'sys')." </td>
    <td> <INPUT id=\"dname\" name=\"dname\" value=\"".$_POST['dname']."\" size=50 onchange=\"data_change.value='Y'\"> </td>
   </tr> 
   <tr>
    <td> ".translate('Character Index', $st, 'sys')." </td>
    <td> <INPUT id=\"dcharIndex\" name=\"dcharIndex\" value=\"".$_POST['dcharIndex']."\" size=100 onchange=\"data_change.value='Y'\" dir=\"".$_POST['dlanguage_direction']."\"> </td>
   </tr> 
   <tr>
    <td> ".translate('Language direction', $st, 'sys')." </td>
    <td>
     <SELECT name=\"dlanguage_direction\" onchange=\"data_change.value='Y'\">
      <OPTION>
      <option value=\"ltr\"> ltr
      <option value=\"rtl\"> rtl
     </select>
    </td> 
   </tr> 
   <tr> 
    <td> Google ".translate('language code', $st, 'sys')." </td>
    <td>
     <SELECT name=\"dgoogle_code\" onchange=\"data_change.value='Y'\">
      <OPTION>
      <option value=\"af\">AFRIKAANS
      <option value=\"sq\">ALBANIAN
      <option value=\"am\">AMHARIC
      <option value=\"ar\">ARABIC
      <option value=\"hy\">ARMENIAN
      <option value=\"az\">AZERBAIJANI
      <option value=\"eu\">BASQUE
      <option value=\"be\">BELARUSIAN
      <option value=\"bn\">BENGALI
      <option value=\"bh\">BIHARI
      <option value=\"br\">BRETON
      <option value=\"bg\">BULGARIAN
      <option value=\"my\">BURMESE
      <option value=\"ca\">CATALAN
      <option value=\"chr\">CHEROKEE
      <option value=\"zh\">CHINESE
      <option value=\"zh-CN\">CHINESE_SIMPLIFIED
      <option value=\"zh-TW\">CHINESE_TRADITIONAL
      <option value=\"co\">CORSICAN
      <option value=\"hr\">CROATIAN
      <option value=\"cs\">CZECH
      <option value=\"da\">DANISH
      <option value=\"dv\">DHIVEHI
      <option value=\"nl\">DUTCH
      <option value=\"en\">ENGLISH
      <option value=\"eo\">ESPERANTO
      <option value=\"et\">ESTONIAN
      <option value=\"fo\">FAROESE
      <option value=\"tl\">FILIPINO
      <option value=\"fi\">FINNISH
      <option value=\"fr\">FRENCH
      <option value=\"fy\">FRISIAN
      <option value=\"gl\">GALICIAN
      <option value=\"ka\">GEORGIAN
      <option value=\"de\">GERMAN
      <option value=\"el\">GREEK
      <option value=\"gu\">GUJARATI
      <option value=\"ht\">HAITIAN_CREOLE
      <option value=\"iw\">HEBREW
      <option value=\"hi\">HINDI
      <option value=\"hu\">HUNGARIAN
      <option value=\"is\">ICELANDIC
      <option value=\"id\">INDONESIAN
      <option value=\"iu\">INUKTITUT
      <option value=\"ga\">IRISH
      <option value=\"it\">ITALIAN
      <option value=\"ja\">JAPANESE
      <option value=\"jw\">JAVANESE
      <option value=\"kn\">KANNADA
      <option value=\"kk\">KAZAKH
      <option value=\"km\">KHMER
      <option value=\"ko\">KOREAN
      <option value=\"ku\">KURDISH
      <option value=\"ky\">KYRGYZ
      <option value=\"lo\">LAO
      <option value=\"la\">LATIN
      <option value=\"lv\">LATVIAN
      <option value=\"lt\">LITHUANIAN
      <option value=\"lb\">LUXEMBOURGISH
      <option value=\"mk\">MACEDONIAN
      <option value=\"ms\">MALAY
      <option value=\"ml\">MALAYALAM
      <option value=\"mt\">MALTESE
      <option value=\"mi\">MAORI
      <option value=\"mr\">MARATHI
      <option value=\"mn\">MONGOLIAN
      <option value=\"ne\">NEPALI
      <option value=\"no\">NORWEGIAN
      <option value=\"oc\">OCCITAN
      <option value=\"or\">ORIYA
      <option value=\"ps\">PASHTO
      <option value=\"fa\">PERSIAN
      <option value=\"pl\">POLISH
      <option value=\"pt\">PORTUGUESE
      <option value=\"pt-PT\">PORTUGUESE_PORTUGAL
      <option value=\"pa\">PUNJABI
      <option value=\"qu\">QUECHUA
      <option value=\"ro\">ROMANIAN
      <option value=\"ru\">RUSSIAN
      <option value=\"sa\">SANSKRIT
      <option value=\"gd\">SCOTS_GAELIC
      <option value=\"sr\">SERBIAN
      <option value=\"sd\">SINDHI
      <option value=\"si\">SINHALESE
      <option value=\"sk\">SLOVAK
      <option value=\"sl\">SLOVENIAN
      <option value=\"es\">SPANISH
      <option value=\"su\">SUNDANESE
      <option value=\"sw\">SWAHILI
      <option value=\"sv\">SWEDISH
      <option value=\"syr\">SYRIAC
      <option value=\"tg\">TAJIK
      <option value=\"ta\">TAMIL
      <option value=\"tt\">TATAR
      <option value=\"te\">TELUGU
      <option value=\"th\">THAI
      <option value=\"bo\">TIBETAN
      <option value=\"to\">TONGA
      <option value=\"tr\">TURKISH
      <option value=\"uk\">UKRAINIAN
      <option value=\"ur\">URDU
      <option value=\"uz\">UZBEK
      <option value=\"ug\">UIGHUR
      <option value=\"vi\">VIETNAMESE
      <option value=\"cy\">WELSH
      <option value=\"yi\">YIDDISH
      <option value=\"yo\">YORUBA
     </SELECT>
    </td>
   </tr>
   </tr> 
    <td> Google ".translate('keyboard code', $st, 'sys')." </td>
    <td>
     <SELECT name=\"dgoogle_keyboard\" onchange=\"data_change.value='Y'\">
      <option>
      <option value=\"ALBANIAN\">ALBANIAN
      <option value=\"ARABIC\">ARABIC
      <option value=\"ARMENIAN_EASTERN\">ARMENIAN_EASTERN
      <option value=\"ARMENIAN_WESTERN\">ARMENIAN_WESTERN
      <option value=\"BASQUE\">BASQUE
      <option value=\"BELARUSIAN\">BELARUSIAN
      <option value=\"BENGALI_PHONETIC\">BENGALI_PHONETIC
      <option value=\"BOSNIAN\">BOSNIAN
      <option value=\"BRAZILIAN_PORTUGUESE\">BRAZILIAN_PORTUGUESE
      <option value=\"BULGARIAN\">BULGARIAN
      <option value=\"CATALAN\">CATALAN
      <option value=\"CROATIAN\">CROATIAN
      <option value=\"CZECH\">CZECH
      <option value=\"CZECH_QWERTZ\">CZECH_QWERTZ
      <option value=\"DANISH\">DANISH
      <option value=\"DARI\">DARI
      <option value=\"DUTCH\">DUTCH
      <option value=\"DEVANAGARI_PHONETIC\">DEVANAGARI_PHONETIC
      <option value=\"ENGLISH\">ENGLISH
      <option value=\"ESTONIAN\">ESTONIAN
      <option value=\"ETHIOPIC\">ETHIOPIC
      <option value=\"FINNISH\">FINNISH
      <option value=\"FRENCH\">FRENCH
      <option value=\"GALICIAN\">GALICIAN
      <option value=\"GEORGIAN_QWERTY\">GEORGIAN_QWERTY
      <option value=\"GEORGIAN_TYPEWRITER\">GEORGIAN_TYPEWRITER
      <option value=\"GERMAN\">GERMAN
      <option value=\"GREEK\">GREEK
      <option value=\"GUJARATI_PHONETIC\">GUJARATI_PHONETIC
      <option value=\"GURMUKHI_PHONETIC\">GURMUKHI_PHONETIC
      <option value=\"HEBREW\">HEBREW
      <option value=\"HINDI\">HINDI
      <option value=\"HUNGARIAN_101\">HUNGARIAN_101
      <option value=\"ICELANDIC\">ICELANDIC
      <option value=\"ITALIAN\">ITALIAN
      <option value=\"KANNADA_PHONETIC\">KANNADA_PHONETIC
      <option value=\"KAZAKH\">KAZAKH
      <option value=\"KHMER\">KHMER
      <option value=\"KOREAN\">KOREAN
      <option value=\"KYRGYZ\">KYRGYZ
      <option value=\"LAO\">LAO
      <option value=\"LATVIAN\">LATVIAN
      <option value=\"LITHUANIAN\">LITHUANIAN
      <option value=\"MACEDONIAN\">MACEDONIAN
      <option value=\"MALAYALAM_PHONETIC\">MALAYALAM_PHONETIC
      <option value=\"MALTESE\">MALTESE
      <option value=\"MONGOLIAN_CYRILLIC\">MONGOLIAN_CYRILLIC
      <option value=\"MONTENEGRIN\">MONTENEGRIN
      <option value=\"NORWEGIAN\">NORWEGIAN
      <option value=\"ORIYA_PHONETIC\">ORIYA_PHONETIC
      <option value=\"PAN_AFRICA_LATIN\">PAN_AFRICA_LATIN
      <option value=\"PASHTO\">PASHTO
      <option value=\"PERSIAN\">PERSIAN
      <option value=\"POLISH\">POLISH
      <option value=\"PORTUGUESE\">PORTUGUESE
      <option value=\"ROMANI\">ROMANI
      <option value=\"ROMANIAN\">ROMANIAN
      <option value=\"RUSSIAN\">RUSSIAN
      <option value=\"SANSKRIT_PHONETIC\">SANSKRIT_PHONETIC
      <option value=\"SERBIAN_CYRILLIC\">SERBIAN_CYRILLIC
      <option value=\"SERBIAN_LATIN\">SERBIAN_LATIN
      <option value=\"SINHALA\">SINHALA
      <option value=\"SLOVAK\">SLOVAK
      <option value=\"SLOVAK_QWERTY\">SLOVAK_QWERTY
      <option value=\"SLOVENIAN\">SLOVENIAN
      <option value=\"SOUTHERN_UZBEK\">SOUTHERN_UZBEK
      <option value=\"SPANISH\">SPANISH
      <option value=\"SWEDISH\">SWEDISH
      <option value=\"TAMIL_PHONETIC\">TAMIL_PHONETIC
      <option value=\"TATAR\">TATAR
      <option value=\"TELUGU_PHONETIC\">TELUGU_PHONETIC
      <option value=\"THAI\">THAI
      <option value=\"TURKISH_F\">TURKISH_F
      <option value=\"TURKISH_Q\">TURKISH_Q
      <option value=\"UIGHUR\">UIGHUR
      <option value=\"UKRAINIAN_101\">UKRAINIAN_101
      <option value=\"URDU\">URDU
      <option value=\"UZBEK_LATIN\">UZBEK_LATIN
      <option value=\"UZBEK_CYRILLIC_PHONETIC\">UZBEK_CYRILLIC_PHONETIC
      <option value=\"UZBEK_CYRILLIC_TYPEWRITTER\">UZBEK_CYRILLIC_TYPEWRITTER
      <option value=\"VIETNAMESE_TCVN\">VIETNAMESE_TCVN
      <option value=\"VIETNAMESE_TELEX\">VIETNAMESE_TELEX
      <option value=\"VIETNAMESE_VIQR\">VIETNAMESE_VIQR
     </SELECT>
    </td>
   </tr>
";

if($sec_readonly!='Y')
{
 $output .= "
   <tr>
     <td colspan=2> <br />
      <INPUT type=button value=\"".translate('Accept Record', $st, 'sys')."\" onclick=accept_func()>
      <INPUT type=button value=\"".translate('Clear Form', $st, 'sys')."\" onclick=clear_func()>
";

 if($_POST['dcode']!='eng')
 { 
  $output .= " 
      <INPUT type=button value=\"".translate('Delete Record', $st, 'sys')."\" onclick=delete_func()>
";

  if($_POST['dgoogle_code'])
  {
  $output .= "
      <INPUT type=button value=\"".translate('Translate', $st, 'sys')."\" onclick=translate_func()>
";
  }
 }

 $output .= "
      <span style=\"color:brown\"> ".$submit_message." </span>  
     </td>
   </tr> 
";
}


$output .= "
  </table>

  <p />
   ".$tr_translations."

  </div>
 
";

echo $output;

require "foot.php";

?>