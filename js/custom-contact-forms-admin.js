jQuery(document).ready(function(){
							
	jQuery('.form-extra-options').hide();
	jQuery('.form-options-expand').prepend('<input type="button" class="form-options-expand-link" value="' + more_options + '" />');
	jQuery('.form-options-expand-link').click(function() {
		jQuery(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".form-extra-options:first")
			.toggle();
	});
	
	jQuery('.submission-content').hide();
	jQuery('.submission-content-expand').prepend('<input type="button" class="submission-content-expand-button" value="' + expand + '" />');
	jQuery('.submission-content-expand-button').click(function() {
		jQuery(this)
		.parent()
		.parent()
		.parent()
		.parent()
		.next()
		.toggle();
	});
	
	jQuery('.fixed-fields-extra-options').hide();
	jQuery('.fixed-fields-options-expand').prepend('<input type="button" class="fixed-fields-options-expand-link" value="' + more_options + '" />');
	jQuery('.fixed-fields-options-expand-link').click(function() {
		jQuery(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".fixed-fields-extra-options:first")
			.toggle();
	});
	
	jQuery('.fields-extra-options').hide();
	jQuery('.fields-options-expand').prepend('<input type="button" class="fields-options-expand-link" value="' + more_options + '" />');
	jQuery('.fields-options-expand-link').click(function() {
		jQuery(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".fields-extra-options:first")
			.toggle();
	});
	jQuery('.usage-popover-button').click(function() {
		showCCFUsagePopover();
	});
	jQuery("#ccf-usage-popover .close").click(function() {
		jQuery("#ccf-usage-popover").fadeOut();											  
	});
	
	jQuery("a[title].toollink").tooltip({
		position: "bottom left",
		offset: [-2, 10],
		effect: "fade",
		tipClass: 'ccf-tooltip',
		opacity: 1.0							
	});
});