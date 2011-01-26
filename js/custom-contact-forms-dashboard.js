/* Custom Contact Forms Dashboard Javascript */

$j = jQuery.noConflict();

$j(document).ready(function() {
	$j("input.ccf-view-submission").click(function() {
		var submission_window = $j(this).next();
		submission_window.find("div.close").click(function() {
			submission_window.fadeOut("slow");
		});
		submission_window.fadeIn("slow");
		
	});
});