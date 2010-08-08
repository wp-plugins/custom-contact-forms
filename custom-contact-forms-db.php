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
		function CustomContactFormsDB() {
			global $wpdb;
			$this->forms_table = $wpdb->prefix . 'customcontactforms_forms';
			$this->fields_table = $wpdb->prefix . 'customcontactforms_fields';
			$this->styles_table = $wpdb->prefix . 'customcontactforms_styles';
			$this->createTables();
			$this->updateTables();
			$this->insertFixedFields();
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
			return str_replace(';', '', $style);
		}
		
		function updateTables() {
			global $wpdb;
			if (!$this->columnExists('user_field', $this->fields_table))
				$wpdb->query("ALTER TABLE `" . $this->fields_table . "` ADD `user_field` INT( 1 ) NOT NULL DEFAULT '1'");
			if (!$this->columnExists('form_style', $this->forms_table))
				$wpdb->query("ALTER TABLE `" . $this->forms_table . "` ADD `form_style` INT( 10 ) NOT NULL DEFAULT '0'");
			if (!$this->columnExists('field_backgroundcolor', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `field_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT '#efefef'");
			if (!$this->columnExists('field_borderstyle', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `field_borderstyle` VARCHAR( 20 ) NOT NULL DEFAULT 'solid'");
			if (!$this->columnExists('form_margin', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `form_margin` VARCHAR( 20 ) NOT NULL DEFAULT '6px'");
			if (!$this->columnExists('form_padding', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `form_padding` VARCHAR( 20 ) NOT NULL DEFAULT '4px'");
			if (!$this->columnExists('title_margin', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `title_margin` VARCHAR( 20 ) NOT NULL DEFAULT '2px'");
			if (!$this->columnExists('label_margin', $this->styles_table))
				$wpdb->query("ALTER TABLE `" . $this->styles_table . "` ADD `label_margin` VARCHAR( 20 ) NOT NULL DEFAULT '3px'");
		}
		
		function insertFixedFields() {
			if (!$this->fieldSlugExists('captcha'))
				$this->insertField('captcha', 'Type the numbers', 'Text', '', '100', 0);
			if (!$this->fieldSlugExists('ishuman'))
				$this->insertField('ishuman', 'Check if you are a human.', 'Checkbox', '1', '0', 0);
		}
		
		function columnExists($column, $table) {
			global $wpdb;
			$tests = $wpdb->get_results("SELECT * FROM INFORMATION_SCHEMA.columns WHERE table_name = '$table' AND column_name = '$column' LIMIT 0 , 30");
			return (!empty($test[0]) && $test[0]->COLUMN_NAME == $column);
		}
		
		function insertForm($form_slug, $form_title, $form_action, $form_method, $submit_button_text, $custom_code, $form_style) {
			global $wpdb;
			if ($this->formSlugExists($this->formatSlug($form_slug))) return false;
			$wpdb->insert($this->forms_table, array('form_slug' => $this->formatSlug($form_slug), 'form_title' => $this->encodeOption($form_title), 'form_action' => $this->encodeOption($form_action), 'form_method' => $form_method, 'submit_button_text' => $this->encodeOption($submit_button_text), 'form_style' => $form_style, 'custom_code' => $this->encodeOption($custom_code)));
			return true;
		}
		
		function insertField($field_slug, $field_label, $field_type, $field_value, $field_maxlength, $user_field) {
			global $wpdb;
			if ($this->fieldSlugExists($this->formatSlug($field_slug))) return false;
			$wpdb->insert($this->fields_table, array('field_slug' => $this->formatSlug($field_slug), 'field_label' => $this->encodeOption($field_label), 'field_type' => $field_type, 'field_value' => $this->encodeOption($field_value), 'field_maxlength' => $this->encodeOption($field_maxlength), 'user_field' => $user_field));
			return true;
		}
		
		function insertStyle($style) {
			global $wpdb;
			if (empty($style) or empty($style[style_slug]) or $this->styleSlugExists($this->formatSlug($style[style_slug]))) return false;
			$style[style_slug] = $this->formatSlug($style[style_slug]);
			foreach ($style as $key => $value) {
				if ($key != 'style_slug')
					$style[$key] = $this->formatStyle($this->encodeOption($value));
			}
			$wpdb->insert($this->styles_table, $style);
			return true;
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
		
		function updateForm($form_slug, $form_title, $form_action, $form_method, $submit_button_text, $custom_code, $form_style, $fid) {
			global $wpdb;
			if (empty($form_slug)) return false;
			$test = $this->selectForm('', $form_slug);
			if (!empty($test) and $test->id != $fid) // if form_slug is different then make sure it is unique
				return false;
			$wpdb->update($this->forms_table, array('form_slug' => $this->formatSlug($form_slug), 'form_title' => $this->encodeOption($form_title), 'form_action' => $this->encodeOption($form_action), 'form_method' => $form_method, 'form_style' => $form_style, 'submit_button_text' => $this->encodeOption($submit_button_text), 'custom_code' => $this->encodeOption($custom_code)), array('id' => $fid));
			return true;
		}
		
		function updateField($field_slug, $field_label, $field_type, $field_value, $field_maxlength, $fid) {
			global $wpdb;
			if (empty($field_slug)) return false;
			$test = $this->selectField('', $field_slug);
			if (!empty($test) and $test->id != $fid) // if field_slug is different then make sure it is unique
				return false;
			$wpdb->update($this->fields_table, array('field_slug' => $this->formatSlug($field_slug), 'field_label' => $this->encodeOption($field_label), 'field_type' => $field_type, 'field_value' => $this->encodeOption($field_value), 'field_maxlength' => $this->encodeOption($field_maxlength)), array('id' => $fid));
			return true;
		}
		
		function updateStyle($style) {
			global $wpdb;
			if (empty($style[style_slug])) return false;
			$test = $this->selectStyle('', $style[style_slug]);
			if (!empty($test) and $test->id != $style[id]) // if style_slug is different then make sure it is unique
				return false;
			$style[style_slug] = $this->formatSlug($style[style_slug]);
			foreach ($style as $key => $value) {
				if ($key != 'style_slug')
					$style[$key] = $this->formatStyle($this->encodeOption($value));
			}
			$wpdb->update($this->styles_table, $style, array('id' => $style[id]));
			return true;
		}
		
		function deleteForm($fid) {
			global $wpdb;
			$wpdb->query("DELETE FROM " . $this->forms_table . " WHERE id='$fid'");
			return true;
		}
		
		function deleteField($fid) {
			global $wpdb;
			$wpdb->query("DELETE FROM " . $this->fields_table . " WHERE id='$fid'");
			return true;
		}
		
		function deleteStyle($sid) {
			global $wpdb;
			$wpdb->query("DELETE FROM " . $this->styles_table . " WHERE id='$sid'");
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
		
		function selectAllStyles() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->styles_table . " ORDER BY style_slug ASC");	
		}
		
		function selectForm($fid, $form_slug) {
			global $wpdb;
			$extra = (!empty($form_slug)) ? " or form_slug = '$form_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->forms_table . " WHERE id='$fid' $extra");
		}
		
		function selectStyle($sid, $style_slug) {
			global $wpdb;
			$extra = (!empty($style_slug)) ? " or style_slug = '$style_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->styles_table . " WHERE id='$sid' $extra");
		}
		
		function selectField($fid, $field_slug) {
			global $wpdb;
			$extra = (!empty($field_slug)) ? " or field_slug = '$field_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->fields_table . " WHERE id='$fid'" . $extra);
		}
		
		function addFieldToForm($field_id, $form_id) {
			global $wpdb;
			$form = $this->selectForm($form_id, '');
			$fields = $this->getAttachedFieldsArray($form_id);
			if (!in_array($field_id, $fields)) {
				$newfields = $form->form_fields . $field_id . ',';
				$wpdb->update($this->forms_table, array('form_fields' => $newfields), array('id' => $form_id));
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
		
		function disattachField($field_id, $form_id) {
			global $wpdb;
			$fields = $this->getAttachedFieldsArray($form_id);
			if (!empty($fields) && in_array($field_id, $fields)) {
				$form = $this->selectForm($form_id, '');
				$newfields = str_replace($field_id . ',', '', $form->form_fields);
				$wpdb->update($this->forms_table, array('form_fields' => $newfields), array('id' => $form_id));
				return true;
			}
			return false;
		}
		
		function formatSlug($slug) {
			$slug = preg_replace('/[^a-zA-Z0-9\s]/', '', $slug);
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
	}
}
?>