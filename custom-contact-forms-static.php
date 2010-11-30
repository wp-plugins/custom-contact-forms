<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsStatic')) {
	class CustomContactFormsStatic {
		function encodeOption($option) {
			return htmlspecialchars(stripslashes($option), ENT_QUOTES);
		}
		
		function startSession() {
			if (!session_id()) session_start();
		}
		
		function redirect($location) {
			if (!empty($location)) {
				wp_redirect($location);
				exit();
			}
		}
		
		function getWPTablePrefix() {
			global $wpdb;
			return $wpdb->prefix;
		}
		
		function encodeOptionArray($option_array) {
			foreach ($option_array as $option) {
				if (is_array($option))
					$option = CustomContactFormsStatic::encodeOptionArray($option);
				else
					$option = CustomContactFormsStatic::encodeOption($option);
			}
			return $option_array;
		}
		
		function decodeOption($option, $strip_slashes = 1, $decode_html_chars = 1) {
			if ($strip_slashes == 1) $option = stripslashes($option);
			if ($decode_html_chars == 1) $option = html_entity_decode($option);
			return $option;
		}
		
		function definePluginConstants() {
			$prefix = CustomContactFormsStatic::getWPTablePrefix();
			define('CCF_FORMS_TABLE', $prefix . 'customcontactforms_forms');
			define('CCF_FIELDS_TABLE', $prefix . 'customcontactforms_fields');
			define('CCF_STYLES_TABLE', $prefix . 'customcontactforms_styles');
			define('CCF_USER_DATA_TABLE', $prefix . 'customcontactforms_user_data');
			define('CCF_FIELD_OPTIONS_TABLE', $prefix . 'customcontactforms_field_options');
			define('CCF_BASE_PATH', ABSPATH . 'wp-content/plugins/custom-contact-forms/');
			$GLOBALS['ccf_tables_array'] = array(CCF_FORMS_TABLE, CCF_FIELDS_TABLE, CCF_STYLES_TABLE, CCF_USER_DATA_TABLE, CCF_FIELD_OPTIONS_TABLE);
			$GLOBALS['ccf_fixed_fields'] = array('customcontactforms_submit' => '', 
							'fid' => '', 
							'fixedEmail' => __('Use this field if you want the plugin to throw an error on fake emails.', 'custom-contact-forms'), 
							'form_page' => '', 
							'captcha' => __('This field requires users to type numbers in an image preventing spam.', 'custom-contact-forms'), 
							'ishuman' => __("This field requires users to check a box to prove they aren't a spam bot.", 'custom-contact-forms'),
							'resetButton' => __('This field lets users reset all form fields to their initial values. This will be inserted next to the submit button.', 'custom-contact-forms')
							);
		}
	}
}
?>