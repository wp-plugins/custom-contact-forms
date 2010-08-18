// Custom Contact Forms plugin javascript

var cid;
function storePopupVar(form_id) {
	cid = form_id;
}

var $j = jQuery.noConflict();

$j(document).ready(function(){
	/*$j('<div></div>')
			.prependTo('body')
			.attr('id', 'ccf-popover')
			.load('wp-content/plugins/custom-contact-forms/custom-contact-forms-popover.php');*/
	$j('<a></a>')
		.addClass('ccf-popover-close')
		.html('[close]')
		.prependTo('.ccf-popover');
	$j('.ccf-popover').css({'padding' : '10px 14px 10px 10px'});
	$j("a#in").click(function(){
		var sel = ".ccf-popover" + cid;
		$j(".ccf-popover1").fadeIn();

	});
	$j(".ccf-popover-close").click(function(){
		$j(".ccf-popover").hide();
	});
	$j('.show-field-instructions').click(function() {
													  
	});
	
	$j(".tooltip-field").tooltip({
		// place tooltip on the right edge
		position: "center right",
		// a little tweaking of the position
		offset: [-2, 10],
		// use the built-in fadeIn/fadeOut effect
		effect: "fade",
		// custom opacity setting
		opacity: 0.7
	
	});
});