<?php
/*
	Plugin Name: Custom Contact Forms
	Plugin URI: http://taylorlovett.com/wordpress-plugins
	Description: Guaranteed to be 1000X more customizable and intuitive than Fast Secure Contact Forms or Contact Form 7. Customize every aspect of your forms without any knowledge of CSS: borders, padding, sizes, colors. Ton's of great features. Required fields, form submissions saved to database, captchas, tooltip popovers, unlimited fields/forms/form styles, import/export, use a custom thank you page or built-in popover with a custom success message set for each form.
	Version: 4.0.7
	Author: Taylor Lovett
	Author URI: http://www.taylorlovett.com
*/

/*
	If you have time to translate this plugin in to your native language, please contact me at 
	admin@taylorlovett.com and I will add you as a contributer with your name and website to the
	Wordpress plugin page.
	
	Languages: English

	Copyright (C) 2010-2011 Taylor Lovett, taylorlovett.com (admin@taylorlovett.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
$old_error_settings = error_reporting();
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_USER_DEPRECATED);
require_once('custom-contact-forms-static.php');
CustomContactFormsStatic::definePluginConstants();
require_once('modules/db/custom-contact-forms-db.php');
if (!class_exists('CustomContactForms')) {
	class CustomContactForms extends CustomContactFormsDB {
		var $adminOptionsName = 'customContactFormsAdminOptions';
		
		function activatePlugin() {
			$admin_options = $this->getAdminOptions();
			$admin_options['show_install_popover'] = 1;
			update_option($this->getAdminOptionsName(), $admin_options);
			parent::createTables();
			parent::updateTables();
			parent::insertFixedFields();
		}
		
		function getAdminOptionsName() {
			return $this->adminOptionsName;
		}
		
		function getAdminOptions() {
			$admin_email = get_option('admin_email');
			$customcontactAdminOptions = array('show_widget_home' => 1, 'show_widget_pages' => 1, 'show_widget_singles' => 1, 'show_widget_categories' => 1, 'show_widget_archives' => 1, 'default_to_email' => $admin_email, 'default_from_email' => $admin_email, 'default_form_subject' => __('Someone Filled Out Your Contact Form!', 'custom-contact-forms'), 
			'remember_field_values' => 0, 'author_link' => 1, 'enable_widget_tooltips' => 1, 'mail_function' => 'default', 'form_success_message_title' => __('Successful Form Submission', 'custom-contact-forms'), 'form_success_message' => __('Thank you for filling out our web form. We will get back to you ASAP.', 'custom-contact-forms'), 'enable_jquery' => 1, 'code_type' => 'XHTML',
			'show_install_popover' => 0, 'email_form_submissions' => 1, 'admin_ajax' => 1, 'smtp_host' => '', 'smtp_encryption' => 'none', 'smtp_authentication' => 0, 'smtp_username' => '', 'smtp_password' => '', 'smtp_port' => ''); // default general settings
			$customcontactOptions = get_option($this->getAdminOptionsName());
			if (!empty($customcontactOptions)) {
				foreach ($customcontactOptions as $key => $option)
					$customcontactAdminOptions[$key] = $option;
			}
			update_option($this->getAdminOptionsName, $customcontactAdminOptions);
			return $customcontactAdminOptions;
		}
	}
}
$custom_contact_forms = new CustomContactForms();

/* general plugin stuff */
if (isset($custom_contact_forms)) {
	register_activation_hook(__FILE__, array(&$custom_contact_forms, 'activatePlugin'));
}

if (!is_admin()) { /* is front */
	require_once('custom-contact-forms-front.php');
	$custom_contact_front = new CustomContactFormsFront();
	if (!function_exists('serveCustomContactForm')) {
		function serveCustomContactForm($fid) {
			global $custom_contact_front;
			echo $custom_contact_front->getFormCode($fid);
		}
	}
	add_action('init', array(&$custom_contact_front, 'frontInit'), 1);
	add_action('wp_print_scripts', array(&$custom_contact_front, 'insertFrontEndScripts'), 1);
	add_action('wp_print_styles', array(&$custom_contact_front, 'insertFrontEndStyles'), 1);
	add_filter('the_content', array(&$custom_contact_front, 'contentFilter'));
} else { /* is admin */
	$GLOBALS['ccf_current_page'] = ($_GET['page']) ? $_GET['page'] : '';
	require_once('custom-contact-forms-admin.php');
	$custom_contact_admin = new CustomContactFormsAdmin();
	if (!function_exists('CustomContactForms_ap')) {
		function CustomContactForms_ap() {
			global $custom_contact_admin;
			if (!isset($custom_contact_admin)) return;
			if (function_exists('add_options_page')) {
				add_options_page('Custom Contact Forms', 'Custom Contact Forms', 9, 'custom-contact-forms', array(&$custom_contact_admin, 'printAdminPage'));	
			}
		}
	}
	add_action('init', array(&$custom_contact_admin, 'adminInit'), 1);
	if ($custom_contact_admin->isPluginAdminPage()) {
		add_action('admin_print_styles', array(&$custom_contact_admin, 'insertBackEndStyles'), 1);
		add_action('admin_print_scripts', array(&$custom_contact_admin, 'insertAdminScripts'), 1);
		add_action('admin_footer', array(&$custom_contact_admin, 'insertUsagePopover'));
	}
	add_filter('plugin_action_links', array(&$custom_contact_admin,'appendToActionLinks'), 10, 2);
	add_action('admin_menu', 'CustomContactForms_ap');
}

/* widget stuff */
require_once('modules/widget/custom-contact-forms-widget.php');
if (!function_exists('CCFWidgetInit')) {
	function CCFWidgetInit() {
		register_widget('CustomContactFormsWidget');
	}
}
add_action('widgets_init', 'CCFWidgetInit');
?>