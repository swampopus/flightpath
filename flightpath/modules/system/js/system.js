
		
		function popupreportcontact()
		{
			err_window = window.open("./popup-report-contact",
			"errwindow","toolbar=no,status=2,scrollbars=yes,resizable=yes,width=500,height=400");

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
