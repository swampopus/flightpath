<?php
/*
	This is very similar to the fp_template, except this is formatted
	to be used with content which is supposed to get printed out.
*/

if ($pageTitle == "")
{ // By default, page title is this...
	$pageTitle = $GLOBALS["fpSystemSettings"]["schoolInitials"] . " FlightPath";
}

$themeLocation = $GLOBALS["fpSystemSettings"]["baseURL"] . "/" . $GLOBALS["fpSystemSettings"]["theme"];

print "
<link rel='stylesheet' type='text/css' href='$themeLocation/style.css'>";

 // Load any extra CSS files which addon modules might have added.
 if (is_array($pageExtraCssFiles) && count($pageExtraCssFiles) > 0) {
   foreach ($pageExtraCssFiles as $cssFileName) {
     print "<link rel='stylesheet' type='text/css' href='$cssFileName'>";
   }
 }



print "
<title>$pageTitle</title>
";

?>
<body style='background-color: white;'>
<!-- TEXT LOGO -->
<table width='500' border='0'>
	  <td valign='middle'>
	    <span style='font-size: 10pt;'><i>
	    electronic student advising system
	    </i></span>
	   </td>
	   <td valign='middle'>
	     <span style='font-family: Times New Roman; font-size: 30pt;'><i>flightpath</i>
	     	 <font color='#660000'><?php print $GLOBALS["fpSystemSettings"]["schoolInitials"]; ?></font></span>
	   </td>
  </table>
<!-- PRINT BUTTON -->
<div style='margin-bottom:10px;' class='print-graphic hand' onClick='window.print();'>
&nbsp;
</div>

<table border='0' width='650' cellspacing='0' cellpadding='0'>
<td valign='top'>
<!-- PAGE CONTENT -->
<?php print $pageContent; ?>
</td> 
</table>

</body>