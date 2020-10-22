

// Set up our modal dialogs on startup.
$(document).ready(function() {
  
    // Set up out iframe/dialog (if it is on the page)
    // TODO:  on close, set back to an "updating" screen.
    var modalWidth = 500;
    var modalHeight = 400;
    $("#fp-iframe-dialog-small").dialog({
      modal: true,
      resize: function (event, ui) {
                      var heightDifference = 10;
                      var widthDifference = 0;
                      $("#fp-iframe-dialog-small iframe").height($(this).height() - heightDifference);
                      $("#fp-iframe-dialog-small iframe").width($(this).width() - widthDifference);
                  },   
      open: function (event, ui) {
                      var heightDifference = 10;
                      var widthDifference = 0;  
                      $(this).parent().css('position', 'fixed');                     
                      $("#fp-iframe-dialog-small iframe").height($(this).height() - heightDifference);
                      $("#fp-iframe-dialog-small iframe").width($(this).width() - widthDifference);
                  },
      autoOpen: false,
      resizable: true,/*
      position: {
            my: "center",
            at: "top",
            of: window
      },*/
      width: modalWidth,      
      height: modalHeight
    });     
  
  
});



  function fpOpenSmallIframeDialog(url, title) {
    $("#fp-iframe-dialog-small-iframe").attr('src', url);
    $("#fp-iframe-dialog-small").dialog({title: title});    
    $("#fp-iframe-dialog-small").dialog('open');

  }



  //+ Jonas Raoni Soares Silva  
  //@ http://jsfromhell.com
  // Found this function on the Internet.  It acts like php str_replace function:
  // f = find  ex:  "bob"
  // r = replace  ex:  "sally"
  // s = string  ex:  "bob is a girl name"
  function str_replace(f, r, s) {
      var ra = r instanceof Array, sa = s instanceof Array, l = (f = [].concat(f)).length, r = [].concat(r), i = (s = [].concat(s)).length;
      while(j = 0, i--)
        while(s[i] = s[i].split(f[j]).join(ra ? r[j] || "" : r[0]), ++j < l);
    return sa ? s : s[0];
  }


  /**
   * Similar to the php function flightpath, this will remove any non alphanumeric characters
   * and replace with a _ (underscore)
   * @param {Object} str
   */
  function fp_get_machine_readable(str) {
    return str.replace(/[\W_]+/g,"_");
  }    



	
	function popupreportcontact()
	{
	  
	  // Figure out the window's options from our settings, if they exist.
	  var win_options = FlightPath.settings.popupAdminWinOptions;
	  if (!win_options) {
	    win_options = "toolbar=no,status=2,scrollbars=yes,resizable=yes,width=600,height=400"; 
	  }
	  
	  
	  // To make compatible with non-clean URLs, use the "unclean" url...
	  var url = FlightPath.settings.basePath + "/index.php?q=popup-report-contact";
	  
		err_window = window.open(url,
		   "errwindow", win_options);

		err_window.focus();  // make sure the popup window is on top.

	}



	function showUpdate(boolShowLoad)
	{
		var scrollTop = document.body.scrollTop;
		var updateMsg = document.getElementById("updateMsg");
		if (boolShowLoad == true)
		{
			updateMsg = document.getElementById("loadMsg");
		}
		var w = document.body.clientWidth;
		//var h = document.body.clientHeight;
		//var t = scrollTop + (h/2);
		var t = scrollTop;
		updateMsg.style.left = "" + ((w/2) - 120) + "px";
		updateMsg.style.top = "" + t + "px";

		updateMsg.style.position = "absolute";  // must use absolute for ie.
		updateMsg.style.display = "";
	}
