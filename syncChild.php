<?php

/******************************************************************************/
/*  Developed by:  Ken Sladaritz                                              */
/*                 Marshall Computer Service                                  */
/*                 2660 E End Blvd S, Suite 122                               */
/*                 Marshall, TX 75672                                         */
/*                 ken@marshallcomputer.net                                   */
/******************************************************************************/

require 'config.php';
require "authorization.php";

if ( (!$menu_tab_access['sync.php'] and !$menu_tab_access['everything']) or $sec_password!=$myrow_us['us_pass'])
{
 echo '<span class="errmsg"> Access denied or timed out. Please Login. Thank you. </span>';
 require "./foot.php";
 exit;
}

// get annotations
$bibleTitleId = 1;
$bookId = 'Jhn';

if($_POST['bibleTitle'])
{
 $query =
 "SELECT * FROM `bibleTitles`
  WHERE `title` = \"".mysql_real_escape_string($_POST['bibleTitle'])."\"
  LIMIT 1 
 ";
 $result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
 $myrow=mysql_fetch_array($result);
 $bibleTitleId = $myrow['id']; 
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
 $bookId = $myrow['id'];
} 


if($_POST['devent']) 
{
 foreach($_POST['cords_p'] as $key=>$coordinates)
 {
  $query =
  "UPDATE `notations` SET
   `coordinates`        = \"".$coordinates."\"
   WHERE `key`          = \"".$key."\"
   AND   `bibleTitleId` = \"".$bibleTitleId."\"
   AND   `bookId`       = \"".$bookId."\"
   LIMIT 1 
   ";
   mysql_query($query) or die ("<pre>".$query."</pre>".mysql_error()."</pre>");
 }
}


// get document image thumbnails
$docDir = 'docs/'.$bookId;
$docSelections = '';
$annotations = '';

$page = 0;
$files = scandir($docDir);
foreach($files as $file)
{
 list($filename, $ext) = explode('.', $file);
 if($ext=='jpg')
 {
  $page++;
  $docSelections .= "
  <p id=\"".$docDir."/".$file."\" onclick=\"setDoc('".$docDir."/".$file."');\">
   ".$file."
   <img class=\"thumbSelection\" src=\"".$docDir."/thumbs/".$file."\">
  </p>
";  
 }
}

$js_coordinates = '';
$query =
"SELECT * FROM `notations`
 WHERE `bibleTitleId` = \"".$bibleTitleId."\"
 AND   `bookId`       = \"".$bookId."\"
 AND   `inactive`    != \"Y\"
 ORDER BY `key`
";
// AND   `quote`        > \"\"
$result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
while($myrow=mysql_fetch_array($result))
{
 $style = "style=\"padding-top:5px;\"";
 if($myrow['coordinates']) {$style="style=\"padding-top:5px; color:green;\"";}
 
 $dnotation = '';
 $notations = unserialize($myrow['notation']);
 $paragraphs = unserialize($myrow['paragraph']);
 $i = 0; 
 foreach($notations as $notation) 
 {
  if($paragraphs[$i]=='Y') 
  {
   if($i==0) {$dnotation = "<p />".$dnotation;}
   else {$notation = "<div class=\"paragraph\">&nbsp;&nbsp".$notation."</div>";}
  }
  $dnotation .= $notation." ";
  $i++;
 }

 $annotations .= "
  <div $style name=\"p".$myrow['key']."\" id=\"p".$myrow['key']."\" onclick=\"focusAnnotation(this, '".str_replace("'", "\\'", strip_tags($dnotation))."');\">
   <div style=\"float:right\">
    <img src='images/edit.png'
     onclick=\"editVerse('".$myrow['id']."','".$myrow['key']."');\"
     title='".translate('Delete image highlight', $st, 'sys')."'>
    <img src='images/delete.png'
     onclick=\"removeElement('p".$myrow['key']."');\"
     title='".translate('Delete image highlight', $st, 'sys')."'>
   </div>
   <b>".$myrow['key']."</b>
   <input name=\"cords_p[".$myrow['key']."]\" id=\"cords_p".$myrow['key']."\" value=\"".$myrow['coordinates']."\" type=\"hidden\">
  </div>";

//  <br />".$myrow['quote']."

 if($myrow['coordinates']) {$js_coordinates .= "\"p".$myrow['key']."\":\"".$myrow['coordinates']."\",";}
}
$js_coordinates = rtrim($js_coordinates, ",");



// get bible title options
$bibleTitle_options = '';
$query =
"SELECT * FROM `bibleTitles`
 WHERE `code` = \"".mysql_real_escape_string($sec_code)."\"
 ORDER BY `title`
";
$result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
while($myrow=mysql_fetch_array($result))
{
 $selected = '';
 if($myrow['title']==$_POST['bibleTitle']) {$selected = 'selected';}
 $bibleTitle_options .= "<option value=\"".$myrow['title']."\" ".$selected.">".$myrow['title'];
}

// get book name options
$bookName_options = '';
$query =
"SELECT * FROM `books`
 WHERE `bibleTitleId` = \"".$bibleTitleId."\"
 ORDER BY `displayOrder`, `name`
";
$result=mysql_query($query) or die ("<pre>".$query.mysql_error()."</pre>");
while($myrow=mysql_fetch_array($result))
{
 $selected = '';
 if($myrow['name']==$_POST['bookName']) {$selected = 'selected';}
 $bookName_options .= "<option value=\"".$myrow['name']."\" ".$selected.">".$myrow['name'];
}


echo "
<!DOCTYPE html>
<html>
<head>
 <meta content=\"text/html; charset=UTF-8\" http-equiv=\"content-type\">
 <title>".translate('Tag image quotes', $st, 'sys')."</title>
 <link REL=\"SHORTCUT ICON\" HREF=\"images/favicon.ico\">
 <link type=\"text/css\" rel=\"stylesheet\" href=\"style.css\">

 <script language=JavaScript>

  imageFile = '".$_POST['imageFile']."';  
  lastPFocus = '';
  lastDFocus = '';
  PFocusCount = 0;
  docFocus = false;
  coordinates = {".$js_coordinates."};

  function setDoc(file)
  {
   PFocusCount = 0;
   if(imageFile) {document.getElementById(imageFile).style.background='';}
   document.getElementById(file).style.background='#ccccff';
   document.getElementById(\"doc\").innerHTML = \"<img src='\"+file+\"' onMouseover='docFocus=true;' onMouseout='docFocus=false;'>\";

   imageFile = file;
   document.getElementById('imageFile').value = file;
   document.getElementById(file).scrollIntoView(true);


   for(key in coordinates)
   {
    var c = coordinates[key].split(',');
    var file = c[0];
    var t    = c[1];
    var l    = c[2];
    var h    = c[3];
    var w    = c[4];

    if(file==imageFile)
    {
     var ni = document.getElementById('doc');
     var newdiv = document.createElement('div');
     var divIdName = 'marker_'+key;
     newdiv.setAttribute('id',divIdName);
     newdiv.setAttribute('onMouseover','docFocus=true;');
     newdiv.style.position = 'absolute';
     newdiv.style.top = t;
     newdiv.style.left = l;
     newdiv.style.width = w;
     newdiv.style.height = h;
     newdiv.style.zIndex = '2';
     newdiv.style.background = '#009900';
     newdiv.style.filter = 'alpha(opacity=50)';
     newdiv.style.MozOpacity = .5;
     newdiv.style.opacity = .5;
     ni.appendChild(newdiv);
    }
   }
  }


  function focusAnnotation(p, notation)
  {
   updateCoordinates();

   var c = document.getElementById('cords_'+p.id).value.split(',');
   var file = c[0];

   if(file && file!=imageFile) {setDoc(file);}

   // unset focus on last annotation 
   if(lastPFocus) 
   {
    document.getElementById(lastPFocus).style.background='';
    if(document.getElementById('marker_'+lastPFocus))
    { 
     document.getElementById(lastPFocus).style.color='green';
     document.getElementById('marker_'+lastPFocus).style.border='';
     PFocusCount = 0;
    }
   }

   // set focus on current annotation
   p.style.background='#ccccff';

   if(document.getElementById('marker_'+p.id))
   { 
    document.getElementById('marker_'+p.id).style.border='.1em dotted red';
    PFocusCount = 1;
   }

   lastPFocus = p.id;

   document.getElementById('notation').innerHTML=notation;
  }


  function updateCoordinates()
  {
   if(document.getElementById('marker_'+lastPFocus))
   {
    var ni = document.getElementById('marker_'+lastPFocus);
    coordinates[lastPFocus] = imageFile +','+ ni.style.top +','+ ni.style.left +','+ ni.style.height +','+ ni.style.width;
    document.getElementById('cords_'+lastPFocus).value = coordinates[lastPFocus];
   }
  }


  function addElement(t,l)
  {
   var ni = document.getElementById('doc');
   var newdiv = document.createElement('div');
   var divIdName = 'marker_'+lastPFocus;
   newdiv.setAttribute('id',divIdName);
   newdiv.setAttribute('onMouseover','docFocus=true;');
   newdiv.style.position = 'absolute';
   newdiv.style.top = t+'px';
   newdiv.style.left = l+'px';
   newdiv.style.width = '30px';
   newdiv.style.height = '30px';
   newdiv.style.border = '.1em dotted red';
   newdiv.style.zIndex = '2';
   newdiv.style.background = '#009900';
   newdiv.style.filter = 'alpha(opacity=50)';
   newdiv.style.MozOpacity = .5;
   newdiv.style.opacity = .5;
   ni.appendChild(newdiv);
  }

/*
  function extendElement(tt,ll)
  {
   if(document.getElementById('marker_'+lastPFocus))
   {
    var ni = document.getElementById('marker_'+lastPFocus);
    t  = ni.offsetTop;
    l  = ni.offsetLeft;

    if(tt>t) {h = tt-t;} else {h = (t-tt)+parseInt(ni.style.height); t=tt;}
    if(h<30) {h = 30;}

    if(ll>l) {w = ll-l;} else {w = (l-ll)+parseInt(ni.style.width); l=ll;}
    if(w<10) {w = 10;}

    ni.style.top = t+'px';
    ni.style.left = l+'px'; 
    ni.style.height = h+'px';
    ni.style.width  = w+'px'; 
   }
  }
*/

  function removeElement(el)
  {
   coordinates[el]='';
   document.getElementById('cords_'+el).value = '';
   document.getElementById(el).style.color = '';
   var d = document.getElementById(\"doc\");
   var olddiv = document.getElementById('marker_'+el);
   d.removeChild(olddiv);
   PFocusCount = 0;
  }

  // Detect if the browser is IE or not.
  // If it is not IE, we assume that the browser is NS.
  var IE = document.all?true:false;
  var tempX = 0;
  var tempY = 0;
  var sav_tempX = 0;
  var sav_tempY = 0;

  document.onmousemove = getMouseXY;
  function getMouseXY(e)
  {
   if(!sav_tempY) {sav_tempY = tempY;}
   if(!sav_tempX) {sav_tempX = tempX;}

   if (IE) { // grab the x-y pos.s if browser is IE
    if(event && document.body) { 
     tempX = event.clientX + document.body.scrollLeft;
     tempY = event.clientY + document.body.scrollTop;
    }
   } else {  // grab the x-y pos.s if browser is NS
    if(e)
    { 
     tempX = e.pageX + document.body.scrollLeft;
     tempY = e.pageY + document.body.scrollLeft;
    } 
   }  
   // catch possible negative values in NS4
   if (tempX < 0) {tempX = 0;}
   if (tempY < 0) {tempY = 0;}  

   if(mouse_rc_pressed)
   {
    var ni = document.getElementById('marker_'+lastPFocus);
    ni.style.top = ni.offsetTop+(tempY-sav_tempY)+'px';
    ni.style.left = ni.offsetLeft+(tempX-sav_tempX)+'px'; 
   }
   sav_tempX = 0;
   sav_tempY = 0; 
 }


 mouse_lc_pressed = 0;
 mouse_rc_pressed = 0;
 document.onmousedown=click_down;
 function click_down(e)
 {
  if (IE) {
   if(event.button == 1) {
    if(mouse_lc_pressed==0) {mouse_lc_pressed = 1; mouse_rc_pressed = 0;} else {mouse_lc_pressed = 0;}
   }
   if(event.button==2) {
    if(mouse_rc_pressed==0) {mouse_rc_pressed = 1; mouse_lc_pressed = 0;} else {mouse_rc_pressed = 0;}
   }   
  }
  else {
   if(e.which == 1) {
    if(mouse_lc_pressed==0) {mouse_lc_pressed = 1; mouse_rc_pressed = 0;} else {mouse_lc_pressed = 0;}
   }
   if(e.which == 3) {
    if(mouse_rc_pressed==0) {mouse_rc_pressed = 1; mouse_lc_pressed = 0;} else {mouse_rc_pressed = 0;}
   }
  }

  if(docFocus && lastPFocus)
  {


   var t = e.pageY - document.getElementById(\"doc\").offsetTop + document.getElementById(\"doc\").scrollTop;
   var l = e.pageX - document.getElementById(\"doc\").offsetLeft;  
   if(!PFocusCount && mouse_lc_pressed) {addElement(t,l); PFocusCount++;}
//   if(PFocusCount && mouse_lc_pressed)  {extendElement(t,l);}

  }
 }

 document.onmouseup=click_up;
 function click_up()
 {
  mouse_lc_pressed = 0;
  mouse_rc_pressed = 0;
  sav_tempX = 0;
  sav_tempY = 0;
 }
  
 function saveFunc()
 {
  updateCoordinates();
  document.getElementById(\"devent\").value='save';
  document.form1.submit();
 }

 function editVerse(el,key)
 {
  editScreen = window.open('edit.php?id='+el+'&key='+key,'editScreen','status=0,toolbar=0,menubar=0,location=0,titlebar=0,scrollbars=1,width=1150px,height=800px');
  editScreen.focus();
 }

</script>

</head>

<form id=\"form1\" name=\"form1\" action=\"\" method=post enctype=\"multipart/form-data\">

<body oncontextmenu=\"return false;\">

    <div id=\"docSelect\">
     ".$docSelections."
    </div>
 
    <div id=\"bookSelect\">
     &nbsp;&nbsp;&nbsp;&nbsp;
     ".translate('Bible or Testament name', $st, 'sys')."
     <select name=\"bibleTitle\" onchange=\"submit();\">
      <option value=\"\"> -- ".translate('Select a current title', $st, 'sys')." -- 
      ".$bibleTitle_options."
     </select>
      &nbsp;&nbsp;&nbsp;&nbsp;
     ".translate('Book name', $st, 'sys')."
     <select name=\"bookName\" onchange=\"submit();\">
      <option value=\"\"> -- ".translate('Select a current name', $st, 'sys')." -- 
      ".$bookName_options."
     </select> 
     &nbsp;&nbsp;&nbsp;&nbsp;
     <input name=\"imageFile\" id=\"imageFile\" style=\"border:0;\" type=\"hidden\">
    </div>

    <div id=\"doc\">
     <h2> &nbsp;&nbsp;&nbsp; ".translate('Select image from left to start processing', $st, 'sys').".</h2>
    </div>
    <div id=\"notation\"></div>

    <div id=\"pageControlTop\">
     <input type=\"button\" value=\"".translate('Save', $st, 'sys')."\" onclick=\"saveFunc();\"> 
     <input type=button value=\"".translate('Home', $st, 'sys')."\" onclick=\"window.close();\">
    </div>

    <div id=\"annotations\">
      ".$annotations."
    </div>

    <div id=\"pageControlBottom\">
     <input type=\"button\" value=\"".translate('Save', $st, 'sys')."\" onclick=\"saveFunc();\"> 
     <input type=button value=\"".translate('Home', $st, 'sys')."\" onclick=\"window.location='https://marshallcomputer.org/Turk/'\">
    </div>

 <input name=\"devent\" id=\"devent\" type=\"hidden\">
 <input name=\"bibleTitleId\" value=\"".$bibleTitleId."\" type=\"hidden\">
 <input name=\"bookId\" value=\"".$bookId."\" type=\"hidden\">


 <script language=JavaScript>
  if(imageFile) {setDoc(imageFile);}
 </script>


</body>
</form>
</html>
";

?>