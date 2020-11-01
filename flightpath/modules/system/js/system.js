
/**
 * This will allow us to have HTML tags in dialog titles.
 */
$.widget("ui.dialog", $.extend({}, $.ui.dialog.prototype, {
    _title: function(title) {
        if (!this.options.title ) {
            title.html("&#160;");
        } else {
            title.html(this.options.title);
        }
    }
}));



// Set up our modal dialogs on startup.
$(document).ready(function() {
  
    
    // TODO: use settings for width/height, if its been set.  This allows us to let the end user configure the size.
    var modalWidth = 500;
    var modalHeight = 400;
    $("#fp-iframe-dialog-small").dialog({
      modal: true,      
      show: {
        effect: "blind",
        duration: 300
      },
      resize: function (event, ui) {
                      var heightDifference = 10;
                      var widthDifference = 1;
                      $("#fp-iframe-dialog-small iframe.dialog-iframe").height($(this).height() - heightDifference);
                      $("#fp-iframe-dialog-small iframe.dialog-iframe").width($(this).width() - widthDifference);
                  },   
      open: function (event, ui) {
                      var heightDifference = 10; 
                      var widthDifference = 1;  
                                                                 
                      $("#fp-iframe-dialog-small iframe.dialog-iframe").height($(this).height() - heightDifference);
                      $("#fp-iframe-dialog-small iframe.dialog-iframe").width($(this).width() - widthDifference);
                      
                      // Get rid of annoying focus on close button.
                      $( this ).siblings( ".ui-dialog-titlebar" ).find( "button" ).blur();                       
                      
                  },
      close: function (event, ui) {
                      //url = FlightPath.settings.basePath + "/inc/static-screens/dialog-empty.php?mode=loading";      
                      //$("#fp-iframe-dialog-small iframe.dialog-iframe").attr('src', url);
                      $("#fp-iframe-dialog-small iframe.dialog-iframe").attr('src', 'about:blank');
                      
                  },
      autoOpen: false,
      resizable: false,
      width: modalWidth,      
      height: modalHeight      
    });     
    
    
  
  
  
  
    ////////////////////////
    // Large iframe dialog
    modalWidth = 700;
    modalHeight = 600;
    $("#fp-iframe-dialog-large").dialog({
      modal: true,
      show: {
        effect: "blind",
        duration: 300
      },      
      resize: function (event, ui) {
                      var heightDifference = 10;
                      var widthDifference = 1;
                      $("#fp-iframe-dialog-large iframe").height($(this).height() - heightDifference);
                      $("#fp-iframe-dialog-large iframe").width($(this).width() - widthDifference);
                  },   
      open: function (event, ui) {
                      var heightDifference = 10;
                      var widthDifference = 1;  
                                           
                      $("#fp-iframe-dialog-large iframe").height($(this).height() - heightDifference);
                      $("#fp-iframe-dialog-large iframe").width($(this).width() - widthDifference);
                      // Get rid of annoying focus on close button.
                      $( this ).siblings( ".ui-dialog-titlebar" ).find( "button" ).blur();                       

                  },
      close: function (event, ui) {
                      //url = FlightPath.settings.basePath + "/inc/static-screens/dialog-empty.php?mode=loading";      
                      //$("#fp-iframe-dialog-large-iframe").attr('src', url);
                      $("#fp-iframe-dialog-large-iframe").attr('src', 'about:blank');
                  },
      autoOpen: false,
      resizable: false,
      width: modalWidth,      
      height: modalHeight
    });     
  
    // Give the dialog an initial screen
    //url = FlightPath.settings.basePath + "/inc/static-screens/dialog-empty.php?mode=loading";
    //$("#fp-iframe-dialog-large-iframe").attr('src', url);    
    $("#fp-iframe-dialog-large-iframe").attr('src', 'about:blank');
  
  
    
  
});



  function fpOpenLargeIframeDialog(url, title) {
    
    // make sure we have the "?initial_dialog_open=yes" set in the URL, so we will know if we
    // should keep "nudging" or not in Chrome.
    if (!url.includes("?")) {
      url = url + "?";
    }
    else {
      url = url + "&";
    }

    url = url + "initial_dialog_open=yes";    
    
    
    $("#fp-iframe-dialog-large-iframe").attr('src', url);
    $("#fp-iframe-dialog-large").dialog({title: title});    
    $("#fp-iframe-dialog-large").dialog('open');
  }


  function fpCloseLargeIframeDialog() {
      $("#fp-iframe-dialog-large").dialog('close'); 
  }



  /**
   * This is to fix a bug in Chrome where the iframe does not display any content (though it is there)
   * until the dialog is moved slightly.  This gets called by the dialog itself, via:  parent.fpNudgeDialog() in its document.ready().
   */
  function fpNudgeDialog() {

    setTimeout(function() {
    
      $("div[role=dialog]").each(function() {
        
        $(this).addClass('ui-draggable-dragging');        
        $(this).addClass('ui-dialog-dragging');
        var x = $(this).css('top');
        var oldx = x;        
        x = parseFloat(x.replace("px", ""));        
        var newx = (x + 1) + "px";              
        $(this).css('top',  newx);
      
        var that = $(this);
              
        //setTimeout(function() {
        //  $(that).css('top',  oldx);
        //}, 10);                
          
      });
        
      
      
    
    }, 50);

    
  }



  function fpOpenSmallIframeDialog(url, title) {

    // make sure we have the "?initial_dialog_open=yes" set in the URL, so we will know if we
    // should keep "nudging" or not in Chrome.
    if (!url.includes("?")) {
      url = url + "?";
    }
    else {
      url = url + "&";
    }

    url = url + "initial_dialog_open=yes";


    $("#fp-iframe-dialog-small iframe.dialog-iframe").attr('src', url);
    
    $("#fp-iframe-dialog-small").dialog({title: title});
   
    $("#fp-iframe-dialog-small").dialog('open');
        
  }

  


  function fpCloseSmallIframeDialog() {

    $("#fp-iframe-dialog-small").dialog('close');
      
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


  // Replacement for built-in alert(str).  Uses the DayPilot code.  See the /inc/ directory.
  function fp_alert(str) {
    DayPilot.Modal.alert(str);
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
