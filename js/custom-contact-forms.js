// Custom Contact Forms plugin javascript

jQuery(document).ready(function(){
	jQuery('<a></a>')
		.addClass('ccf-popover-close')
		.html('[close]')
		.prependTo('.ccf-popover');
	jQuery('.ccf-popover').css({'padding' : '10px 14px 10px 10px'});
	jQuery("a#in").click(function(){
		var sel = ".ccf-popover" + cid;
		jQuery(".ccf-popover1").fadeIn();

	});
	jQuery(".ccf-popover-close").click(function(){
		jQuery(".ccf-popover").hide();
	});
	jQuery('.show-field-instructions').click(function() {
													  
	});
	
	jQuery(".ccf-tooltip-field").tooltip({
		position: "center right",
		offset: [-2, 10],
		effect: "fade",
		opacity: 0.7,
		tipClass: 'ccf-tooltip'
	
	});
	
	jQuery("#ccf-form-success").delay(500).fadeIn('slow');
	jQuery("#ccf-form-success .close").click(function() {
		jQuery("#ccf-form-success").fadeOut();											  
	});
	
});