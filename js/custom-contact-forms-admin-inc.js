var $j = jQuery.noConflict();

function showCCFUsagePopover() {
	$j("#ccf-usage-popover").fadeIn('slow');	
}

var fx = {
	"initModal" : function() {
		if ($j(".modal-window").length == 0) {
			return $j("<div>")
				.addClass("modal-window")
				.appendTo("body");
		} else {
			return $j(".modal-window");
		}
	},
	
	"initDebugWindow" : function() {
		if ($j(".debug-window").length == 0) {
			debug = $j("<div>").addClass("debug-window").appendTo("body");
			debug.click(function() { debug.remove(); });
			return debug;
		} else {
			return $j(".debug-window");
		}
	},
	
	"initSaveBox" : function() {
		if ($j(".save-box").length == 0) {
			box = $j("<div>").addClass("save-box").appendTo("body");
			$j("<a>")
				.attr("href", "#")
				.addClass("save-box-close-btn")
				.html("&times")
				.click(function(event) { event.preventDefault(); $j(".save-box").fadeOut("slow"); })
				.appendTo(box);
			$j("<p>").html('Saving <img src="' + ccf_plugin_dir + '/images/wpspin_light.gif" />').appendTo(".save-box");
			return box;
		} else {
			return $j(".save-box");
		}
	},
	
	"boxOut": function(event) {
		if (event != undefined) event.preventDefault();
		$j(".modal-window").fadeOut("slow", function() { $j(this).remove(); });
	}
};