<?php
/*
	Custom Contact Forms DB class is a parent to the Custom Contact Forms Class
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
	@version 1.0.0
*/
if (!class_exists('CustomContactFormsDB')) {
	class CustomContactFormsDB {
		var $forms_table;
		var $fields_table;
		
		function CustomContactFormsDB() {
			global $wpdb;
			$this->forms_table = $wpdb->prefix . 'customcontactforms_forms';
			$this->fields_table = $wpdb->prefix . 'customcontactforms_fields';
			$this->createTables();
		}
		
		function encodeOption($option) {
			return htmlspecialchars(stripslashes($option), ENT_QUOTES);
		}
		
		function decodeOption($option, $strip_slashes = 1, $decode_html_chars = 1) {
			if ($strip_slashes == 1) $option = stripslashes($option);
			if ($decode_html_chars == 1) $option = htmlspecialchars_decode($option);
			return $option;
		}
		
		function createTables() {
			global $wpdb;
			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
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
				dbDelta($sql1);
			} if(!$this->fieldsTableExists()) {
				$sql2 = "CREATE TABLE `".$this->fields_table."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`field_slug` VARCHAR( 50 ) NOT NULL ,
						`field_label` VARCHAR( 100 ) NOT NULL ,
						`field_type` VARCHAR( 25 ) NOT NULL ,
						`field_value` TEXT NOT NULL ,
						`field_maxlength` INT ( 5 )  NOT NULL DEFAULT '0',
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				dbDelta($sql2);
			}
			return true;
		}
		
		function insertForm($form_slug, $form_title, $form_action, $form_method, $submit_button_text, $custom_code) {
			global $wpdb;
			$test = $this->selectForm('', $form_slug);
			if (empty($test)) {
				$wpdb->insert($this->forms_table, array('form_slug' => $this->formatSlug($form_slug), 'form_title' => $this->encodeOption($form_title), 'form_action' => $this->encodeOption($form_action), 'form_method' => $form_method, 'submit_button_text' => $this->encodeOption($submit_button_text), 'custom_code' => $this->encodeOption($custom_code)));
				return true;
			}
			return false;
		}
		
		function insertField($field_slug, $field_label, $field_type, $field_value, $field_maxlength) {
			global $wpdb;
			$test = $this->selectField('', $field_slug);
			
			if (empty($test)) {
				$wpdb->insert($this->fields_table, array('field_slug' => $this->formatSlug($field_slug), 'field_label' => $this->encodeOption($field_label), 'field_type' => $field_type, 'field_value' => $this->encodeOption($field_value), 'field_maxlength' => $this->encodeOption($field_maxlength)));
				return true;
			}
			return false;
		}
		
		function fieldsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". $this->fields_table . "'") == $this->fields_table);
		}
		
		function formsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". $this->forms_table . "'") == $this->forms_table);
		}
		
		function updateForm($form_slug, $form_title, $form_action, $form_method, $submit_button_text, $custom_code, $fid) {
			global $wpdb;
			if (empty($form_slug)) return false;
			$test = $this->selectForm('', $form_slug);
			if (!empty($test) and $test->id != $fid) // if form_slug is different then make sure it is unique
				return false;
			$wpdb->update($this->forms_table, array('form_slug' => $this->formatSlug($form_slug), 'form_title' => $this->encodeOption($form_title), 'form_action' => $this->encodeOption($form_action), 'form_method' => $form_method, 'submit_button_text' => $this->encodeOption($submit_button_text), 'custom_code' => $this->encodeOption($custom_code)), array('id' => $fid));
			return true;
		}
		
		function updateField($field_slug, $field_label, $field_type, $field_value, $field_maxlength, $fid) {
			global $wpdb;
			if (empty($field_slug)) return false;
			$test = $this->selectField('', $field_slug);
			if (!empty($test) and $test->id != $fid) // if form_slug is different then make sure it is unique
				return false;
			$wpdb->update($this->fields_table, array('field_slug' => $this->formatSlug($field_slug), 'field_label' => $this->encodeOption($field_label), 'field_type' => $field_type, 'field_value' => $this->encodeOption($field_value), 'field_maxlength' => $this->encodeOption($field_maxlength)), array('id' => $fid));
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
		
		function selectAllForms() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->forms_table . " ORDER BY form_slug ASC");	
		}
		
		function selectAllFields() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . $this->fields_table . " ORDER BY field_slug ASC");	
		}
		
		function selectForm($fid, $form_slug) {
			global $wpdb;
			$extra = (!empty($field_slug)) ? " or form_slug = '$form_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . $this->forms_table . " WHERE id='$fid' $extra");
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
	}
}
?>