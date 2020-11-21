<?php
/**
 * This PHP script is meant to show within a "dialog" popup, and will simply display a
 * message that the screen is updating, loading, etc.
 * 
 */
 
 $msg = "Loading...";
 
 if ($_GET['mode'] == 'updating') {
   $msg = "Updating...";
 }
 
 // TODO:  make this more interesting!
 ?>
 <head>
 <style>
   body {
     font-family: "Segoe UI", Arial, Helvetica, sans-serif;
   }
 </style>
</head>

<body>
  <div class='updating-msg'>
    <?php print $msg; ?>
  </div>
</body>