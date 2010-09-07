<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsDB')) {
	class CustomContactFormsDB {
		var $forms_table;
		var $fields_table;
		var $styles_table;
		var $field_options_table;
		function CustomContactFormsDB() {
			global $wpdb;
			$table_prefix = $wpdb->prefix;
			$this->forms_table = $table_prefix . 'customcontactforms_forms';
			$this->fields_table = $table_prefix . 'customcontactforms_fields';
			$this->styles_table = $table_prefix . 'customcontactforms_styles';
			$this->field_options_table = $table_prefix . 'customcontactforms_field_options';
		}
		
		function encodeOption($option) {
			return htmlspecialchars(stripslashes($option), ENT_QUOTES);
		}
		
		function decodeOption($option, $strip_slashes = 1, $decode_html_chars = 1) {
			if ($strip_slashes == 1) $option = stripslashes($option);
			if ($decode_html_chars == 1) $option = html_entity_decode($option);
			return $option;
		}
		
		function createTables() {
			global $wpdb;
			if(!$this->formsTableExists()) {
				$sql1 = " CREATE TABLE `".$this->forms_table."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`form_slug` VARCHAR( 100 ) NOT NULL ,
						`form_title` VARCHAR( 200 ) NOT NULL ,
						`form_action` TEXT NOT NULL ,
						`form_method` VARCHAR( 4 ) NOT NULL ,
						`form_fields` VARCHAR( 200 ) NOT NULL ,
						`submit_button_text` VARCHAR( 200 ) NOT NULL ,
						`custom_code` TEXT NOT NULL ,
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql1);
			} if(!$this->fieldOptionsTableExists()) {
				$sql5 = " CREATE TABLE `".$this->field_options_table."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`option_slug` VARCHAR( 100 ) NOT NULL ,
						`option_label` VARCHAR( 200 ) NOT NULL ,
						`option_value` VARCHAR( 100 ) NOT NULL ,
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql5);
			} if(!$this->fieldsTableExists()) {
				$sql2 = "CREATE TABLE `".$this->fields_table."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`field_slug` VARCHAR( 50 ) NOT NULL ,
						`field_label` VARCHAR( 100 ) NOT NULL ,
						`field_type` VARCHAR( 25 ) NOT NULL ,
						`field_value` TEXT NOT NULL ,
						`field_maxlength` INT ( 5 )  NOT NULL DEFAULT '0',
						`user_field` INT ( 1 )  NOT NULL DEFAULT '1',
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql2);
			} if(!$this->stylesTableExists()) {
				// Title, input, textarea, label, form, submit
				$sql3 = "CREATE TABLE `".$this->styles_table."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`style_slug` VARCHAR( 30 ) NOT NULL ,
						`input_width` VARCHAR( 10 ) NOT NULL DEFAULT '200px',
						`textarea_width` VARCHAR( 10 ) NOT NULL DEFAULT '200px',
						`textarea_height` VARCHAR( 10 ) NOT NULL DEFAULT '100px',
						`form_borderwidth` VARCHAR( 10 ) NOT NULL DEFAULT '0px',
						`label_width` VARCHAR( 10 ) NOT NULL DEFAULT '110px',
						`form_width` VARCHAR( 10 ) NOT NULL DEFAULT '500px',
						`submit_width` VARCHAR( 10 ) NOT NULL DEFAULT '80px',
						`submit_height` VARCHAR( 10 ) NOT NULL DEFAULT '35px',
						`label_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1em',
						`title_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1.2em',
						`field_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1em',
						`submit_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1.1em',
						`field_bordercolor` VARCHAR( 10 ) NOT NULL DEFAULT 'blue',
						`form_borderstyle` VARCHAR( 30 ) NOT NULL DEFAULT 'dashed',
						`form_bordercolor` VARCHAR( 20 ) NOT NULL DEFAULT 'black',
						`field_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#000000',
						`label_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#333333',
						`title_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#333333',
						`submit_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#333333',
						`form_fontfamily` VARCHAR( 150 ) NOT NULL DEFAULT 'Verdana, Tahoma, Arial',
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql3);
			}
			return true;
		}
		
		function formatStyle($style) {
			return str_replace('#', '', str_replace(';', '', $style));
		}
		
		function updateTables() {
			global $wpdb;
			$wpdb->show_errors();
			if (!$this->columnExists('user_field', $this->fields_table))
				$wpdb->query("ALTER TABLE `" . $this->fields_table . "` ADD `user_field` INT( 1 ) NOT NULL DEFAULT '1'");
			if (!$this->columnExists('form_style', $this->forms_table))
				$wpdb->query("ALTER TABLE `" . $this->forms_table . "` ADD `form_style` INT( 10 ) NOT NULL DEFAULT '0'");
			if (!$this->columnExists('form_email', $this->forms_table))
				$wpdb->query("ALTER TABLE `" . $this->forms_table . "` ADD `form_email` VARCHAR( 50 ) NOT NULL");
			if (!$this->columnExists('form_success_message', $this->forms_table))
				$wpdb->query("ALTER TABLE `" . $this->forms_table . "` ADD `form_success_message` TEXT NOT NULL");
			if (!$this->columnExists('form_thank_you_page', $this->forms_table))
				$wpdb->query("ALTER TABLE `" . $this->forms_table . "` ADD `form_thank_you_page` VARCHAR ( 200 ) NOT NULL");
			if (!$this->columnExists('field_backgroundcolor', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `field_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#efefef'");
			if (!$this->columnExists('field_borderstyle', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `field_borderstyle` VARCHAR( 20 ) NOT NULL DEFAULT 'solid'");
			if (!$this->columnExists('form_success_title', $this->forms_table))
				$wpdb->query("ALTER TABLE `" . $this->forms_table . "` ADD `form_success_title` VARCHAR( 150 ) NOT NULL DEFAULT 'Form Success!'");
			if (!$this->columnExists('form_padding', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `form_padding` VARCHAR( 20 ) NOT NULL DEFAULT '4px'");
			if (!$this->columnExists('form_margin', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `form_margin` VARCHAR( 20 ) NOT NULL DEFAULT '4px'");
			if (!$this->columnExists('title_margin', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `title_margin` VARCHAR( 20 ) NOT NULL DEFAULT '2px'");
			if (!$this->columnExists('label_margin', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `label_margin` VARCHAR( 20 ) NOT NULL DEFAULT '3px'");
			if (!$this->columnExists('textarea_backgroundcolor', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `textarea_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#efefef'");
			if (!$this->columnExists('success_popover_bordercolor', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `success_popover_bordercolor` VARCHAR( 20 ) NOT NULL DEFAULT '#efefef'");
			if (!$this->columnExists('dropdown_width', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `dropdown_width` VARCHAR( 20 ) NOT NULL DEFAULT 'auto'");
			if (!$this->columnExists('success_popover_fontsize', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `success_popover_fontsize` VARCHAR( 20 ) NOT NULL DEFAULT '12px'");
			if (!$this->columnExists('success_popover_title_fontsize', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `success_popover_title_fontsize` VARCHAR( 20 ) NOT NULL DEFAULT '1.3em'");
			if (!$this->columnExists('success_popover_height', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `success_popover_height` VARCHAR( 20 ) NOT NULL DEFAULT '200px'");
			if (!$this->columnExists('success_popover_fontcolor', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `success_popover_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#333333'");
			if (!$this->columnExists('success_popover_title_fontcolor', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `success_popover_title_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#333333'");
			if (!$this->columnExists('field_instructions', $this->fields_table))
				$wpdb->query("ALTER TABLE `" . $this->fields_table . "` ADD `field_instructions` TEXT NOT NULL");
			if (!$this->columnExists('field_options', $this->fields_table))
				$wpdb->query("ALTER TABLE `" . $this->fields_table . "` ADD `field_options` VARCHAR( 300 ) NOT NULL");
			if (!$this->columnExists('field_required', $this->fields_table))
				$wpdb->query("ALTER TABLE `" . $this->fields_table . "` ADD `field_required` INT( 1 ) NOT NULL DEFAULT '0'");
			$wpdb->show_errors();
			$wpdb->print_error();
		}
		
		function insertFixedFields() {
			$captcha = array('field_slug' => 'captcha', 'field_label' => 'Type the numbers.', 'field_type' => 'Text', 'field_value' => '', 'field_maxlength' => '100', 'user_field' => 0, 'field_instructions' => 'Type the numbers displayed in the image above.');
			$ishuman = array('field_slug' => 'ishuman', 'field_label' => 'Check if you are human.', 'field_type' => 'Checkbox', 'field_value' => '1', 'field_maxlength' => '0', 'user_field' => 0, 'field_instructions' => 'This helps us prevent spam.');
			$fixedEmail = array('field_slug' => 'fixedEmail', 'field_label' => 'Your Email', 'field_type' => 'Text', 'field_value' => '', 'field_maxlength' => '100', 'user_field' => 0, 'field_instructions' => 'Please enter your email address.');
			if (!$this->fieldSlugExists('captcha'))
				$this->insertField($captcha);
			if (!$this->fieldSlugExists('ishuman'))
				$this->insertField($ishuman);
			if (!$this->fieldSlugExists('fixedEmail'))
				$this->insertField($fixedEmail);
		
		}
		
		function columnExists($column, $table) {
			global $wpdb;
			$tests = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.columns WHERE table_name = '$table' AND column_name = '$column' LIMIT 0 , 30");
			return (!empty($test[0]) && $test[0]->COLUMN_NAME == $column);
		}
		
		function insertForm($form) {
			global $wpdb;
			if (empty($form) or empty($form[form_slug]) or $this->formSlugExists($this->formatSlug($form[form_slug]))) return false;
			$form[form_slug] = $this->formatSlug($form[form_slug]);
			$form = array_map(array(&$this, 'encodeOption'), $form);
			$wpdb->insert($this->forms_table, $form);
			return $wpdb->insert_id;
		}
		
		function insertField($field) {
			global $wpdb;
			if (empty($field) or empty($field[field_slug]) or $this->fieldSlugExists($this->formatSlug($field[field_slug]))) return false;
			$field[field_slug] = $this->formatSlug($field[field_slug]);
			foreach ($field as $key => $value) {
				if ($key != 'field_slug')
					$field[$key] = $this->encodeOption($value);
			}
			$wpdb->insert($this->fields_table, $field);
			return $wpdb->insert_id;
		}
		
		function insertFieldOption($option) {
			global $wpdb;
			if (empty($option) or empty($option[option_slug]) or $this->fieldOptionsSlugExists($this->formatSlug($option[option_slug]))) return false;
			$option[option_slug] = $this->formatSlug($option[option_slug]);
			$option = array_map(array(&$this, 'encodeOption'), $option);
			$wpdb->insert($this->field_options_table, $option);
			return $wpdb->insert_id;
		}
		
		function insertStyle($style) {
			global $wpdb;
			$wpdb->show_errors();
			if (empty($style) or empty($style[style_slug]) or $this->styleSlugExists($this->formatSlug($style[style_slug]))) return false;
			$style[style_slug] = $this->formatSlug($style[style_slug]);
			foreach ($style as $key => $value) {
				if ($key != 'style_slug')
					$style[$key] = $this->formatStyle($this->encodeOption($value));
			}
			$wpdb->insert($this->styles_table, $style);
			$wpdb->print_error();
			return $wpdb->insert_id;
		}
		
		
		function fieldsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". $this->fields_table . "'") == $this->fields_table);
		}
		
		function formsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". $this->forms_table . "'") == $this->forms_table);
		}
		
		function stylesTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". $this->styles_table . "'") == $this->styles_table);
		}
		
		function fieldOptionsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". $this->field_options_table . "'") == $this->field_options_table);
		}
		
		function updateForm($form, $fid) {
			global $wpdb;
			if (!empty($form[form_slug])) {
				$test = $this->selectForm('', $this->formatSlug($form[form_slug]));
				if (!empty($test) and $test->id != $fid) return false;
				$form[form_slug] = $this->formatSlug($form[form_slug]);
			}
			$form = array_map(array(&$this, 'encodeOption'), $form);
			$wpdb->update($this->forms_table, $form, array('id' => $fid));
			return true;
		}
		
		function updateField($field, $fid) {
			global $wpdb;
			if (!empty($field[field_slug])) {
				$test = $this->selectField('', $this->formatSlug($field[field_slug]));
				if (!empty($test) and $test->id != $fid)
					return false;
				$field[field_slug] = $this->formatSlug($field[field_slug]);
			}
			$field = array_map(array(&$this, 'encodeOption'), $field);
			$wpdb->update($this->fields_table, $field, array('id' => $fid));
			return true;
		}
		
		function updateFieldOption($option, $oid) {
			global $wpdb;
			if (!empty($option[option_slug])) {
				$test = $this->selectFieldOption('', $this->formatSlug($option[option_slug]));
				if (!empty($test) and $test->id != $oid)
					return false;
				$option[option_slug] = $this->formatSlug($option[option_slug]);
			}
			$option = array_map(array(&$this, 'encodeOption'), $option);
			$wpdb->update($this->field_options_table, $option, array('id' => $oid));
			return true;
		}
		
		function updateStyle($style, $sid) {
			global $wpdb;
			if (empty($style[style_slug])) return false;
			$test = $this->selectStyle('', $this->formatSlug($style[style_slug]));
			if (!empty($test) and $test->id != $sid) // if style_slug is different then make sure it is unique
				return false;
			$style[style_slug] = $this->formatSlug($style[style_slug]);
			foreach ($style as $key => $value) {
				if ($key != 'style_slug')
					$style[$key] = $this->formatStyle($this->encodeOption($value));
			}
			$wpdb->update($this->styles_table, $style, array('id' => $sid));
			return true;
		}
		
		function deleteForm($fid, $slug = NULL) {
			global $wpdb;
			$where_params = ($slug == NULL) ? "id='$fid'" : "form_slug='$slug'";
			$wpdb->query("DELETE FROM " . $this->forms_table . ' WHERE ' . $where_params);
			return true;
		}
		
		function deleteField($fid, $slug = NULL) {
			global $wpdb;
			$this->dettachFieldAll($fid);
			$where_params = ($slug == NULL) ? "id='$fid'" : "field_slug='$slug'";
			$wpdb->query("DELETE FROM " . $this->fields_table . ' WHERE ' . $where_params);
			return false;
		}
		
		function deleteStyle($sid, $slug = NULL) {
			global $wpdb;
			$this->dettachStyleAll($sid);
			$where_params = ($slug == NULL) ? "id='$sid'" : "style_slug='$slug'";
			$wpdb->query("DELETE FROM " . $this->styles_table . ' WHERE ' . $where_params);
			return true;
		}
		
		function deleteFieldOption($oid, $slug = NULL) {
			global $wpdb;
			$this->dettachFieldOptionAll($oid);
			$where_params = ($slug == NULL) ? "id='$oid'" : "option_slug='$slug'";
			$wpdb->query("DELETE FROM " . $this->field_options_table . ' WHERE ' . $where_params);
			return true;
		}
		
		function selectAllForms() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->forms_table . " ORDER BY form_slug ASC");	
		}
		
		function selectAllFields() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->fields_table . " ORDER BY field_slug ASC");	
		}
		
		function selectAllFieldOptions() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->field_options_table . " ORDER BY option_slug ASC");	
		}
		
		function selectAllStyles() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->styles_table . " ORDER BY style_slug ASC");	
		}
		
		function selectForm($fid, $form_slug = '') {
			global $wpdb;
			$extra = (!empty($form_slug)) ? " or form_slug = '$form_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->forms_table . " WHERE id='$fid' $extra");
		}
		
		function selectStyle($sid, $style_slug = '') {
			global $wpdb;
			$extra = (!empty($style_slug)) ? " or style_slug = '$style_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->styles_table . " WHERE id='$sid' $extra");
		}
		
		function selectField($fid, $field_slug = '') {
			global $wpdb;
			$extra = (!empty($field_slug)) ? " or field_slug = '$field_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->fields_table . " WHERE id='$fid'" . $extra);
		}
		
		function selectFieldOption($oid, $option_slug = '') {
			global $wpdb;
			$extra = (!empty($option_slug)) ? " or option_slug = '$option_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->field_options_table . " WHERE id='$oid'" . $extra);
		}
		
		function addFieldToForm($field_id, $form_id) {
			$form = $this->selectForm($form_id, '');
			$fields = $this->getAttachedFieldsArray($form_id);
			if (!in_array($field_id, $fields)) {
				$new_fields = $form->form_fields . $field_id . ',';
				$this->updateForm(array('form_fields' => $new_fields), $form_id);
				return true;
			}
			return false;
		}
		
		function addFieldOptionToField($option_id, $field_id) {
			$field = $this->selectField($field_id);
			$options = $this->getAttachedFieldOptionsArray($field_id);
			if (!in_array($option_id, $options)) {
				$new_options = $field->field_options . $option_id . ',';
				$this->updateField(array('field_options' => $new_options), $field_id);
				return true;
			}
			return false;
		}
		
		function getAttachedFieldsArray($form_id) {
			$form = $this->selectForm($form_id, '');
			$out = explode(',', $form->form_fields);
			if (!empty($out)) array_pop($out);
			return $out;
		}
		
		function getAttachedFieldOptionsArray($field_id) {
			$field = $this->selectField($field_id);
			$out = explode(',', $field->field_options);
			if (!empty($out)) array_pop($out);
			return $out;
		}
		
		function dettachField($field_id, $form_id) {
			$fields = $this->getAttachedFieldsArray($form_id);
			if (!empty($fields) && in_array($field_id, $fields)) {
				$form = $this->selectForm($form_id);
				$new_fields = str_replace($field_id . ',', '', $form->form_fields);
				$this->updateForm(array('form_fields' => $new_fields), $form_id);
				return true;
			}
			return false;
		}
		
		function dettachFieldAll($field_id) {
			$forms = $this->selectAllForms();
			foreach ($forms as $form)
				$this->dettachField($field_id, $form->id);
		}
		
		function dettachFieldOptionAll($option_id) {
			$fields = $this->selectAllFields();
			foreach ($fields as $field)
				$this->dettachFieldOption($option_id, $field->id);
		}
		
		function dettachFieldOption($option_id, $field_id) {
			$options = $this->getAttachedFieldOptionsArray($field_id);
			if (!empty($options) && in_array($option_id, $options)) {
				$field = $this->selectField($field_id);
				$new_options = str_replace($option_id . ',', '', $field->field_options);
				$this->updateField(array('field_options' => $new_options), $field_id);
				return true;
			}
			return false;
		}
		
		function dettachStyleAll($style_id) {
			$forms = $this->selectAllForms();
			foreach ($forms as $form) {
				if ($form->form_style == $style_id) {
					$this->updateForm(array('form_style' => 0), $form->id);
				}
			}
		}
		
		function formatSlug($slug) {
			$slug = preg_replace('/[^a-z_ A-Z0-9\s]/', '', $slug);
			return str_replace(' ', '_', $slug);	
		}
		
		function fieldSlugExists($slug) {
			$test = $this->selectField('', $slug);
			return (!empty($test));
		}
		
		function styleSlugExists($slug) {
			$test = $this->selectStyle('', $slug);
			return (!empty($test));
		}
		
		function formSlugExists($slug) {
			$test = $this->selectForm('', $slug);
			return (!empty($test));
		}
		
		function fieldOptionsSlugExists($slug) {
			$test = $this->selectFieldOption('', $slug);
			return (!empty($test));
		}
		
		function insertDefaultContent($overwrite = false) {
			$field_slugs = array('name' => 'ccf_name', 'message' => 'ccf_message',
			'website' => 'ccf_website', 'phone' => 'ccf_phone', 'google' => 'ccf_google',
			'contact_method' => 'ccf_contact_method');
			$option_slugs = array('email' => 'ccf_email', 'phone' => 'ccf_phone', 'nocontact' => 'ccf_no_contact');
			$form_slugs = array('contact_form' => 'ccf_contact_form');
			if ($overwrite) {
				foreach($field_slugs as $slug) $this->deleteField(0, $slug);
				foreach($option_slugs as $slug) $this->deleteFieldOption(0, $slug);
				foreach($form_slugs as $slug) $this->deleteForm(0, $slug);
			}
			$name_field = array('field_slug' => $field_slugs[name], 'field_label' => 'Your Name:',
			'field_required' => 1, 'field_instructions' => 'Please enter your full name.',
			'field_maxlength' => '100', 'field_type' => 'Text');
			$message_field = array('field_slug' => $field_slugs[message], 'field_label' => 'Your Message:',
			'field_required' => 0, 'field_instructions' => 'Enter any message or comment.',
			'field_maxlength' => 0, 'field_type' => 'Textarea');
			$website_field = array('field_slug' => $field_slugs[website], 'field_label' => 'Your Website:',
			'field_required' => 0, 'field_instructions' => 'If you have a website, please enter it here.',
			'field_maxlength' => 200, 'field_type' => 'Text');
			$phone_field = array('field_slug' => $field_slugs[phone], 'field_label' => 'Your Phone Number:',
			'field_required' => 0, 'field_instructions' => 'Please enter your phone number.',
			'field_maxlength' => 30, 'field_type' => 'Text');
			$google_field = array('field_slug' => $field_slugs[google], 'field_label' => 'Did you find my website through Google?',
			'field_required' => 0, 'field_instructions' => 'If you found my website through Google, check this box.',
			'field_maxlength' => 0, 'field_type' => 'Checkbox');
			$contact_method_field = array('field_slug' => $field_slugs[contact_method], 'field_label' => 'How should we contact you?',
			'field_required' => 1, 'field_instructions' => 'By which method we should contact you?',
			'field_maxlength' => 0, 'field_type' => 'Dropdown');
			$email_field = $this->selectField(0, 'fixedEmail');
			$captcha_field = $this->selectField(0, 'captcha');
			$email_option = array('option_slug' => $option_slugs[email], 'option_label' => 'By Email');
			$phone_option = array('option_slug' => $option_slugs[phone], 'option_label' => 'By Phone');
			$nocontact_option = array('option_slug' => $option_slugs[nocontact], 'option_label' => 'Do Not Contact Me');
			$contact_form = array('form_slug' => $form_slugs[contact_form], 'form_title' => 'Contact Form', 'form_method' => 'Post',
			'submit_button_text' => 'Send Message', 'form_email' => get_option('admin_email'), 'form_success_message' => 'Thank you for filling out our contact form. We will contact you very soon by the way you specified.',
			'form_success_title' => 'Thank You!', 'form_style' => 0);
			$name_field_id = $this->insertField($name_field);
			$message_field_id = $this->insertField($message_field);
			$website_field_id = $this->insertField($website_field);
			$phone_field_id = $this->insertField($phone_field);
			$google_field_id = $this->insertField($google_field);
			$contact_method_field_id = $this->insertField($contact_method_field);
			$email_option_id = $this->insertFieldOption($email_option);
			$phone_option_id = $this->insertFieldOption($phone_option);
			$nocontact_option_id = $this->insertFieldOption($nocontact_option);
			$contact_form_id = $this->insertForm($contact_form);
			$this->addFieldOptionToField($email_option_id, $contact_method_field_id);
			$this->addFieldOptionToField($phone_option_id, $contact_method_field_id);
			$this->addFieldOptionToField($nocontact_option_id, $contact_method_field_id);
			$this->addFieldToForm($name_field_id, $contact_form_id);
			$this->addFieldToForm($website_field_id, $contact_form_id);
			$this->addFieldToForm($email_field->id, $contact_form_id);
			$this->addFieldToForm($phone_field_id, $contact_form_id);
			$this->addFieldToForm($google_field_id, $contact_form_id);
			$this->addFieldToForm($contact_method_field_id, $contact_form_id);
			$this->addFieldToForm($message_field_id, $contact_form_id);
			$this->addFieldToForm($captcha_field->id, $contact_form_id);
		}
	}
}
?>