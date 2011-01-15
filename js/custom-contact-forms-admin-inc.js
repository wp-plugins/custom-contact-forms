function showCCFUsagePopover() {
	jQuery("#ccf-usage-popover").fadeIn('slow');	
}

(function(jQuery) {
  var cache = [];
  // Arguments are image paths relative to the current page.
  jQuery.preloadImages = function() {
    var args_len = arguments.length;
    for (var i = args_len; i--;) {
      var cacheImage = document.createElement('img');
      cacheImage.src = arguments[i];
      cache.push(cacheImage);
    }
  }
})(jQuery)

var fx = {
	"initModal" : function() {
		if (jQuery(".modal-window").length == 0) {
			return jQuery("<div>")
				.addClass("modal-window")
				.appendTo("body");
		} else {
			return jQuery(".modal-window");
		}
	},
	
	"initDebugWindow" : function() {
		if (jQuery(".debug-window").length == 0) {
			debug = jQuery("<div>").addClass("debug-window").appendTo("body");
			debug.click(function() { debug.remove(); });
			return debug;
		} else {
			return jQuery(".debug-window");
		}
	},
	
	"initSaveBox" : function(text) {
		if (jQuery(".save-box").length == 0) {
			box = jQuery("<div>").addClass("save-box").appendTo("body");
			jQuery("<a>")
				.attr("href", "#")
				.addClass("save-box-close-btn")
				.html("&times;")
				.click(function(event) { event.preventDefault(); jQuery(".save-box").fadeOut("slow"); })
				.appendTo(box);
			jQuery("<p>").html(text + ' <img src="' + ccf_plugin_dir + '/images/wpspin_light.gif" />').appendTo(".save-box");
			return box;
		} else {
			return jQuery(".save-box");
		}
	},
	
	"boxOut": function(event) {
		if (event != undefined) event.preventDefault();
		jQuery(".modal-window").fadeOut("slow", function() { jQuery(this).remove(); });
	}
};