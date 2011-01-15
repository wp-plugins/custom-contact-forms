// Custom Contact Forms plugin javascript

jQuery(document).ready(function ($) {
	$('<a></a>')
		.addClass('ccf-popover-close')
		.html('[close]')
		.prependTo('.ccf-popover');
	$('.ccf-popover').css({'padding' : '10px 14px 10px 10px'});
	$("a#in").click(function(){
		var sel = ".ccf-popover" + cid;
		$(".ccf-popover1").fadeIn();

	});
	$(".ccf-popover-close").click(function(){
		$(".ccf-popover").hide();
	});
	$('.show-field-instructions').click(function() {
													  
	});
	
	$(".ccf-tooltip-field").tooltip({
		position: "center right",
		offset: [-2, 10],
		effect: "fade",
		opacity: 0.7,
		tipClass: 'ccf-tooltip'
	
	});
	
	$("#ccf-form-success").delay(500).fadeIn('slow');
	$("#ccf-form-success .close").click(function() {
		$("#ccf-form-success").fadeOut();											  
	});
	
});