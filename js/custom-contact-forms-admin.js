$j(document).ready(function(){
							
	$j('.form-extra-options').hide();
	$j('.form-options-expand').prepend('<input type="button" class="form-options-expand-link" value="' + more_options + '" />');
	$j('.form-options-expand-link').click(function() {
		$j(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".form-extra-options:first")
			.toggle();
	});
	
	$j('.submission-content').hide();
	$j('.submission-content-expand').prepend('<input type="button" class="submission-content-expand-button" value="' + expand + '" />');
	$j('.submission-content-expand-button').click(function() {
		$j(this)
		.parent()
		.parent()
		.parent()
		.parent()
		.next()
		.toggle();
	});
	
	$j('.fixed-fields-extra-options').hide();
	$j('.fixed-fields-options-expand').prepend('<input type="button" class="fixed-fields-options-expand-link" value="' + more_options + '" />');
	$j('.fixed-fields-options-expand-link').click(function() {
		$j(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".fixed-fields-extra-options:first")
			.toggle();
	});
	
	$j('.fields-extra-options').hide();
	$j('.fields-options-expand').prepend('<input type="button" class="fields-options-expand-link" value="' + more_options + '" />');
	$j('.fields-options-expand-link').click(function() {
		$j(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".fields-extra-options:first")
			.toggle();
	});
	$j('.usage-popover-button').click(function() {
		showCCFUsagePopover();
	});
	$j("#ccf-usage-popover .close").click(function() {
		$j("#ccf-usage-popover").fadeOut();											  
	});
	
	$j("a[title].toollink").tooltip({
		position: "bottom left",
		offset: [-2, 10],
		effect: "fade",
		tipClass: 'ccf-tooltip',
		opacity: 1.0							
	});
});