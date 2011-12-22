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
 
 <script language=JavaScript>

  window.open('syncChild.php','','fullscreen=1,status=0,toolbar=0,menubar=0,location=0,titlebar=0,scrollbars=1,width='+screen.width+',height='+screen.height);

 </script>

";

require "foot.php";

?>