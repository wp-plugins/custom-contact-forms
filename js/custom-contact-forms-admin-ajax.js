$j(document).ready(function() {
	$j(".delete-button").each(function(){
		var name = $j(this).attr('name');
		var value = $j(this).attr('value');
		var html = '<input class="delete-button" type="button" name="'+name+'" value="'+value+'" />';
		$j(this).after(html).remove(); // add new, then remove original input
	});
	$j(".delete-button").live("click", function(event) {
		var object_id = $j(this).parents().find(".object-id").attr("value");
		var object_type = $j(this).parents().find(".object-type").attr("value");
		var parent_row = $j(this).parents("tr:eq(0)");
		var modal = fx.initModal();
		$j("<a>")
			.attr("href", "#")
			.addClass("modal-close-btn")
			.html("&times")
			.click(function(event) { fx.boxOut(event); })
			.appendTo(modal);
		$j("<p>")
			.html("Are you sure you want to delete this " + object_type.replace("_", " ") + "?")
			.appendTo(modal);
		$j("<input>").attr({
			type: "button",
			value: "Click to Confirm"
		}).addClass("delete-button-confirm").appendTo(modal);
		$j(".delete-button-confirm").click(function() {
			$j.ajax({
				type: "POST",
				url: ccf_file,
				data: "ajax_action=delete&object_id=" + object_id + "&object_type=" + object_type,
				success: function(data) {
					if (object_type == "form" || object_type == "field" || object_type == "form_submission")
						parent_row.next().remove();
					parent_row.remove();
					modal.remove();
				},
				error: function() {
						modal.append("An error has occured, please try again later.");
				}
			});
		});
	});
	$j(".edit-button").each(function(){
		var name = $j(this).attr('name');
		var value = $j(this).attr('value');
		var html = '<input class="edit-button" type="button" name="'+name+'" value="'+value+'" />';
		$j(this).after(html).remove(); // add new, then remove original input
	});
	$j(".edit-button").live("click", function(event) {
		save_box = fx.initSaveBox("Saving");
		var object_id = $j(this).parents().find(".object-id").attr("value");
		var object_type = $j(this).parents().find(".object-type").attr("value");
		var values = "object_id=" + object_id + "&object_type=" + object_type + "&ajax_action=edit";
		var object_rows = $j(this).parents("tr:eq(0)");
		if (object_type == "form" || object_type == "field")
			object_rows = object_rows.add(object_rows.next());
		object_rows.find("input, select, textarea").each(function() {
			if ($j(this).attr("name").match(/\[/) && $j(this).attr("type") != "submit" && $j(this).attr("type") != "button") {
				key = $j(this).attr("name");;
				values = values + "&" + key + "=" + $j(this).attr("value");
			}
		});
		$j.ajax({
			type: "POST",
			url: ccf_file,
			data: values,
			success: function(data) {
				$j(".save-box").fadeOut().remove();
			},
			error: function() {
				alert("An error has occured, please try again later.");
			}
		});
	});
		
	$j(".attach-button").each(function(){
		var name = $j(this).attr('name');
		var value = $j(this).attr('value');
		var html = '<input class="attach-button" type="button" name="' + name + '" value="' + value + '" />';
		$j(this).after(html).remove(); // add new, then remove original input
	});
	$j(".attach-button").live("click", function() {
		var object_type = $j(this).parents().find(".object-type").attr("value");
		var attach_object_field = $j(this).parents().find("select[name=attach_object_id]:first");
		var object_id = attach_object_field.attr("class").replace(/[^0-9]*([0-9]*)/, "$1");
		var dettach_object_field = $j(this).parents().find("select[name=dettach_object_id]:first");
		var attach_object_id = attach_object_field.attr("value");
		var attach_object_slug = attach_object_field.find("option[value=" + attach_object_id + "]:eq(0)").first().text();
		pattern = new RegExp('<option value="' + attach_object_id + '">', "i");
		str = dettach_object_field.html();
		if (!str.match(pattern)) {
			var save_box = fx.initSaveBox("Attaching");
			$j.ajax({
				type: "POST",
				url: ccf_file,
				data: "ajax_action=attach&attach_object_id=" + attach_object_id + "&attach_to=" + object_id + "&object_type=" + object_type,
				success: function(data) {
					//debug = fx.initDebugWindow();
					//$j("<div></div>").html(data).appendTo(debug);
					new_option = $j("<option></option>").attr("value", attach_object_id).text(attach_object_slug); 
					dettach_object_field.append(new_option);
					dettach_object_field.find('option[value=-1]').remove();
					$j(".save-box").fadeOut().remove();
				},
				error: function() {
					alert("An error has occured, please try again later.");
				}
			});
		}
	});
	/*
	$j(".create-button").each(function(){
		var name = $j(this).attr('name');
		var value = $j(this).attr('value');
		var html = '<input class="create-button" type="button" name="'+name+'" value="'+value+'" />';
		$j(this).after(html).remove(); // add new, then remove original input
	});
	$j(".create-button").live("click", function(event) {
		var icon = fx.getLoadingIcon().insertBefore(this);
		var object_type = $j(this).parents().find("input[name=object_type]").attr("value");
		if (object_type == "field_option") {
			var values = "ajax_action=create_field_option";
			var value_array = [];
			var new_row = $j('#edit-field-options tr:first').clone(true).addClass("new-object").hide().insertBefore('#edit-field-options tr:first');
			$j(this).parents("form:eq(0)").find("input").each(function() {
				if ($j(this).attr("name").match(/^option\[/)) {
					values = values + "&" + $j(this).attr("name") + "=" + $j(this).attr("value");
					key = $j(this).attr("name").replace(/^option\[(.*)\]$/, "$1");
					value_array[key] = $j(this).attr("value");
					$j(".new-object input[name=\"option[" + key + "]\"]").attr("value", value_array[key]);
				}
			});
			// Try to get first row of field options table to use its html as a framework for new row
		}
		//new_row = $j("#edit-field-options tr:first").html().appendTo("body").hide();
	
		// Create generic class of objects/functions to add delete/edit/insert capabilities on certain tables
		$j.ajax({
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
		$j(".ccf-loading-icon").remove();
	});*/
});