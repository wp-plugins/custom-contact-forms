<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsDB')) {
	class CustomContactFormsDB {
		var $cache = array();
		function createTables() {
			global $wpdb;
			if(!$this->formsTableExists()) {
				$sql1 = " CREATE TABLE `".CCF_FORMS_TABLE."` (
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
			} if(!$this->userDataTableExists()) {
				$sql7 = " CREATE TABLE `".CCF_USER_DATA_TABLE."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`data_time` INT( 11 ) NOT NULL DEFAULT '0',
						`data_formid` INT( 11 ) NOT NULL ,
						`data_formpage` VARCHAR ( 250 ) NOT NULL ,
						`data_value` LONGTEXT NOT NULL ,
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql7);
			} if(!$this->fieldOptionsTableExists()) {
				$sql5 = " CREATE TABLE `".CCF_FIELD_OPTIONS_TABLE."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`option_slug` VARCHAR( 100 ) NOT NULL ,
						`option_label` VARCHAR( 200 ) NOT NULL ,
						`option_value` VARCHAR( 100 ) NOT NULL ,
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql5);
			} if(!$this->fieldsTableExists()) {
				$sql2 = "CREATE TABLE `".CCF_FIELDS_TABLE."` (
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
				$sql3 = "CREATE TABLE `".CCF_STYLES_TABLE."` (
						`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
						`style_slug` VARCHAR( 30 ) NOT NULL ,
						`input_width` VARCHAR( 10 ) NOT NULL DEFAULT '200px',
						`textarea_width` VARCHAR( 10 ) NOT NULL DEFAULT '200px',
						`textarea_height` VARCHAR( 10 ) NOT NULL DEFAULT '100px',
						`form_borderwidth` VARCHAR( 10 ) NOT NULL DEFAULT '0px',
						`label_width` VARCHAR( 10 ) NOT NULL DEFAULT '200px',
						`form_width` VARCHAR( 10 ) NOT NULL DEFAULT '100%',
						`submit_width` VARCHAR( 10 ) NOT NULL DEFAULT 'auto',
						`submit_height` VARCHAR( 10 ) NOT NULL DEFAULT '40px',
						`label_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1em',
						`title_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1.2em',
						`field_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1.3em',
						`submit_fontsize` VARCHAR( 10 ) NOT NULL DEFAULT '1.1em',
						`field_bordercolor` VARCHAR( 10 ) NOT NULL DEFAULT '999999',
						`form_borderstyle` VARCHAR( 30 ) NOT NULL DEFAULT 'none',
						`form_bordercolor` VARCHAR( 20 ) NOT NULL DEFAULT '',
						`field_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '333333',
						`label_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '333333',
						`title_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '333333',
						`submit_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '333333',
						`form_fontfamily` VARCHAR( 150 ) NOT NULL DEFAULT 'Tahoma, Verdana, Arial',
						PRIMARY KEY ( `id` )
						) ENGINE = MYISAM AUTO_INCREMENT=1 ";
				$wpdb->query($sql3);
			}
			return true;
		}
		
		function formatStyle($style) {
			return str_replace('#', '', str_replace(';', '', $style));
		}
		
		function updateTableCharSets() {
			global $wpdb;
			foreach ($GLOBALS['ccf_tables_array'] as $table) {
				$wpdb->query("ALTER TABLE `" . $table . "`  DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
				$wpdb->query("ALTER TABLE `" . $table . "` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
			}
		}
		
		function updateTables() {
			global $wpdb;
			if (!$this->columnExists('user_field', CCF_FIELDS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FIELDS_TABLE . "` ADD `user_field` INT( 1 ) NOT NULL DEFAULT '1'");
			if (!$this->columnExists('form_style', CCF_FORMS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FORMS_TABLE . "` ADD `form_style` INT( 10 ) NOT NULL DEFAULT '0'");
			if (!$this->columnExists('form_email', CCF_FORMS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FORMS_TABLE . "` ADD `form_email` VARCHAR( 50 ) NOT NULL");
			if (!$this->columnExists('form_success_message', CCF_FORMS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FORMS_TABLE . "` ADD `form_success_message` TEXT NOT NULL");
			if (!$this->columnExists('form_thank_you_page', CCF_FORMS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FORMS_TABLE . "` ADD `form_thank_you_page` VARCHAR ( 200 ) NOT NULL");
			if (!$this->columnExists('field_backgroundcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `field_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT 'f5f5f5'");
			if (!$this->columnExists('field_borderstyle', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `field_borderstyle` VARCHAR( 20 ) NOT NULL DEFAULT 'solid'");
			if (!$this->columnExists('form_success_title', CCF_FORMS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FORMS_TABLE . "` ADD `form_success_title` VARCHAR( 150 ) NOT NULL DEFAULT '".__('Form Success!', 'custom-contact-forms')."'");
			if (!$this->columnExists('form_padding', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `form_padding` VARCHAR( 20 ) NOT NULL DEFAULT '8px'");
			if (!$this->columnExists('form_margin', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `form_margin` VARCHAR( 20 ) NOT NULL DEFAULT '7px'");
			if (!$this->columnExists('title_margin', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `title_margin` VARCHAR( 20 ) NOT NULL DEFAULT '4px'");
			if (!$this->columnExists('label_margin', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `label_margin` VARCHAR( 20 ) NOT NULL DEFAULT '6px'");
			if (!$this->columnExists('textarea_backgroundcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `textarea_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT 'f5f5f5'");
			if (!$this->columnExists('success_popover_bordercolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `success_popover_bordercolor` VARCHAR( 20 ) NOT NULL DEFAULT 'efefef'");
			if (!$this->columnExists('dropdown_width', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `dropdown_width` VARCHAR( 20 ) NOT NULL DEFAULT 'auto'");
			if (!$this->columnExists('success_popover_fontsize', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `success_popover_fontsize` VARCHAR( 20 ) NOT NULL DEFAULT '12px'");
			if (!$this->columnExists('success_popover_title_fontsize', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `success_popover_title_fontsize` VARCHAR( 20 ) NOT NULL DEFAULT '1.3em'");
			if (!$this->columnExists('success_popover_height', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `success_popover_height` VARCHAR( 20 ) NOT NULL DEFAULT '200px'");
			if (!$this->columnExists('success_popover_fontcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `success_popover_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '333333'");
			if (!$this->columnExists('success_popover_title_fontcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `success_popover_title_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT '333333'");
			if (!$this->columnExists('field_instructions', CCF_FIELDS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FIELDS_TABLE . "` ADD `field_instructions` TEXT NOT NULL");
			if (!$this->columnExists('field_options', CCF_FIELDS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FIELDS_TABLE . "` ADD `field_options` VARCHAR( 300 ) NOT NULL");
			if (!$this->columnExists('field_required', CCF_FIELDS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FIELDS_TABLE . "` ADD `field_required` INT( 1 ) NOT NULL DEFAULT '0'");
			if (!$this->columnExists('form_backgroundcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `form_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT 'ffffff'");
			if (!$this->columnExists('field_borderround', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `field_borderround` VARCHAR( 20 ) NOT NULL DEFAULT '6px'");
			if (!$this->columnExists('tooltip_backgroundcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `tooltip_backgroundcolor` VARCHAR( 20 ) NOT NULL DEFAULT '000000'");
			if (!$this->columnExists('tooltip_fontsize', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `tooltip_fontsize` VARCHAR( 20 ) NOT NULL DEFAULT '12px'");
			if (!$this->columnExists('tooltip_fontcolor', CCF_STYLES_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_STYLES_TABLE . "` ADD `tooltip_fontcolor` VARCHAR( 20 ) NOT NULL DEFAULT 'ffffff'");
			if (!$this->columnExists('field_class', CCF_FIELDS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FIELDS_TABLE . "` ADD `field_class` VARCHAR( 50 ) NOT NULL");
			if (!$this->columnExists('field_error', CCF_FIELDS_TABLE))
				$wpdb->query("ALTER TABLE `" . CCF_FIELDS_TABLE . "` ADD `field_error` VARCHAR( 300 ) NOT NULL");
			$wpdb->query("ALTER TABLE `" . CCF_FORMS_TABLE . "` CHANGE `form_email` `form_email` VARCHAR( 300 ) NOT NULL");
			$this->updateTableCharSets();
		}
		
		function insertFixedFields() {
			$captcha = array('field_slug' => 'captcha', 'field_label' => __('Type the numbers.', 'custom-contact-forms'), 'field_type' => 'Text', 'field_value' => '', 'field_maxlength' => '100', 'user_field' => 0, 'field_instructions' => 'Type the numbers displayed in the image above.');
			$ishuman = array('field_slug' => 'ishuman', 'field_label' => __('Check if you are human.', 'custom-contact-forms'), 'field_type' => 'Checkbox', 'field_value' => '1', 'field_maxlength' => '0', 'user_field' => 0, 'field_instructions' => 'This helps us prevent spam.');
			$fixedEmail = array('field_slug' => 'fixedEmail', 'field_label' => __('Your Email', 'custom-contact-forms'), 'field_type' => 'Text', 'field_value' => '', 'field_maxlength' => '100', 'user_field' => 0, 'field_instructions' => 'Please enter your email address.');
			$reset = array('field_slug' => 'resetButton', 'field_type' => 'Reset', 'field_value' => __('Reset Form', 'custom-contact-forms'), 'user_field' => 0);
			if (!$this->fieldSlugExists('captcha'))
				$this->insertField($captcha, true);
			if (!$this->fieldSlugExists('ishuman'))
				$this->insertField($ishuman, true);
			if (!$this->fieldSlugExists('fixedEmail'))
				$this->insertField($fixedEmail, true);
			if (!$this->fieldSlugExists('resetButton'))
				$this->insertField($reset, true);
		}
		
		function columnExists($column, $table) {
			global $wpdb;
			if (!is_array($this->cache[$table]))
				$this->cache[$table] = array();
			if (empty($this->cache[$table]['columns']))
				$this->cache[$table]['columns'] = $wpdb->get_results('SHOW COLUMNS FROM ' . $table, ARRAY_A);
			$col_array = $this->cache[$table]['columns'];
			foreach ($col_array as $col) {
				if ($col['Field'] == $column)
					return true;
			}
			return false;
		}
		
		function insertForm($form) {
			global $wpdb;
			if (empty($form) or empty($form['form_slug']) or $this->formSlugExists($this->formatSlug($form['form_slug']))) return false;
			$form['form_slug'] = $this->formatSlug($form['form_slug']);
			foreach ($form as $key => $value)
					$form[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->insert(CCF_FORMS_TABLE, $form);
			return $wpdb->insert_id;
		}
		
		function insertField($field, $fixed = false) {
			global $wpdb;
			if (empty($field) or empty($field['field_slug']) or (array_key_exists($this->formatSlug($field['field_slug']), $GLOBALS['ccf_fixed_fields']) && !$fixed) or $this->fieldSlugExists($this->formatSlug($field['field_slug'])))
				return false;
			$field['field_slug'] = $this->formatSlug($field['field_slug']);
			foreach ($field as $key => $value)
					$field[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->insert(CCF_FIELDS_TABLE, $field);
			return $wpdb->insert_id;
		}
		
		function insertFieldOption($option) {
			global $wpdb;
			if (empty($option) or empty($option['option_slug']) or empty($option['option_label']) or $this->fieldOptionsSlugExists($this->formatSlug($option['option_slug']))) return false;
			$option['option_slug'] = $this->formatSlug($option['option_slug']);
			foreach ($option as $key => $value)
					$option[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->insert(CCF_FIELD_OPTIONS_TABLE, $option);
			return $wpdb->insert_id;
		}
		
		function insertStyle($style) {
			global $wpdb;
			if (empty($style) or empty($style['style_slug']) or $this->styleSlugExists($this->formatSlug($style['style_slug']))) return false;
			$style['style_slug'] = $this->formatSlug($style['style_slug']);
			foreach ($style as $key => $value)
					$style[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->insert(CCF_STYLES_TABLE, $style);
			return $wpdb->insert_id;
		}
		
		
		function fieldsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". CCF_FIELDS_TABLE . "'") == CCF_FIELDS_TABLE);
		}
		
		function formsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". CCF_FORMS_TABLE . "'") == CCF_FORMS_TABLE);
		}
		
		function stylesTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". CCF_STYLES_TABLE . "'") == CCF_STYLES_TABLE);
		}
		
		function fieldOptionsTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". CCF_FIELD_OPTIONS_TABLE . "'") == CCF_FIELD_OPTIONS_TABLE);
		}
		
		function userDataTableExists() {
			global $wpdb;
			return ($wpdb->get_var("show tables like '". CCF_USER_DATA_TABLE . "'") == CCF_USER_DATA_TABLE);
		}
		
		function updateForm($form, $fid) {
			global $wpdb;
			if (!empty($form['form_slug'])) {
				$test = $this->selectForm('', $this->formatSlug($form['form_slug']));
				if (!empty($test) and $test->id != $fid) return false;
				$form['form_slug'] = $this->formatSlug($form['form_slug']);
			}
			foreach ($form as $key => $value)
					$form[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->update(CCF_FORMS_TABLE, $form, array('id' => $fid));
			return true;
		}
		
		function updateField($field, $fid) {
			global $wpdb;
			if (!empty($field['field_slug'])) {
				$test = $this->selectField('', $this->formatSlug($field['field_slug']));
				if ((!empty($test) and $test->id != $fid) or array_key_exists($this->formatSlug($field['field_slug']), $GLOBALS['ccf_fixed_fields']))
					return false;
				$field['field_slug'] = $this->formatSlug($field['field_slug']);
			}
			foreach ($field as $key => $value)
					$field[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->update(CCF_FIELDS_TABLE, $field, array('id' => $fid));
			return true;
		}
		
		function updateFieldOption($option, $oid) {
			global $wpdb;
			if (!empty($option['option_slug'])) {
				$test = $this->selectFieldOption('', $this->formatSlug($option['option_slug']));
				if (!empty($test) and $test->id != $oid)
					return false;
				$option['option_slug'] = $this->formatSlug($option['option_slug']);
			}
			foreach ($option as $key => $value)
					$option[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->update(CCF_FIELD_OPTIONS_TABLE, $option, array('id' => $oid));
			return true;
		}
		
		function updateStyle($style, $sid) {
			global $wpdb;
			if (empty($style['style_slug'])) return false;
			$test = $this->selectStyle('', $this->formatSlug($style['style_slug']));
			if (!empty($test) and $test->id != $sid) // if style_slug is different then make sure it is unique
				return false;
			$style['style_slug'] = $this->formatSlug($style['style_slug']);
			foreach ($style as $key => $value)
					$style[$key] = CustomContactFormsStatic::encodeOption($value);
			$wpdb->update(CCF_STYLES_TABLE, $style, array('id' => $sid));
			return true;
		}
		
		function deleteForm($fid, $slug = NULL) {
			global $wpdb;
			$where_params = ($slug == NULL) ? "id='$fid'" : "form_slug='$slug'";
			$wpdb->query("DELETE FROM " . CCF_FORMS_TABLE . ' WHERE ' . $where_params);
			return true;
		}
		
		function deleteField($fid, $slug = NULL) {
			global $wpdb;
			$this->dettachFieldAll($fid);
			$where_params = ($slug == NULL) ? "id='$fid'" : "field_slug='$slug'";
			$wpdb->query("DELETE FROM " . CCF_FIELDS_TABLE . ' WHERE ' . $where_params);
			return false;
		}
		
		function query($query) {
			global $wpdb;
			if (empty($query)) return false;
			return ($wpdb->query($query) != false) ? $wpdb->insert_id : false;
		}
		
		function deleteStyle($sid, $slug = NULL) {
			global $wpdb;
			$this->dettachStyleAll($sid);
			$where_params = ($slug == NULL) ? "id='$sid'" : "style_slug='$slug'";
			$wpdb->query("DELETE FROM " . CCF_STYLES_TABLE . ' WHERE ' . $where_params);
			return true;
		}
		
		function deleteFieldOption($oid, $slug = NULL) {
			global $wpdb;
			$this->dettachFieldOptionAll($oid);
			$where_params = ($slug == NULL) ? "id='$oid'" : "option_slug='$slug'";
			$wpdb->query("DELETE FROM " . CCF_FIELD_OPTIONS_TABLE . ' WHERE ' . $where_params);
			return true;
		}
		
		function deleteUserData($uid) {
			global $wpdb;
			$wpdb->query("DELETE FROM " . CCF_USER_DATA_TABLE . " WHERE id='$uid'");
			return true;
		}
		
		function selectAllFromTable($table, $output_type = OBJECT) {
			global $wpdb;
			return $wpdb->get_results('SELECT * FROM ' . $table, $output_type);
		}
		
		function selectAllForms() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . CCF_FORMS_TABLE . " ORDER BY form_slug ASC");	
		}
		
		function selectAllFields() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . CCF_FIELDS_TABLE . " ORDER BY field_slug ASC");	
		}
		
		function selectAllFieldOptions() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . CCF_FIELD_OPTIONS_TABLE . " ORDER BY option_slug ASC");	
		}
		
		function selectAllStyles() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . CCF_STYLES_TABLE . " ORDER BY style_slug ASC");	
		}
		
		function selectAllUserData() {
			global $wpdb;
			return $wpdb->get_results("SELECT * FROM " . CCF_USER_DATA_TABLE . " ORDER BY data_time DESC");	
		}
		
		function selectForm($fid, $form_slug = '') {
			global $wpdb;
			$extra = (!empty($form_slug)) ? " or form_slug = '$form_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . CCF_FORMS_TABLE . " WHERE id='$fid' $extra");
		}
		
		function selectStyle($sid, $style_slug = '') {
			global $wpdb;
			$extra = (!empty($style_slug)) ? " or style_slug = '$style_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . CCF_STYLES_TABLE . " WHERE id='$sid' $extra");
		}
		
		function selectField($fid, $field_slug = '') {
			global $wpdb;
			$extra = (!empty($field_slug)) ? " or field_slug = '$field_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . CCF_FIELDS_TABLE . " WHERE id='$fid'" . $extra);
		}
		
		function selectFieldOption($oid, $option_slug = '') {
			global $wpdb;
			$extra = (!empty($option_slug)) ? " or option_slug = '$option_slug'" : '';
			return $wpdb->get_row("SELECT * FROM " . CCF_FIELD_OPTIONS_TABLE . " WHERE id='$oid'" . $extra);
		}
		
		function selectUserData($uid) {
			global $wpdb;
			return $wpdb->get_row("SELECT * FROM " . CCF_USER_DATA_TABLE . " WHERE id='$uid'");
		}
		
		function addFieldToForm($field_id, $form_id) {
			$field = $this->selectField($field_id);
			if (empty($field)) return false;
			$form = $this->selectForm($form_id);
			if (empty($form)) return false;
			$fields = $this->getAttachedFieldsArray($form_id);
			if (!in_array($field_id, $fields)) {
				$new_fields = $form->form_fields . $field_id . ',';
				$this->updateForm(array('form_fields' => $new_fields), $form_id);
				return true;
			}
			return false;
		}
		
		function addFieldOptionToField($option_id, $field_id) {
			$option = $this->selectFieldOption($option_id);
			if (empty($option)) return false;
			$field = $this->selectField($field_id);
			if (empty($field)) return false;
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
		
		function insertUserData($data_object) {
			global $wpdb;
			$wpdb->insert(CCF_USER_DATA_TABLE, array('data_time' => $data_object->getDataTime(), 'data_formid' => $data_object->getFormID(), 'data_formpage' => $data_object->getFormPage(), 'data_value' => $data_object->getEncodedData()));
			return $wpdb->insert_id;
		}
		
		function emptyAllTables() {
			$fields = $this->selectAllFields();
			$forms = $this->selectAllForms();
			$user_data = $this->selectAllUserData();
			$styles = $this->selectAllStyles();
			$options = $this->selectAllFieldOptions();
			foreach ($fields as $field) $this->deleteField($field->id);
			foreach ($forms as $form) $this->deleteForm($form->id);
			foreach ($user_data as $data) $this->deleteUserData($data->id);
			foreach ($styles as $style) $this->deleteStyle($style->id);
			foreach ($options as $option) $this->deleteFieldOption($option->id);
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
			$name_field = array('field_slug' => $field_slugs['name'], 'field_label' => __('Your Name:', 'custom-contact-forms'),
			'field_required' => 1, 'field_instructions' => __('Please enter your full name.', 'custom-contact-forms'),
			'field_maxlength' => '100', 'field_type' => 'Text');
			$message_field = array('field_slug' => $field_slugs['message'], 'field_label' => __('Your Message:', 'custom-contact-forms'),
			'field_required' => 0, 'field_instructions' => __('Enter any message or comment.', 'custom-contact-forms'),
			'field_maxlength' => 0, 'field_type' => 'Textarea');
			$website_field = array('field_slug' => $field_slugs['website'], 'field_label' => __('Your Website:', 'custom-contact-forms'),
			'field_required' => 0, 'field_instructions' => __('If you have a website, please enter it here.', 'custom-contact-forms'),
			'field_maxlength' => 200, 'field_type' => 'Text');
			$phone_field = array('field_slug' => $field_slugs['phone'], 'field_label' => __('Your Phone Number:', 'custom-contact-forms'),
			'field_required' => 0, 'field_instructions' => __('Please enter your phone number.', 'custom-contact-forms'),
			'field_maxlength' => 30, 'field_type' => 'Text');
			$google_field = array('field_slug' => $field_slugs['google'], 'field_label' => __('Did you find my website through Google?', 'custom-contact-forms'),
			'field_required' => 0, 'field_instructions' => __('If you found my website through Google, check this box.', 'custom-contact-forms'),
			'field_maxlength' => 0, 'field_type' => 'Checkbox', 'field_value' => __('Yes', 'custom-contact-forms'));
			$contact_method_field = array('field_slug' => $field_slugs['contact_method'], 'field_label' => __('How should we contact you?', 'custom-contact-forms'),
			'field_required' => 1, 'field_instructions' => __('By which method we should contact you?', 'custom-contact-forms'),
			'field_maxlength' => 0, 'field_type' => 'Dropdown');
			$email_field = $this->selectField(0, 'fixedEmail');
			$captcha_field = $this->selectField(0, 'captcha');
			$reset_button = $this->selectField(0, 'resetButton');
			$email_option = array('option_slug' => $option_slugs['email'], 'option_label' => __('By Email', 'custom-contact-forms'));
			$phone_option = array('option_slug' => $option_slugs['phone'], 'option_label' => __('By Phone', 'custom-contact-forms'));
			$nocontact_option = array('option_slug' => $option_slugs['nocontact'], 'option_label' => __('Do Not Contact Me', 'custom-contact-forms'));
			$contact_form = array('form_slug' => $form_slugs['contact_form'], 'form_title' => __('Contact Form', 'custom-contact-forms'), 'form_method' => 'Post',
			'submit_button_text' => __('Send Message', 'custom-contact-forms'), 'form_email' => get_option('admin_email'), 'form_success_message' => __('Thank you for filling out our contact form. We will contact you very soon by the way you specified.', 'custom-contact-forms'),
			'form_success_title' => __('Thank You!', 'custom-contact-forms'), 'form_style' => 0);
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
			$this->addFieldToForm($reset_button->id, $contact_form_id);
		}
	}
}
?>