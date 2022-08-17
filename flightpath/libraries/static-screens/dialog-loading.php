<?php
/**
 * This PHP script is meant to show within a "dialog" popup, and will simply display a
 * message that the screen is updating, loading, etc.
 * 
 */
 
 $msg = "Loading...";
 
 if (@$_GET['mode'] == 'updating') {
   $msg = "Updating...";
 }
 
 $modulefunc = @trim($_GET['modulefunc']);
 
 // TODO:  make this more interesting!
 ?>
 <head>
 <style>
   body {
     font-family: "Segoe UI", Arial, Helvetica, sans-serif;
     font-size: 1.2em;
     padding: 10px;
   }
 </style>
 <script type='text/javascript'>
  
  // Once this page is fully loaded, we assume there is a function on the parent page, to call named $modulefunc_finishedLoadingEmptyDialog.  $modulefunc is from 
  // the GET.
  document.addEventListener("DOMContentLoaded", function(){
    //dom is fully loaded, but maybe waiting on images & css files
    parent.<?php print $modulefunc;?>_finishedLoadingEmptyDialog();    
  });  
  
 </script>
</head>

<body>
  <div class='updating-msg'>
    <img src='throbber.gif'>&nbsp;<?php print $msg; ?>
  </div>
</body>