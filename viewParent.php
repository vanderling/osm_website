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

echo "

<iframe src='view.php?iso=".$sec_code."' width=900px; height=740px;></iframe>


<h3>Embed code:</h3>
<hr>
&lt;iframe src='http://as.premiumtextreader.com/view.php?iso=".$sec_code."' width=900px; height=740px;&gt;&lt;/iframe&gt;
<hr>
<h3>Optional src parameters:</h3>
<table>
 <tr>
  <td style=\"color:brown\"> '&bibleTitle=' </td>  
  <td> - Default bible title to load </td>
 </tr>
 <tr>
  <td style=\"color:brown\"> '&bookName=' </td>
  <td> - Default book name to load </td>
 </tr>
 <tr>
  <td style=\"color:brown\"> '&st=' </td>
  <td> - Translate labels code </td>
 </tr>
</table>
";

require "foot.php";

?>