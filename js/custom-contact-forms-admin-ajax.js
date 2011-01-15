jQuery.preloadImages(ccf_plugin_dir + "/images/wpspin_light.gif"); // preload loading image
jQuery(document).ready(function() {
	jQuery(".delete-button").each(function(){
		var name = jQuery(this).attr('name');
		var value = jQuery(this).attr('value');
		var html = '<input class="delete-button" type="button" name="'+name+'" value="'+value+'" />';
		jQuery(this).after(html).remove(); // add new, then remove original input
	});
	jQuery(".delete-button").live("click", function(event) {
		var object_id = jQuery(this).parents().find(".object-id").attr("value");
		var object_type = jQuery(this).parents().find(".object-type").attr("value");
		var parent_row = jQuery(this).parents("tr:eq(0)");
		var modal = fx.initModal();
		jQuery("<a>")
			.attr("href", "#")
			.addClass("modal-close-btn")
			.html("&times")
			.click(function(event) { fx.boxOut(event); })
			.appendTo(modal);
		jQuery("<p>")
			.html(delete_confirm + " " + object_type.replace("_", " ") + "?")
			.appendTo(modal);
		jQuery("<input>").attr({
			type: "button",
			value: click_to_confirm
		}).addClass("delete-button-confirm").appendTo(modal);
		var loading_img = jQuery("<img>")
				.attr("src", ccf_plugin_dir + '/images/wpspin_light.gif')
				.addClass("modal-wpspin")
				.appendTo(modal)
				.hide();
		//var option_pattern = RegExp('<option value="' + object_id + '">.*?<\/option>', "i");
		jQuery(".delete-button-confirm").click(function() {
			loading_img.show();
			jQuery.ajax({
				type: "POST",
				url: ccf_file,
				data: "ajax_action=delete&object_id=" + object_id + "&object_type=" + object_type,
				success: function(data) {
					if (object_type == "form" || object_type == "field" || object_type == "form_submission")
						parent_row.next().remove();
					if (object_type == "style") {
						/* delete occurences of this option within style dropdowns. */
						var style_inputs = jQuery(".form_style_input");
						style_inputs.each(function() {
							this_option = jQuery(this).find("option[value=" + object_id + "]");
							if (this_option.attr("selected") == "selected")
								jQuery(this).find("option[value=0]").attr("selected", "selected");
							this_option.remove();
						});
					} else if (object_type == "field" || object_type == "field_option") {
						/* delete occurences of this option within field and field option attach dropdowns. */
						var fields_options_input = jQuery("select[name=attach_object_id], select[name=dettach_object_id]");
						fields_options_input.each(function () {
							this_option = jQuery(this).find("option[value=" + object_id + "]");
							this_option.remove();
						});
					}
					parent_row.remove();
				},
				error: function() { modal.append(error); },
				complete: function() { modal.remove(); }
			});
		});
	});
	jQuery(".edit-button").each(function(){
		var name = jQuery(this).attr('name');
		var value = jQuery(this).attr('value');
		var html = '<input class="edit-button" type="button" name="'+name+'" value="'+value+'" />';
		jQuery(this).after(html).remove(); // add new, then remove original input
	});
	jQuery(".edit-button").live("click", function(event) {
		save_box = fx.initSaveBox("Saving");
		var object_id = jQuery(this).parents().find(".object-id").attr("value");
		var object_type = jQuery(this).parents().find(".object-type").attr("value");
		var values = "object_id=" + object_id + "&object_type=" + object_type + "&ajax_action=edit";
		var object_rows = jQuery(this).parents("tr:eq(0)");
		if (object_type == "form" || object_type == "field")
			object_rows = object_rows.add(object_rows.next());
		object_rows.find("input, select, textarea").each(function() {
			if (jQuery(this).attr("name").match(/\[/) && jQuery(this).attr("type") != "submit" && jQuery(this).attr("type") != "button") {
				key = jQuery(this).attr("name");;
				values = values + "&" + key + "=" + jQuery(this).attr("value");
			}
		});
		jQuery.ajax({
			type: "POST",
			url: ccf_file,
			data: values,
			//success: function(data) {
				//jQuery(".save-box").fadeOut().remove();
			//},
			error: function() { alert(error); },
			complete: function() { jQuery(".save-box").fadeOut().remove(); }
		});
	});
		
	jQuery(".attach-button").each(function(){
		var name = jQuery(this).attr('name');
		var value = jQuery(this).attr('value');
		var html = '<input class="attach-button" type="button" name="' + name + '" value="' + value + '" />';
		jQuery(this).after(html).remove(); // add new, then remove original input
	});
	jQuery(".attach-button").live("click", function() {
		var object_type = jQuery(this).parents().find(".object-type").attr("value");
		var attach_object_field = jQuery(this).parents().find("select[name=attach_object_id]:first");
		var object_id = attach_object_field.attr("class").split(' ')[0].replace(/[^0-9]*([0-9]*)/, "$1");
		var dettach_object_field = jQuery(this).parents().find("select[name=dettach_object_id]:first");
		var attach_object_id = attach_object_field.attr("value");
		var attach_object_slug = attach_object_field.find("option[value=" + attach_object_id + "]:eq(0)").first().text();
		pattern = new RegExp('<option value="' + attach_object_id + '">', "i");
		str = dettach_object_field.html();
		if (!str.match(pattern)) {
			var save_box = fx.initSaveBox(attaching);
			jQuery.ajax({
				type: "POST",
				url: ccf_file,
				data: "ajax_action=attach&attach_object_id=" + attach_object_id + "&attach_to=" + object_id + "&object_type=" + object_type,
				success: function(data) {
					//debug = fx.initDebugWindow();
					//jQuery("<div></div>").html(data).appendTo(debug);
					new_option = jQuery("<option></option>").attr("value", attach_object_id).text(attach_object_slug); 
					dettach_object_field.append(new_option);
					dettach_object_field.find('option[value=-1]').remove();
					
				},
				error: function() { alert(error); },
				complete: function() { jQuery(".save-box").fadeOut().remove(); }
			});
		}
	});
	
	jQuery(".dettach-button").each(function(){
		var name = jQuery(this).attr('name');
		var value = jQuery(this).attr('value');
		var html = '<input class="dettach-button" type="button" name="' + name + '" value="' + value + '" />';
		jQuery(this).after(html).remove(); // add new, then remove original input
	});
	jQuery(".dettach-button").live("click", function() {
		var object_type = jQuery(this).parents().find(".object-type").attr("value");
		var dettach_object_field = jQuery(this).parents().find("select[name=dettach_object_id]:first");
		var object_id = dettach_object_field.attr("class").split(' ')[0].replace(/[^0-9]*([0-9]*)/, "$1");
		var dettach_object_id = dettach_object_field.attr("value");
		if (dettach_object_id != "-1") {
			var dettach_object_slug = dettach_object_field.find("option[value=" + dettach_object_id + "]:eq(0)").first().text();
			var save_box = fx.initSaveBox(dettaching);
			jQuery.ajax({
				type: "POST",
				url: ccf_file,
				data: "ajax_action=dettach&dettach_object_id=" + dettach_object_id + "&dettach_from=" + object_id + "&object_type=" + object_type,
				success: function(data) {
					//debug = fx.initDebugWindow();
					//jQuery("<div></div>").html(data).appendTo(debug);
					pattern = new RegExp('<option value="' + dettach_object_id + '">.*?<\/option>', "i");
					new_options = dettach_object_field.html().replace(pattern, '');
					if (!new_options.match(/<\/option>/)) new_options = '<option value="-1">Nothing Attached!</option>';
					dettach_object_field.html(new_options);
				},
				error: function() { alert(error); },
				complete: function() { jQuery(".save-box").fadeOut().remove(); }
			});
		}
	});
	/*
	jQuery(".create-button").each(function(){
		var name = jQuery(this).attr('name');
		var value = jQuery(this).attr('value');
		var html = '<input class="create-button" type="button" name="'+name+'" value="'+value+'" />';
		jQuery(this).after(html).remove(); // add new, then remove original input
	});
	jQuery(".create-button").live("click", function(event) {
		var icon = fx.getLoadingIcon().insertBefore(this);
		var object_type = jQuery(this).parents().find("input[name=object_type]").attr("value");
		if (object_type == "field_option") {
			var values = "ajax_action=create_field_option";
			var value_array = [];
			var new_row = jQuery('#edit-field-options tr:first').clone(true).addClass("new-object").hide().insertBefore('#edit-field-options tr:first');
			jQuery(this).parents("form:eq(0)").find("input").each(function() {
				if (jQuery(this).attr("name").match(/^option\[/)) {
					values = values + "&" + jQuery(this).attr("name") + "=" + jQuery(this).attr("value");
					key = jQuery(this).attr("name").replace(/^option\[(.*)\]$/, "$1");
					value_array[key] = jQuery(this).attr("value");
					jQuery(".new-object input[name=\"option[" + key + "]\"]").attr("value", value_array[key]);
				}
			});
			// Try to get first row of field options table to use its html as a framework for new row
		}
		//new_row = jQuery("#edit-field-options tr:first").html().appendTo("body").hide();
	
		// Create generic class of objects/functions to add delete/edit/insert capabilities on certain tables
		jQuery.ajax({
			type: "POST",
			url: ccf_file,
			data: values,
			success: function(data) {
				
			},
			error: function() {
					alert("An error has occured, please try again later.");
			}
		});
		new_row.fadeIn("slow").removeClass("new-object");
		jQuery(".ccf-loading-icon").remove();
	});*/
});