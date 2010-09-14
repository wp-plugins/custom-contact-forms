function showCCFUsagePopover() {
	$j("#ccf-usage-popover").delay(500).fadeIn('slow');	
}

var $j = jQuery.noConflict();
$j(document).ready(function(){
	$j('.form-extra-options').hide();
	$j('.form-options-expand').prepend('<input type="button" class="form-options-expand-link" value="More Options" />');
	$j('.form-options-expand-link').click(function() {
		$j(this)
			.parent()
			.parent()
			.parent()
			.next()
			.find(".form-extra-options:first")
			.toggle();
	});
	
	$j('.fixed-fields-extra-options').hide();
	$j('.fixed-fields-options-expand').prepend('<input type="button" class="fixed-fields-options-expand-link" value="More Options" />');
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
	$j('.fields-options-expand').prepend('<input type="button" class="fields-options-expand-link" value="More Options" />');
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
});