<?php
/*
	Plugin Name: Custom Contact Forms
	Plugin URI: http://taylorlovett.com/wordpress-plugins
	Description: Guaranteed to be 1000X more customizable and intuitive than Fast Secure Contact Forms or Contact Form 7. Customize every aspect of your forms without any knowledge of CSS: borders, padding, sizes, colors. Ton's of great features. Required fields, captchas, tooltip popovers, unlimited fields/forms/form styles, use a custom thank you page or built-in popover with a custom success message set for each form. <a href="options-general.php?page=custom-contact-forms">Settings</a>
	Version: 3.0.0
	Author: <a href="http://www.taylorlovett.com" title="Maryland Wordpress Developer">Taylor Lovett</a>
	Author URI: http://www.taylorlovett.com
	Contributors: Taylor Lovett
*/
/*
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
require_once('custom-contact-forms-db.php');
require_once('custom-contact-forms-mailer.php');
require_once('custom-contact-forms-images.php');
if (!class_exists('CustomContactForms')) {
	class CustomContactForms extends CustomContactFormsDB {
		var $adminOptionsName = 'customContactFormsAdminOptions';
		var $widgetOptionsName = 'widget_customContactForms';
		var $version = '2.1.0';
		var $form_errors;
		var $error_return;
		var $gets;
		var $current_thank_you_message;
		var $fixed_fields = array('customcontactforms_submit' => '', 
							'fid' => '', 
							'fixedEmail' => 'Use this field if you want the plugin to throw an error on fake emails.', 
							'form_page' => '', 
							'captcha' => 'This field requires users to type numbers in an image preventing spam.', 
							'ishuman' => 'This field requires users to check a box to prove they aren\'t a spam bot.'
							);
		
		function CustomContactForms() {
			parent::CustomContactFormsDB();
			$this->form_errors = array();
		}
		
		function getAdminOptions() {
			$admin_email = get_option('admin_email');
			$customcontactAdminOptions = array('show_widget_home' => 1, 'show_widget_pages' => 1, 'show_widget_singles' => 1, 'show_widget_categories' => 1, 'show_widget_archives' => 1, 'default_to_email' => $admin_email, 'default_from_email' => $admin_email, 'default_form_subject' => 'Someone Filled Out Your Contact Form!', 
			'remember_field_values' => 0, 'author_link' => 1, 'enable_widget_tooltips' => 1, 'wp_mail_function' => 1, 'form_success_message' => 'Thank you for filling out our web form. We will get back to you ASAP.'); // default general settings
			$customcontactOptions = get_option($this->adminOptionsName);
			if (!empty($customcontactOptions)) {
				foreach ($customcontactOptions as $key => $option)
					$customcontactAdminOptions[$key] = $option;
			}
			update_option($this->adminOptionsName, $customcontactAdminOptions);
			return $customcontactAdminOptions;
		}
		function init() {
			$this->storeGets();
			$this->getAdminOptions();
			if (!is_admin()) {
				wp_enqueue_script('jquery');
				$this->startSession();
				$this->processForms();
			}
			$this->registerSidebar();
		}
		
		function registerSidebar() {
			register_sidebar_widget(__('Custom Contact Form'), array($this, 'widget_customContactForms'));
			register_widget_control('Custom Contact Form', array($this, 'customContactForms_control'), 300, 200);
		}
		
		function customContactForms_control() {
			$option = get_option($this->widgetOptionsName);
			if (empty($option)) $option = array('widget_form_id' => '0');
			if ($_POST[widget_form_id]) {
				$option[widget_form_id] = $_POST[widget_form_id];
				update_option($this->widgetOptionsName, $option);
				$option = get_option($this->widgetOptionsName);
			}
			$forms = parent::selectAllForms();
			
			$form_options = '';
			foreach ($forms as $form) {
				$sel = ($option[widget_form_id] == $form->id) ? ' selected="selected"' : '';
				$form_options .= '<option value="'.$form->id.'"'.$sel.'>'.$form->form_slug.'</option>';
			}
			if (empty($form_options)) { ?>
<p>Create a form in the Custom Contact Forms settings page.</p>
<?php
			} else {
				?>
<p>
  <label for="widget_form_id">Show Form:</label>
  <select name="widget_form_id">
    <?php echo $form_options; ?>
  </select>
</p>
<?php
			}
		}
		function widget_customContactForms($args) {
			extract($args);
			$admin_option = $this->getAdminOptions();
			if ((is_front_page() and $admin_option[show_widget_home] != 1) or (is_single() and $admin_option[show_widget_singles] != 1) or 
				(is_page() and $admin_option[show_widget_pages] != 1) or (is_category() and $admin_option[show_widget_categories] != 1) or 
				(is_archive() and $admin_option[show_widget_archives] != 1))
				return false;
			$option = get_option($this->widgetOptionsName);
			if (empty($option) or $option[widget_form_id] < 1) return false;
			echo $before_widget . $this->getFormCode($option[widget_form_id], true) . $after_widget;
		}
		function addHeaderCode() {
			?>
            <!-- Custom Contact Forms by Taylor Lovett - http://www.taylorlovett.com -->
            <link rel="stylesheet" href="<?php echo get_option('siteurl'); ?>/wp-content/plugins/custom-contact-forms/custom-contact-forms.css" type="text/css" media="screen" />
            <?php		
		}
		
		function insertAdminScripts() {
			wp_enqueue_script('ccf-main', get_option('siteurl') . '/wp-content/plugins/custom-contact-forms/js/custom-contact-forms-admin.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'/*, 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-dialog'*/), '1.0');
		}
		
		function insertFrontEndScripts() {
			wp_enqueue_script('jquery-tools', get_option('siteurl') . '/wp-content/plugins/custom-contact-forms/js/jquery.tools.min.js');
			//wp_enqueue_script('jquery-ui-position', get_option('siteurl') . '/wp-content/plugins/custom-contact-forms/js/jquery.ui.position.js');
			//wp_enqueue_script('jquery-ui-widget', get_option('siteurl') . '/wp-content/plugins/custom-contact-forms/js/jquery.ui.widget.js');
			//wp_enqueue_script('jquery-bgiframe', get_option('siteurl') . '/wp-content/plugins/custom-contact-forms/js/jquery.bgiframe-2.1.1.js');
			wp_enqueue_script('ccf-main', get_option('siteurl') . '/wp-content/plugins/custom-contact-forms/js/custom-contact-forms.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'/*, 'jquery-ui-draggable', 'jquery-ui-resizable', 'jquery-ui-dialog'*/), '1.0');
			//jquery-ui-position
		}
		
		function setFormError($key, $message) {
			$this->form_errors[$key] = $message;
		}
		
		function storeGets() {
			foreach ($_GET as $k => $v) {
				$this->gets[$k] = $v;
			}
		}
		
		function getFormError($key) {
			return $this->form_errors[$key];
		}
		
		function getAllFormErrors() {
			return $this->form_errors;
		}
		
		function printAdminPage() {
			$admin_options = $this->getAdminOptions();
			if ($_POST[form_create]) {
				parent::insertForm($_POST[form]);
			} elseif ($_POST[field_create]) {
				parent::insertField($_POST[field]);
			} elseif ($_POST[general_settings]) {
				$admin_options[default_to_email] = $_POST[default_to_email];
				$admin_options[default_from_email] = $_POST[default_from_email];
				$admin_options[default_form_subject] = $_POST[default_form_subject];
				$admin_options[show_widget_categories] = $_POST[show_widget_categories];
				$admin_options[show_widget_singles] = $_POST[show_widget_singles];
				$admin_options[show_widget_pages] = $_POST[show_widget_pages];
				$admin_options[show_widget_archives] = $_POST[show_widget_archives];
				$admin_options[show_widget_home] = $_POST[show_widget_home];
				$admin_options[custom_thank_you] = $_POST[custom_thank_you];
				$admin_options[author_link] = $_POST[author_link];
				$admin_options[form_success_message] = $_POST[form_success_message];
				$admin_options[wp_mail_function] = $_POST[wp_mail_function];
				$admin_options[enable_widget_tooltips] = $_POST[enable_widget_tooltips];
				$admin_options[remember_field_values] = $_POST[remember_field_values];
				update_option($this->adminOptionsName, $admin_options);
			} elseif ($_POST[field_edit]) {
				parent::updateField($_POST[field], $_POST[fid]);
			} elseif ($_POST[field_delete]) {
				parent::deleteField($_POST[fid]);
			} elseif ($_POST[form_delete]) {
				parent::deleteForm($_POST[fid]);
			} elseif ($_POST[form_edit]) {
				parent::updateForm($_POST[form], $_POST[fid]);
			} elseif ($_POST[form_add_field]) {
				parent::addFieldToForm($_POST[field_id], $_POST[fid]);
			} elseif ($_POST[disattach_field]) {
				parent::disattachField($_POST[disattach_field_id], $_POST[fid]);
			}  elseif ($_POST[style_create]) {
				parent::insertStyle($_POST[style]);
			}  elseif ($_POST[style_edit]) {
				parent::updateStyle($_POST[style], $_POST[sid]);
			}  elseif ($_POST[style_delete]) {
				parent::deleteStyle($_POST[sid]);
			} elseif ($_POST[contact_author]) {
				$this_url = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $_SERVER['SERVER_NAME'];
				$this->contactAuthor($_POST[name], $_POST[email], $this_url, $_POST[message], $_POST[type]);
			}
			$styles = parent::selectAllStyles();
			$style_options = '<option value="0">None</option>';
			foreach ($styles as $style)
				$style_options .= '<option value="'.$style->id.'">'.$style->style_slug.'</option>';
			?>
<div id="customcontactforms-admin">
  <div id="icon-themes" class="icon32"></div>
  <h2>Custom Contact Forms</h2>
  <ul id="plugin-nav">
  	<li><a href="#instructions">Plugin Instructions</a></li>
  	<li><a href="#general-settings">General Settings</a></li>
  	<li><a href="#create-fields">Create Fields</a></li>
    <li><a href="#create-forms">Create Forms</a></li>
    <li><a href="#manage-fields">Manage Fields</a></li>
    <li><a href="#manage-fixed-fields">Manage Fixed Fields</a></li>
    <li><a href="#manage-forms">Manage Forms</a></li>
    <li><a href="#create-styles">Create Styles</a></li>
    <li><a href="#manage-styles">Manage Styles</a></li>
    <li><a href="#contact-author">Suggest a Feature</a></li>
    <li><a href="#contact-author">Bug Report</a></li>
    <li><a href="#custom-html">Custom HTML Forms (New!)</a></li>
    <li class="last"><a href="#plugin-news">Plugin News</a></li>
  </ul><a name="create-fields"></a>
  <div id="create-fields" class="postbox">
    <h3 class="hndle"><span>Create A Form Field</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
          <li>
            <label for="field_slug">* Slug (Name):</label>
            <input name="field[field_slug]" type="text" maxlength="50" />
            (Must be unique)</li>
          <li>
            <label for="field_label">Field Label:</label>
            <input name="field[field_label]" type="text" maxlength="100" />
          </li>
          <li>
            <label for="field_type">* Field Type:</label>
            <select name="field[field_type]">
              <option>Text</option>
              <option>Textarea</option>
              <option>Hidden</option>
              <option>Checkbox</option>
            </select>
          </li>
          <li>
            <label for="field_value">Initial Value:</label>
            <input name="field[field_value]" type="text" maxlength="50" />
          </li>
          <li>
            <label for="field_maxlength">Max Length:</label>
            <input class="width50" size="10" name="field[field_maxlength]" type="text" maxlength="4" />
            (0 for no limit; only applies to Text fields)</li>
          <li>
            <label for="field_required">Required Field:</label>
            <select name="field[field_required]"><option value="0">No</option><option value="1">Yes</option></select></li>
          <li>
            <label for="field_value">Field Instructions:</label>
            <input name="field[field_instructions]" type="text" /><br />
            (If this is filled out, a tooltip popover displaying this text will show when the field is selected.)
          </li>
          <li><input type="hidden" name="field[user_field]" value="1" />
            <input type="submit" value="Create Field" name="field_create" />
          </li>
        </ul>
      </form>
    </div>
  </div><a name="create-forms"></a>
  <div id="create-forms" class="postbox">
    <h3 class="hndle"><span>Create A Form</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
          <li>
            <label for="form[form_name]">Form Slug:</label>
            <input type="text" maxlength="100" name="form[form_slug]" /><br />
            (Must be unique and contain only underscores and alphanumeric characters.)</li>
          <li>
            <label for="form[form_title]">Form Title:</label>
            <input type="text" maxlength="200" name="form[form_title]" />
            (The form header text)</li>
          <li>
            <label for="form[form_method]">Form Method:</label>
            <select name="form[form_method]">
              <option>Post</option>
              <option>Get</option>
            </select>
            (If unsure, leave as is.)</li>
          <li>
            <label for="form[form_action]">Form Action:</label>
            <input type="text" name="form[form_action]" value="" />
            (If unsure, leave blank.)</li>
          <li>
            <label for="form[form_action]">Form Style:</label>
            <select name="form[form_style]"><?php echo $style_options; ?></select>
            (<a href="#create-styles">Click to create a style</a>)</li>
          <li>
            <label for="form[submit_button_text]">Submit Button Text:</label>
            <input type="text" maxlength="200" name="form[submit_button_text]" />
          </li>
          <li>
            <label for="form[custom_code]">Custom Code:</label>
            <input type="text" name="form[custom_code]" /><br />
            (If unsure, leave blank. This field allows you to insert custom HTML directly after the starting form tag.)</li>
          <li>
            <label for="form[form_email]">Form Destination Email:</label>
            <input type="text" name="form[form_email]" /><br />
            (Will receive all submissions from this form; if left blank it will use the default specified in general settings.)</li>
          <li>
            <label for="form[form_success_message]">Form Success Message:</label>
            <input type="text" name="form[form_success_message]" /><br />
            (Will be displayed in a popover when the form is filled out successfully when no custom success page is specified; if left blank it will use the default specified in general settings.)</li>
          <li>
            <label for="form[form_thank_you_page]">Custom Success URL:</label>
            <input type="text" name="form[form_thank_you_page]" /><br />
            (If this is filled out, users will be sent to this page when they successfully fill out this form. If it is left blank, a popover showing the form's "success message" will be displayed on form success.)</li>
          <li>
            <input type="submit" value="Create Form" name="form_create" />
          </li>
        </ul>
      </form>
    </div>
  </div><a name="manage-fields"></a>
  <h3 class="manage-h3">Manage User Fields</h3>
  <table class="widefat post" id="manage-fields" cellspacing="0">
    <thead>
      <tr>
        <th scope="col" class="manage-column field-slug">Slug</th>
        <th scope="col" class="manage-column field-label">Label</th>
        <th scope="col" class="manage-column field-type">Type</th>
        <th scope="col" class="manage-column field-value">Initial Value</th>
        <th scope="col" class="manage-column field-required">Required</th>
        <th scope="col" class="manage-column field-maxlength">Maxlength</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
                $fields = parent::selectAllFields();
                for ($i = 0, $z = 0; $i < count($fields); $i++, $z++) {
					if ($fields[$i]->user_field == 0) { $z--; continue; }
                    $field_types = '<option>Text</option><option>Textarea</option><option>Hidden</option><option>Checkbox</option>';
                    $field_types = str_replace('<option>'.$fields[$i]->field_type.'</option>',  '<option selected="selected">'.$fields[$i]->field_type.'</option>', $field_types);
                    
                ?>
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">        
      <tr<?php if ($z % 2 == 1) echo ' class="evenrow"'; ?>>
        
          <td><input type="text" name="field[field_slug]" class="width100" maxlength="50" value="<?php echo $fields[$i]->field_slug; ?>" /></td>
          <td><input type="text" name="field[field_label]" maxlength="100" value="<?php echo $fields[$i]->field_label; ?>" /></td>
          <td><select name="field[field_type]">
              <?php echo $field_types; ?>
            </select></td>
          <td><input type="text" name="field[field_value]" maxlength="50" class="width75" value="<?php echo $fields[$i]->field_value; ?>" /></td>
          <td><select name="field[field_required]"><option value="1">Yes</option><option value="0" <?php if ($fields[$i]->field_required != 1) echo 'selected="selected"'; ?>>No</option></select></td>
          <td><input type="text" class="width50" name="field[field_maxlength]" value="<?php echo $fields[$i]->field_maxlength; ?>" /></td>
          <td><input type="hidden" name="fid" value="<?php echo $fields[$i]->id; ?>" />
            <span class="fields-options-expand"></span>
            <input type="submit" name="field_edit" value="Edit" />
            <input type="submit" name="field_delete" value="Delete" /></td>
        
      </tr>
      <tr<?php if ($z % 2 == 1) echo ' class="evenrow"'; ?>>
      	<td class="fields-extra-options" colspan="7" style="border-bottom:1px solid black;">Field Instructions: <input type="text" class="width200" name="field[field_instructions]" value="<?php echo $fields[$i]->field_instructions; ?>" /></td>
      </tr>
      </form>
      <?php
                }
                ?>
    </tbody>
    <tfoot>
      <tr>
        <th scope="col" class="manage-column field-slug">Slug</th>
        <th scope="col" class="manage-column field-label">Label</th>
        <th scope="col" class="manage-column field-type">Type</th>
        <th scope="col" class="manage-column field-value">Initial Value</th>
        <th scope="col" class="manage-column field-required">Required</th>
        <th scope="col" class="manage-column field-maxlength">Maxlength</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </tfoot>
  </table><a name="manage-fixed-fields"></a>
  <h3 class="manage-h3">Manage Fixed Fields</h3>
  <table class="widefat post" id="manage-fixed-fields" cellspacing="0">
    <thead>
      <tr>
        <th scope="col" class="manage-column field-slug">Slug</th>
        <th scope="col" class="manage-column field-label">Label</th>
        <th scope="col" class="manage-column field-type">Type</th>
        <th scope="col" class="manage-column field-value">Initial Value</th>
        <th scope="col" class="manage-column field-value">Required</th>
        <th scope="col" class="manage-column field-maxlength">Maxlength</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
                $fields = parent::selectAllFields();
                for ($i = 0, $z = 0; $i < count($fields); $i++, $z++) {
					if ($fields[$i]->user_field == 1) { $z--; continue;}
                    $field_types = '<option>Text</option><option>Textarea</option><option>Hidden</option><option>Checkbox</option>';
                    $field_types = str_replace('<option>'.$fields[$i]->field_type.'</option>',  '<option selected="selected">'.$fields[$i]->field_type.'</option>', $field_types);
                    
                ?>
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">        
      <tr <?php if ($z % 2 == 0) echo ' class="evenrow"'; ?> style="border:none;">
        
          <td><?php echo $fields[$i]->field_slug; ?></td>
          <td><input type="text" name="field[field_label]" maxlength="100" value="<?php echo $fields[$i]->field_label; ?>" /></td>
          <td><?php echo $fields[$i]->field_type; ?>
            <td><?php if ($fields[$i]->field_type != 'Checkbox') { ?>
          	<input type="text" name="field[field_value]" class="width75" maxlength="50" value="<?php echo $fields[$i]->field_value; ?>" />
          <?php } else {
          	echo $fields[$i]->field_value;
			?>
          <?php } ?>
          </td>
          <td>
          <?php if ($fields[$i]->field_slug == 'fixedEmail') { ?>
          <select name="field[field_required]"><option value="1">Yes</option><option <?php if($fields[$i]->field_required != 1) echo 'selected="selected"'; ?> value="0">No</option></select>
          <?php } else { ?>
          	Yes
          <?php } ?>
          </td>
          <td><?php if ($fields[$i]->field_type != 'Checkbox') { ?>
          	<input type="text" class="width50" name="field[field_maxlength]" value="<?php echo $fields[$i]->field_maxlength; ?>" />
          <?php } else { ?>
          	None
          <?php } ?>
          </td>
          
          <td><input type="hidden" name="fid" value="<?php echo $fields[$i]->id; ?>" />
            <span class="fixed-fields-options-expand"></span>
            <input type="submit" name="field_edit" value="Edit" /></td>
      </tr>
      <tr <?php if ($z % 2 == 0) echo ' class="evenrow"'; ?> style="border:none;">
      	<td class="fixed-fields-extra-options" colspan="7" style="border-bottom:1px solid black;">Field Instructions: <input type="text" name="field[field_instructions]" class="width200" value="<?php echo $fields[$i]->field_instructions; ?>" /> - <?php echo $this->fixed_fields[$fields[$i]->field_slug]; ?></td>
      </tr>
      </form>
      <?php
                }
                ?>
    </tbody>
    <tfoot>
      <tr>
        <th scope="col" class="manage-column field-slug">Slug</th>
        <th scope="col" class="manage-column field-label">Label</th>
        <th scope="col" class="manage-column field-type">Type</th>
        <th scope="col" class="manage-column field-value">Initial Value</th>
        <th scope="col" class="manage-column field-value">Required</th>
        <th scope="col" class="manage-column field-maxlength">Maxlength</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </tfoot>
  </table><a name="manage-forms"></a>
  <h3 class="manage-h3">Manage Forms</h3>
  <table class="widefat post" id="manage-forms" cellspacing="0">
    <thead>
      <tr>
      	<th scope="col" class="manage-column form-code">Form Display Code</th>
        <th scope="col" class="manage-column form-slug">Slug</th>
        <th scope="col" class="manage-column form-title">Title</th>
        <th scope="col" class="manage-column form-submit">Button Text</th>
        <th scope="col" class="manage-column form-submit">Style</th>
        <th scope="col" class="manage-column form-submit">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
                $forms = parent::selectAllForms();
                for ($i = 0; $i < count($forms); $i++) {
                    $form_methods = '<option>Post</option><option>Get</option>';
                    $form_methods = str_replace('<option>'.$forms[$i]->form_method.'</option>',  '<option selected="selected">'.$forms[$i]->form_method.'</option>', $form_methods);
                    $add_fields = $this->getFieldsForm();
					$this_style = parent::selectStyle($forms[$i]->form_style, '');
					$sty_opt = str_replace('<option value="'.$forms[$i]->form_style.'">'.$this_style->style_slug.'</option>', '<option value="'.$forms[$i]->form_style.'" selected="selected">'.$this_style->style_slug.'</option>', $style_options);
                ?>
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <tr class="<?php if ($i % 2 == 0) echo 'evenrow'; ?>">
      	  <td><span class="bold">[customcontact form=<?php echo $forms[$i]->id ?>]</span></td>
          <td><input type="text" class="width75" name="form[form_slug]" value="<?php echo $forms[$i]->form_slug; ?>" /></td>
          <td><input type="text" class="width125" name="form[form_title]" value="<?php echo $forms[$i]->form_title; ?>" /></td>
          <td><input class="width100" type="text" name="form[submit_button_text]" value="<?php echo $forms[$i]->submit_button_text; ?>" /></td>
          <td><select name="form[form_style]"><?php echo $sty_opt; ?></select></td>
          <td><input type="hidden" name="fid" value="<?php echo $forms[$i]->id; ?>" />
            <span class="form-options-expand"></span>
            <input type="submit" name="form_edit" value="Edit" />
            <input type="submit" name="form_delete" value="Delete" />
          </td>
      </tr>
      <tr class="<?php if ($i % 2 == 0) echo 'evenrow'; ?>">
          <td class="form-extra-options textcenter" colspan="8" style="border-bottom:1px solid black;">
              <table class="form-extra-options-table">
              	<tbody>
                	<tr>
                    	<td class="bold">Form Method</td>
                        <td class="bold">Form Action</td>
                        <td class="bold">Destination Email</td>
                        <td class="bold">Custom Code</td>
                        <td class="bold">Success Message</td>
                        <td class="bold">Custom Success URL</td>
                    </tr>
                    <tr>
                    	<td><select name="form[form_method]"><?php echo $form_methods; ?></select></td>
                    	<td><input type="text" name="form[form_action]" value="<?php echo $forms[$i]->form_action; ?>" /></td>
                        <td><input type="text" name="form[form_email]" value="<?php echo $forms[$i]->form_email; ?>" /></td>
                        <td><input type="text" name="form[custom_code]" value="<?php echo $forms[$i]->custom_code; ?>" /></td>
                        <td><input type="text" name="form[form_success_message]" value="<?php echo $forms[$i]->form_success_message; ?>" /></td>
                    	<td><input type="text" class="width125" name="form[form_thank_you_page]" value="<?php echo $forms[$i]->form_thank_you_page; ?>" /></td>
                    </tr>
                    <tr>
                    	<td colspan="3">
                        	<label for="disattach_field_id"><span>Attached Fields:</span></label>
							  <?php
                              	$attached_fields = parent::getAttachedFieldsArray($forms[$i]->id);
                                if (empty($attached_fields)) echo 'None ';
                                else {
                                	echo '<select name="disattach_field_id">';
                                    foreach($attached_fields as $attached_field) {
                                    	$this_field = parent::selectField($attached_field, '');
                                        echo $this_field->field_slug . ' <option value="'.$this_field->id.'">'.$this_field->field_slug.'</option>';
                                    }
                                    echo '</select> <input type="submit" value="Disattach Field" name="disattach_field" />';
                                }
                              ?><br />
                              <span class="red bold">*</span> Attach fields in the order you want them displayed.
                        </td>
                        <td colspan="3">
                        	<label for="field_id"><span>Attach Field:</span></label>
              					<select name="field_id"><?php echo $add_fields; ?></select> <input type="submit" name="form_add_field" value="Attach Field" />
                                <br /><span class="red bold">*</span> Attach fixed fields or ones you <a href="#create-fields">create</a>.
                        </td>
                    </tr>
                    <tr>
                    	<td colspan="6"><label for="theme_code_<?php echo $forms[$i]->id; ?>"><span>Code to Display Form in Theme Files:</span></label> <input type="text" class="width225" value="if (function_exists('serveCustomContactForm')) { serveCustomContactForm(<?php echo $forms[$i]->id; ?>); }" name="theme_code_<?php echo $form[$i]->id; ?>" /></td>
                    </tr>
                </tbody>
          	  </table>
          </td>
      </tr>
      
      </form>
      <?php
                }
				$remember_check = ($admin_options[remember_field_values] == 0) ? 'selected="selected"' : '';
				$remember_fields = '<option value="1">Yes</option><option '.$remember_check.' value="0">No</option>';
				$border_style_options = '<option>solid</option><option>dashed</option>
            <option>grooved</option><option>double</option><option>dotted</option><option>ridged</option><option>none</option>
            <option>inset</option><option>outset</option>';
                ?>
    </tbody>
    <tfoot>
      <tr>
      <tr>
      	<th scope="col" class="manage-column form-code">Form Code</th>
        <th scope="col" class="manage-column form-slug">Slug</th>
        <th scope="col" class="manage-column form-title">Title</th>
        <th scope="col" class="manage-column form-submit">Button Text</th>
        <th scope="col" class="manage-column form-submit">Style</th>
        <th scope="col" class="manage-column form-submit">Action</th>
      </tr>
      </tr>
      
    </tfoot>
  </table><a name="general-settings"></a>
  <div id="general-settings" class="postbox">
    <h3 class="hndle"><span>General Settings</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
          <li>
            <label for="default_to_email">Default Email:</label>
            <input name="default_to_email" value="<?php echo $admin_options[default_to_email]; ?>" type="text" maxlength="100" />
          </li>
          <li class="descrip">Form emails will be sent <span>to</span> this address, if no destination email is specified by the form.</li>
          <li>
            <label for="default_from_email">Default From Email:</label>
            <input name="default_from_email" value="<?php echo $admin_options[default_from_email]; ?>" type="text" maxlength="100" />
          </li>
          <li class="descrip">Form emails will be sent <span>from</span> this address. It is recommended you provide a real email address that has been created through your host.</li>
          <li>
            <label for="default_form_subject">Default Email Subject:</label>
            <input name="default_form_subject" value="<?php echo $admin_options[default_form_subject]; ?>" type="text" />
          </li>
          <li class="descrip">Default subject to be included in all form emails.</li>
          <li>
            <label for="form_success_message">Default Thank You Message:</label>
            <input name="form_success_message" value="<?php echo $admin_options[form_success_message]; ?>" type="text"/>
          </li>
          <li class="descrip">If someone fills out a form for which a success message is not provided and a custom success page is not provided, the plugin will show a popover containing this message.</li>
          
          <li>
            <label for="remember_field_values">Remember Field Values:</label>
            <select name="remember_field_values"><option value="1">Yes</option><option <?php if ($admin_options[remember_field_values] == 0) echo 'selected="selected"'; ?> value="0">No</option></select>
          </li>
          <li class="descrip">Selecting yes will make form fields remember how they were last filled out.</li>
          <li>
            <label for="enable_widget_tooltips">Enable Tooltips in Widget:</label>
            <select name="enable_widget_tooltips"><option value="1">Yes</option><option <?php if ($admin_options[enable_widget_tooltips] == 0) echo 'selected="selected"'; ?> value="0">No</option></select>
          </li>
          <li class="descrip">Enabling this shows tooltips containing field instructions on forms in the widget.</li>
          <li>
            <label for="author_link">Hide Plugin Author Link in Code:</label>
            <select name="author_link"><option value="1">Yes</option><option <?php if ($admin_options[author_link] == 0) echo 'selected="selected"'; ?> value="0">No</option></select>
          </li>
          <li>
            <label for="wp_mail_function">Use Wordpress Mail Function:</label>
            <select name="wp_mail_function"><option value="1">Yes</option><option <?php if ($admin_options[wp_mail_function] == 0) echo 'selected="selected"'; ?> value="0">No</option></select>
          </li>
          <li class="descrip">Setting this to no will use the PHP mail function. If your forms aren't sending mail properly try setting this to no.</li>
          <li class="show-widget"><b>Show Sidebar Widget:</b></li>
          <li>
            <label>
            <input value="1" type="checkbox" name="show_widget_home" <?php if ($admin_options[show_widget_home] == 1) echo 'checked="checked"'; ?> />
            On Homepage</label>
          </li>
          <li>
            <label>
            <input value="1" type="checkbox" name="show_widget_pages" <?php if ($admin_options[show_widget_pages] == 1) echo 'checked="checked"'; ?> />
            On Pages</label>
          </li>
          <li>
            <label>
            <input value="1" type="checkbox" name="show_widget_singles" <?php if ($admin_options[show_widget_singles] == 1) echo 'checked="checked"'; ?> />
            On Single Posts</label>
          </li>
          <li>
            <label>
            <input value="1" type="checkbox" name="show_widget_categories" <?php if ($admin_options[show_widget_categories] == 1) echo 'checked="checked"'; ?> />
            On Categories</label>
          </li>
          <li>
            <label>
            <input value="1" type="checkbox" name="show_widget_archives" <?php if ($admin_options[show_widget_archives] == 1) echo 'checked="checked"'; ?> />
            On Archives</label>
          </li>
          <li>
            <input type="submit" value="Update" name="general_settings" />
          </li>
        </ul>
      </form>
    </div>
  </div><a name="instructions"></a>
  <div id="instructions" class="postbox">
    <h3 class="hndle"><span>Instructions</span></h3>
    <div class="inside">
      <p>1. Create a form.</p>
      <p>2. Create fields and attach those fields to the forms of your choice. <b>* Attach the fields in the order that you want them to show up in the form. If you mess up you can detach and reattach them.</b></p>
      <p>3. Display those forms in posts and pages by inserting the code: [customcontact form=<b>FORMID</b>]. Replace <b>FORMID</b> with the id listed to the left of the form slug next to the form of your choice above. You can also display forms in theme files; the code for this is provided within each forms admin section.</p>
      <p>4. Prevent spam by attaching the fixed field, captcha or ishuman. Captcha requires users to type in a number shown on an image. Ishuman requires users to check a box to prove they aren't a spam bot.</p>
      <p>5. Add a form to your sidebar, by dragging the Custom Contact Form widget in to your sidebar.</p>
      <p>6. Configure the General Settings appropriately; this is important if you want to receive your web form messages!</p>
      <p>7. Create form styles to change your forms appearances. The image below explains how each style field can change the look of your forms.</p>
      <p>8. (advanced) If you are confident in your HTML and CSS skills, you can use the <a href="#custom-html">Custom HTML Forms feature</a> as a framework and write your forms from scratch. This allows you to use this plugin simply to process your form requests. The Custom HTML Forms feature will process and email any form variables sent to it regardless of whether they are created in the fields manager.</p>
      <div id="style-example"></div>
    </div>
  </div>
  <a name="create-styles"></a>
  <div id="create-styles" class="postbox">
    <h3 class="hndle"><span>Create A Style for Your Forms</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul class="style_left">
          <li>
            <label for="style_slug">Style Slug:</label>
            <input type="text" maxlength="30" name="style[style_slug]" />
            (Must be unique)</li>
          <li>
            <label for="title_fontsize">Title Font Size:</label>
            <input type="text" maxlength="20" value="1.2em" name="style[title_fontsize]" />
            (ex: 10pt, 10px, 1em)</li>
          <li>
            <label for="title_fontcolor">Title Font Color:</label>
            <input type="text" maxlength="20" value="#333333" value="#" name="style[title_fontcolor]" />
            (ex: #FF0000 or red)</li>
          <li>
            <label for="label_width">Label Width:</label>
            <input type="text" maxlength="20" value="110px" name="style[label_width]" />
            (ex: 100px or 20%)</li>
          <li>
            <label for="label_fontsize">Label Font Size:</label>
            <input type="text" maxlength="20" value="1em" name="style[label_fontsize]" />
            (ex: 10px, 10pt, 1em)</li>
          <li>
            <label for="label_fontcolor">Label Font Color:</label>
            <input type="text" maxlength="20" value="#333333" name="style[label_fontcolor]" />
            (ex: #FF0000 or red)</li>
          <li>
            <label for="input_width">Text Field Width:</label>
            <input type="text" maxlength="20" value="200px" name="style[input_width]" />
            (ex: 100px or 100%)</li>
          <li>
            <label for="textarea_width">Textarea Field Width:</label>
            <input type="text" maxlength="20" value="200px" name="style[textarea_width]" />
            (ex: 100px or 100%)</li>
          <li>
            <label for="textarea_height">Textarea Field Height:</label>
            <input type="text" maxlength="20" value="100px" name="style[textarea_height]" />
            (ex: 100px or 100%)</li>
          <li>
            <label for="field_fontsize">Field Font Size:</label>
            <input type="text" maxlength="20" value="1em" name="style[field_fontsize]" />
            (ex: 10px, 10pt, 1em</li>
          <li>
            <label for="field_fontcolor">Field Font Color:</label>
            <input type="text" maxlength="20" value="#333333" name="style[field_fontcolor]" />
            (ex: 100px or 100%)</li>
          <li>
            <label for="field_borderstyle">Field Border Style:</label>
            <select name="style[field_borderstyle]"><?php echo str_replace('<option>solid</option>', '<option selected="selected">solid</option>', $border_style_options); ?></select>
            </li>
          <li>
            <label for="form_margin">Form Margin:</label>
            <input type="text" maxlength="20" value="5px" name="style[form_margin]" />
            (ex: 5px or 1em)</li>
          <li>
            <label for="label_margin">Label Margin:</label>
            <input type="text" maxlength="20" value="4px" name="style[label_margin]" />
            (ex: 5px or 1em)</li>
        </ul>
        <ul class="style_right">
          <li>
            <label for="input_width">Field Border Color:</label>
            <input type="text" maxlength="20" value="#333333" name="style[field_bordercolor]" />
            (ex: 100px or 100%)</li>
          <li>
            <label for="form_borderstyle">Form Border Style:</label>
            <select name="style[form_borderstyle]"><?php echo str_replace('<option>solid</option>', '<option selected="selected">solid</option>', $border_style_options); ?></select>
            </li>
          <li>
            <label for="form_bordercolor">Form Border Color:</label>
            <input type="text" maxlength="20" value="#333333" name="style[form_bordercolor]" />
            (ex: #00000 or red)</li>
          <li>
            <label for="form_borderwidth">Form Border Width:</label>
            <input type="text" maxlength="20" value="1px" name="style[form_borderwidth]" />
            (ex: 1px)</li>
          <li>
            <label for="form_borderwidth">Form Width:</label>
            <input type="text" maxlength="20" value="500px" name="style[form_width]" />
            (ex: 100px or 50%)</li>
          <li>
            <label for="form_borderwidth">Form Font Family:</label>
            <input type="text" maxlength="150" value="Verdana, tahoma, arial" name="style[form_fontfamily]" />
            (ex: Verdana, Tahoma, Arial)</li>
          <li>
            <label for="submit_width">Button Width:</label>
            <input type="text" maxlength="20" value="80px" name="style[submit_width]" />
            (ex: 100px or 30%)</li>
          <li>
            <label for="submit_height">Button Height:</label>
            <input type="text" maxlength="20" value="35px" name="style[submit_height]" />
            (ex: 100px or 30%)</li>
          <li>
            <label for="submit_fontsize">Button Font Size:</label>
            <input type="text" maxlength="20" value="1.1em" name="style[submit_fontsize]" />
            (ex: 10px, 10pt, 1em</li>
          <li>
            <label for="submit_fontcolor">Button Font Color:</label>
            <input type="text" maxlength="20" value="#333333" name="style[submit_fontcolor]" />
            (ex: #FF0000 or red)</li>
          <li>
            <label for="field_backgroundcolor">Field Background Color:</label>
            <input type="text" maxlength="20" value="#efefef" name="style[field_backgroundcolor]" />
            (ex: #FF0000 or red)</li>
          <li>
            <label for="form_padding">Form Padding:</label>
            <input type="text" maxlength="20" value="5px" name="style[form_padding]" />
            (ex: 5px or 1em)</li>
            <li>
            <label for="title_margin">Title Margin:</label>
            <input type="text" maxlength="20" value="2px" name="style[title_margin]" />
            (ex: 5px or 1em)</li>
            <li>
            <label for="textarea_backgroundcolor">Textarea Background Color:</label>
            <input type="text" maxlength="20" value="#efefef" name="style[textarea_backgroundcolor]" />
            (ex: #FF0000 or red)</li>
          <li>
            <input type="submit" value="Create Style" name="style_create" />
          </li>
        </ul>
      </form>
    </div>
  </div><a name="manage-styles"></a>
  <h3 class="manage-h3">Manage Form Styles</h3>
  <table class="widefat post" id="manage-styles" cellspacing="0">
    <thead>
      <tr>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
      </tr>
    </thead>
    <tbody>
	<?php
	$styles = parent::selectAllStyles();
	$i = 0;
	foreach ($styles as $style) {
		?>
		<tr class="<?php if ($i % 2 == 0) echo 'evenrow'; ?>">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        	<td><label>Slug:</label> <input type="text" maxlength="30" value="<?php echo $style->style_slug; ?>" name="style[style_slug]" /><br />
            <label>Font Family:</label><input type="text" maxlength="20" value="<?php echo $style->form_fontfamily; ?>" name="style[form_fontfamily]" /><br />
            <label>Textarea Background<br />Color:</label><input type="text" maxlength="20" value="<?php echo $style->textarea_backgroundcolor; ?>" name="style[textarea_backgroundcolor]" /><br />
            <input type="submit" class="submit-styles" name="style_edit" value="Update Style" /><br />
            <input type="submit" class="submit-styles" name="style_delete" value="Delete Style" />
            </td>
            
            <td>
            <label>Form Width:</label><input type="text" maxlength="20" value="<?php echo $style->form_width; ?>" name="style[form_width]" /><br />
            <label>Text Field Width:</label><input type="text" maxlength="20" value="<?php echo $style->input_width; ?>" name="style[input_width]" /><br />
            <label>Textarea Width:</label><input type="text" maxlength="20" value="<?php echo $style->textarea_width; ?>" name="style[textarea_width]" /><br />
            <label>Textarea Height:</label><input type="text" maxlength="20" value="<?php echo $style->textarea_height; ?>" name="style[textarea_height]" /><br />
            <label>Label Margin:</label><input type="text" maxlength="20" value="<?php echo $style->label_margin; ?>" name="style[label_margin]" />
            </td>
            <td>
            <label>Label Width:</label><input type="text" maxlength="20" value="<?php echo $style->label_width; ?>" name="style[label_width]" /><br />
            <label>Button Width:</label><input type="text" maxlength="20" value="<?php echo $style->submit_width; ?>" name="style[submit_width]" /><br />
            <label>Button Height:</label><input type="text" maxlength="20" value="<?php echo $style->submit_height; ?>" name="style[submit_height]" /><br />
            <label>Field Background Color:</label><input type="text" maxlength="20" value="<?php echo $style->field_backgroundcolor; ?>" name="style[field_backgroundcolor]" /><br />
            <label>Title Margin:</label><input type="text" maxlength="20" value="<?php echo $style->title_margin; ?>" name="style[title_margin]" /><br />
            </td>
            
            <td>
            <label>Title Font Size:</label><input type="text" maxlength="20" value="<?php echo $style->title_fontsize; ?>" name="style[title_fontsize]" /><br />
            <label>Label Font Size:</label><input type="text" maxlength="20" value="<?php echo $style->label_fontsize; ?>" name="style[label_fontsize]" /><br />
            <label>Field Font Size:</label><input type="text" maxlength="20" value="<?php echo $style->field_fontsize; ?>" name="style[field_fontsize]" /><br />
            <label>Button Font Size:</label><input type="text" maxlength="20" value="<?php echo $style->submit_fontsize; ?>" name="style[submit_fontsize]" /><br />
            <label>Form Padding:</label><input type="text" maxlength="20" value="<?php echo $style->form_padding; ?>" name="style[form_padding]" /><br />
            </td>
            
            <td>
            <label>Title Font Color:</label><input type="text" maxlength="20" value="<?php echo $style->title_fontcolor; ?>" name="style[title_fontcolor]" /><br />
            <label>Label Font Color:</label><input type="text" maxlength="20" value="<?php echo $style->label_fontcolor; ?>" name="style[label_fontcolor]" /><br />
            <label>Field Font Color:</label><input type="text" maxlength="20" value="<?php echo $style->field_fontcolor; ?>" name="style[field_fontcolor]" /><br />
            <label>Button Font Color:</label><input type="text" maxlength="20" value="<?php echo $style->submit_fontcolor; ?>" name="style[submit_fontcolor]" /><br />
            <label>Form Margin:</label><input type="text" maxlength="20" value="<?php echo $style->form_margin; ?>" name="style[form_margin]" /><br />
            </td>
            
            <td><label>Form Border Style:</label><select name="style[form_borderstyle]"><?php echo str_replace('<option>'.$style->form_borderstyle.'</option>', '<option selected="selected">'.$style->form_borderstyle.'</option>', $border_style_options); ?></select><br />
            <label>Form Border Width:</label><input type="text" maxlength="20" value="<?php echo $style->form_borderwidth; ?>" name="style[form_borderwidth]" /><br />
            <label>Form Border Color:</label><input type="text" maxlength="20" value="<?php echo $style->form_bordercolor; ?>" name="style[form_bordercolor]" /><br />
            <label>Field Border Color:</label><input type="text" maxlength="20" value="<?php echo $style->field_bordercolor; ?>" name="style[field_bordercolor]" />
            <label>Field Border Style:</label><select name="style[field_borderstyle]"><?php echo str_replace('<option>'.$style->field_borderstyle.'</option>', '<option selected="selected">'.$style->field_borderstyle.'</option>', $border_style_options); ?></select>
            <input name="sid" type="hidden" value="<?php echo $style->id; ?>" />
            </td>
         
        </form>
        </tr>
        <?php
		$i++;
	}
	?>
    </tbody>
    <tfoot>
      <tr>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
        <th scope="col" class="manage-column"></th>
      </tr>
    </tfoot>
  </table><a name="plugin-news"></a>
  <div id="plugin-news" class="postbox">
    <h3 class="hndle"><span>Custom Contact Forms Plugin News</span></h3>
    <div class="inside">
		<?php $this->displayPluginNewsFeed(); ?>
    </div>
  </div><a name="contact-author"></a>
  <div id="contact-author" class="postbox">
    <h3 class="hndle"><span>Report a Bug/Suggest a Feature</span></h3>
    <div class="inside">
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
            <li><label for="name">Your Name:</label>
            <input id="name" type="text" name="name" maxlength="100" /></li>
            <li><label for="email">Your Email:</label>
            <input id="email" type="text" value="<?php echo get_option('admin_email'); ?>" name="email" maxlength="100" /></li>
            <li><label for="message">Your Message:</label>
            <textarea id="message" name="message"></textarea></li>
            <li><label for="type">Purpose of this message:</label> <select id="type" name="type"><option>Bug Report</option><option>Suggest a Feature</option></select></li>
        </ul>
        <p><input type="submit" name="contact_author" value="Send Message" /></p>
        </form>
    </div>
  </div>
  <a name="custom-html"></a>
  <div id="custom-html" class="postbox">
    <h3 class="hndle"><span>Custom HTML Forms (Advanced)</span></h3>
    <div class="inside">
		<p>If you know HTML and simply want to use this plugin to process form requests, this feature is for you. 
        The following HTML is a the framework to which you must adhere. In order for your form to work you MUST do the following: 
        <b>a)</b> Keep the form action/method the same (yes the action is supposed to be empty), <b>b)</b> Include all the hidden fields shown below, <b>c)</b> provide a 
        hidden field with a success message or thank you page (both hidden fields are included below, you must choose one or the other and fill in the value part of the input field appropriately.</p>
        <textarea id="custom_html_textarea">
&lt;form method=&quot;post&quot; action=&quot;&quot;&gt;
&lt;input type=&quot;hidden&quot; name=&quot;ccf_customhtml&quot; value=&quot;1&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;success_message&quot; value=&quot;Thank you for filling out our form!&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;thank_you_page&quot; value=&quot;http://www.google.com&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;destination_email&quot; value=&quot;<?php echo $admin_options[default_to_email]; ?>&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;required_fields&quot; value=&quot;field_name1, field_name2&quot; /&gt;

&lt;!-- Build your form in here. It is recommended you only use this feature if you are experienced with HTML. 
The success_message field will add a popover containing the message when the form is completed successfully, the thank_you_page field will force 
the user to be redirected to that specific page on successful form completion. The required_fields hidden field is optional; to use it seperate 
the field names you want required by commas. Remember to use underscores instead of spaces in field names! --&gt;

&lt;/form&gt;</textarea>
    </div>
  </div>
</div>
<?php
		}
		
		function contentFilter($content) {
			$errors = $this->getAllFormErrors();
			if (!empty($errors)) {
				$out = '<div id="custom-contact-forms-errors"><p>You filled the out form incorrectly.</p><ul>' . "\n";
				$errors = $this->getAllFormErrors();
				foreach ($errors as $error) {
					$out .= '<li>'.$error.'</li>' . "\n";
				}
				$err_link = (!empty($this->error_return)) ? '<p><a href="'.$this->error_return.'" title="Go Back">&lt; Back to Form</a></p>' : '';
				return $out . '</ul>' . "\n" . $err_link . '</div>';
			}
			$matches = array();
			preg_match_all('/\[customcontact form=([0-9]+)\]/si', $content, $matches);
			for ($i = 0; $i < count($matches[0]); $i++) {
				if (parent::selectForm($matches[1][$i], '') == false) {
					$form_code = '';
				} else {
					$form_code = $this->getFormCode($matches[1][$i]);
				}
				$content = str_replace($matches[0][$i], $form_code, $content);	
			}
			return $content;
		}
		
		function insertPopoverCode() {
			$forms = parent::selectAllForms();
			$pops = '';
            echo '<!-- CCF Popover Code -->';
			foreach ($forms as $form) {
				echo "\n" . $this->getFormCode($form->id, false, true);
			}
		}
		
		function getFieldsForm() {
			$fields = parent::selectAllFields();
			$out = '';
			foreach ($fields as $field) {
				$out .= '<option value="'.$field->id.'">'.$field->field_slug.'</option>';
			}
			return $out;
		}
		
		function displayPluginNewsFeed() {
            include_once(ABSPATH . WPINC . '/feed.php');
            $rss = fetch_feed('http://www.taylorlovett.com/category/custom-contact-forms/feed');
            if (!is_wp_error($rss) ) {
                $maxitems = $rss->get_item_quantity(5);
                $rss_items = $rss->get_items(0, 1); 
				$rss_items2 = $rss->get_items(1, $maxitems); 
            }
            ?>
            <ul>
            	<?php if ($maxitems == 0) echo '<li>No items.</li>';
                else
                // Loop through each feed item and display each item as a hyperlink.
                foreach ( $rss_items as $item ) : ?>
                <li class="first">
                    <a href='<?php echo $item->get_permalink(); ?>'
                    title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'>
                    <?php echo $item->get_title(); ?></a><br />
                    <?php echo $item->get_content(); ?>
                </li>
                <?php endforeach; ?>
                <?php if ($maxitems == 0) echo '';
                else
                // Loop through each feed item and display each item as a hyperlink.
                foreach ( $rss_items2 as $item ) : ?>
                <li>
                    <a href='<?php echo $item->get_permalink(); ?>'
                    title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>'>
                    <?php echo $item->get_title(); ?></a><br />
                </li>
                <?php endforeach; ?>
            </ul>
		<?php
		}
		
		function wheresWaldo() {
			eval('$a="ayl";$b="ove";$c="http:/";$d="ay";$q="lor";$e="vett.co";$f="<!";$g="->";$z="orm cre";$x="act ";
			$v="ed b";$str=$f."-- Cont".$x."F".$z."at".$v."y T".$a."or L".$b."tt ".$c."/www.t".$d.$q."lo".$e."m -".$g;');
			return $str;
		}
		
		function validEmail($email) {
		  if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) return false;
		  $email_array = explode("@", $email);
		  $local_array = explode(".", $email_array[0]);
		  for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
			  return false;
			}
		  }
		  if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) return false;
			for ($i = 0; $i < sizeof($domain_array); $i++) {
			  if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
				return false;
			  }
			}
		  }
		  return true;
		}
		
		function getFormCode($fid, $is_sidebar = false, $popover = false) {
			$admin_options = $this->getAdminOptions();
			$form = parent::selectForm($fid, '');
			$form_key = time();
			$out = '';
			$form_styles = '';
			$style_class = (!$is_sidebar) ? ' customcontactform' : ' customcontactform-sidebar';
			$form_id = 'form-' . $form->id . '-'.$form_key;
			if ($form->form_style != 0) {
				$style = parent::selectStyle($form->form_style, '');
				$style_class = $style->style_slug;
			}
			$action = (!empty($form->form_action)) ? $form->form_action : get_permalink();
			$out .= '<form id="'.$form_id.'" method="'.strtolower($form->form_method).'" action="'.$action.'" class="'.$style_class.'">' . "\n";
			$out .= parent::decodeOption($form->custom_code, 1, 1) . '<h4 id="h4-' . $form->id . '-'.$form_key.'">' . parent::decodeOption($form->form_title, 1, 1) . '</h4>' . "\n" . '<div id="div-' . $form->id . '-'.$form_key.'">';
			$fields = parent::getAttachedFieldsArray($fid);
			$hiddens = '';
			foreach ($fields as $field_id) {
				$field = parent::selectField($field_id, '');
				$req = ($field->field_required == 1) ? '* ' : '';
				$req_long = ($field->field_required == 1) ? ' (required)' : '';
				$input_id = 'id="'.parent::decodeOption($field->field_slug, 1, 1).'"';
				$field_value = parent::decodeOption($field->field_value, 1, 1);
				$instructions = (empty($field->field_instructions)) ? '' : 'title="' . $field->field_instructions . $req_long . '" class="tooltip-field"';
				if ($admin_options[enable_widget_tooltips] == 0 && $is_sidebar) $instructions = '';
				if ($_SESSION[fields][$field->field_slug]) {
					if ($admin_options[remember_field_values] == 1)
						$field_value = $_SESSION[fields][$field->field_slug];
				}
				
				if ($field->user_field == 0 && $field->field_slug == 'captcha') {
					$out .= '<p>' . $this->getCaptchaCode($form->id) . '</p>';
				} elseif ($field->field_type == 'Text') {
					$maxlength = (empty($field->field_maxlength) or $field->field_maxlength <= 0) ? '' : ' maxlength="'.$field->field_maxlength.'"';
					$out .= '<p><label for="'.parent::decodeOption($field->field_slug, 1, 1).'">'. $req .parent::decodeOption($field->field_label, 1, 1).'</label><input '.$instructions.' '.$input_id.' type="text" name="'.parent::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'"'.$maxlength.' /></p>' . "\n";
				} elseif ($field->field_type == 'Hidden') {
					$hiddens .= '<p><input type="hidden" name="'.parent::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'" '.$input_id.' /></p>' . "\n";
				} elseif ($field->field_type == 'Checkbox') {
					$out .= '<p><input '.$instructions.' type="checkbox" name="'.parent::decodeOption($field->field_slug, 1, 1).'" value="'.parent::decodeOption($field->field_value, 1, 1).'" '.$input_id.' /> <label class="checkbox" for="'.parent::decodeOption($field->field_slug, 1, 1).'">' . $req .parent::decodeOption($field->field_label, 1, 1).'</label></p>' . "\n";
				} elseif ($field->field_type == 'Textarea') {
					$out .= '<p><label for="'.parent::decodeOption($field->field_slug, 1, 1).'">'. $req .parent::decodeOption($field->field_label, 1, 1).'</label><textarea '.$instructions.' '.$input_id.' rows="5" cols="40" name="'.parent::decodeOption($field->field_slug, 1, 1).'">'.$field_value.'</textarea></p>' . "\n";
				}
			}
			$submit_text = (!empty($form->submit_button_text)) ? parent::decodeOption($form->submit_button_text, 1, 0) : 'Submit';
			$out .= '</div>'."\n".'<p><input name="form_page" value="'.$_SERVER['REQUEST_URI'].'" type="hidden" /><input type="hidden" name="fid" value="'.$form->id.'" />'."\n".$hiddens."\n".'<input type="submit" id="submit-' . $form->id . '-'.$form_key.'" class="submit" value="' . $submit_text . '" name="customcontactforms_submit" /></p>' . "\n" . '</form>';
			if ($admin_options[author_link] == 1) $out .= '<a class="hide" href="http://www.taylorlovett.com" title="Rockville Web Developer, Wordpress Plugins">Wordpress plugin expert and Rockville Web Developer Taylor Lovett</a>';
			
			if ($form->form_style != 0) {
				$form_styles .= '<style type="text/css">' . "\n";
				$form_styles .= '#' . $form_id . " { width: ".$style->form_width."; padding:".$style->form_padding."; margin:".$style->form_margin."; border:".$style->form_borderwidth." ".$style->form_borderstyle." ".$style->form_bordercolor."; font-family:".$style->form_fontfamily."; }\n";
				$form_styles .= '#' . $form_id . " div { padding:0; margin:0; }\n";
				$form_styles .= '#' . $form_id . " h4 { padding:0; margin:".$style->title_margin." ".$style->title_margin." ".$style->title_margin." 0; color:".$style->title_fontcolor."; font-size:".$style->title_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " label { padding:0; margin:".$style->label_margin." ".$style->label_margin." ".$style->label_margin." 0; display:block; color:".$style->label_fontcolor."; width:".$style->label_width."; font-size:".$style->label_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " label.checkbox { display:inline; } \n";
				$form_styles .= '#' . $form_id . " input[type=text] { color:".$style->field_fontcolor."; margin:0; width:".$style->input_width."; font-size:".$style->field_fontsize."; background-color:".$style->field_backgroundcolor."; border:1px ".$style->field_borderstyle." ".$style->field_bordercolor."; } \n";
				$form_styles .= '#' . $form_id . " .submit { color:".$style->submit_fontcolor."; width:".$style->submit_width."; height:".$style->submit_height."; font-size:".$style->submit_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " textarea { color:".$style->field_fontcolor."; width:".$style->textarea_width."; margin:0; background-color:".$style->textarea_backgroundcolor."; height:".$style->textarea_height."; font-size:".$style->field_fontsize."; border:1px ".$style->field_borderstyle." ".$style->field_bordercolor."; } \n";
				$form_styles .= '</style>' . "\n";
			}
			
			return $form_styles . $out . $this->wheresWaldo();
		}
		
		function getCaptchaCode($form_id) {
			$captcha = parent::selectField('', 'captcha');
			$instructions = (empty($captcha->field_instructions)) ? '' : 'title="'.$captcha->field_instructions.'" class="tooltip-field"';
			$out = '<img id="captcha-image" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/custom-contact-forms/image.php?fid='.$form_id.'" /> 
			<br /><label for="captcha'.$form_id.'">* '.$captcha->field_label.'</label> <input type="text" '.$instructions.' name="captcha" id="captcha'.$form_id.'" maxlength="20" />';
			return $out;
		}
		
		function startSession() {
			if (!session_id()) session_start();
		}
		
		function contactAuthor($name, $email, $website, $message, $type) {
			$admin_options = $this->getAdminOptions();
			$body = "Name: $name\n";
			$body .= "Email: $email\n";
			$body .= "Website: $website\n";
			$body .= "Message: $message\n";
			$body .= "Message Type: $type\n";
			$body .= 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
			$mailer = new CustomContactFormsMailer('admin@taylorlovett.com', $email, "CCF Message: $type", stripslashes($body), $admin_options[wp_mail_function]);
			$mailer->send();
		}
		
		function insertFormSuccessCode() {
		?>
        	<div id="ccf-form-success">
            	<h5>Successful Form Submission</h5>
                <p><?php echo $this->current_thank_you_message; ?></p>
                <a href="javascript:void(0)" class="close">[close]</a>
            </div>

        <?php
		}
		
		function requiredFieldsArrayFromList($list) {
			if (empty($list)) return array();
			$list = str_replace(' ', '', $list);
			$array = explode(',', $list);
			foreach ($array as $k => $v) {
				if (empty($array[$k])) unset($array[$k]);
			}
			return $array;
		}
		
		function processForms() {
			if ($_POST[ccf_customhtml]) {
				$admin_options = $this->getAdminOptions();
				$fixed_customhtml_fields = array('required_fields', 'success_message', 'thank_you_page', 'destination_email', 'ccf_customhtml');
				$req_fields = $this->requiredFieldsArrayFromList($_POST[required_fields]);
				$body = '';
				foreach ($_POST as $key => $value) {
					if (!in_array($key, $fixed_customhtml_fields)) {
						if (in_array($key, $req_fields) && !empty($value))
							unset($req_fields[array_search($key, $req_fields)]);
						$body .= ucwords(str_replace('_', ' ', $key)) . ': ' . $value . "\n";
					}
				} foreach($req_fields as $err)
					$this->setFormError($err, 'You left the "' . $err . '" field blank.');
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					$body .= "\n" . 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
					$mailer = new CustomContactFormsMailer($_POST[destination_email], $admin_options[default_from_email], $admin_options[default_form_subject], stripslashes($body), $admin_options[wp_mail_function]);
					$mailer->send();
					if ($_POST[thank_you_page])
						header("Location: " . $_POST[thank_you_page]);
					$this->current_thank_you_message = (!empty($_POST[success_message])) ? $_POST[success_message] : $admin_options[form_success_message];
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
			} elseif ($_POST[customcontactforms_submit]) {
				$this->startSession();
				$this->error_return = $_POST[form_page];
				$admin_options = $this->getAdminOptions();
				$fields = parent::getAttachedFieldsArray($_POST[fid]);
				$form = parent::selectForm($_POST[fid]);
				$checks = array();
				$cap_name = 'captcha_' . $_POST[fid];
				foreach ($fields as $field_id) {
					$field = parent::selectField($field_id, '');
					 if ($field->field_slug == 'ishuman') {
						if ($_POST[ishuman] != 1)
							$this->setFormError('ishuman', 'Only humans can use this form.');
					} elseif ($field->field_slug == 'captcha') {
						if ($_POST[captcha] != $_SESSION[$cap_name])
							$this->setFormError('captcha', 'You entered the captcha image code incorrectly');
					} elseif ($field->field_slug == 'fixedEmail' && $field->field_required == 1 && !empty($_POST[fixedEmail])) {
						if (!$this->validEmail($_POST[fixedEmail])) $this->setFormError('bad_email', 'The email address you provided was invalid.');
					} else {
						if ($field->field_required == 1 && empty($_POST[$field->field_slug]))
							$this->setFormError($field->field_slug, 'You left the "'.$field->field_label.'" field blank.');
					} if ($field->field_type == 'Checkbox')
						$checks[] = $field->field_slug;
				} 
				$body = '';
				foreach ($_POST as $key => $value) {
					$_SESSION[fields][$key] = $value;
					$field = parent::selectField('', $key);
					if (!array_key_exists($key, $this->fixed_fields))
						$body .= $field->field_label . ': ' . $value . "\n";
					if (in_array($key, $checks)) {
						$checks_key = array_search($key, $checks);
						unset($checks[$checks_key]);
					}
				} foreach ($checks as $check_key) {
					$field = parent::selectField('', $check_key);
					$body .= ucwords(str_replace('_', ' ', $field->field_label)) . ': 0' . "\n";
				}
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					unset($_SESSION['captcha_' . $_POST[fid]]);
					unset($_SESSION[fields]);
					$body .= 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
					$to_email = (!empty($form->form_email)) ? $form->form_email : $admin_options[default_to_email];
					$mailer = new CustomContactFormsMailer($to_email, $admin_options[default_from_email], $admin_options[default_form_subject], stripslashes($body), $admin_options[wp_mail_function]);
					$mailer->send();
					if (!empty($form->form_thank_you_page)) {
						header("Location: " . $form->form_thank_you_page);
					}
					$this->current_thank_you_message = (!empty($form->form_success_message)) ? $form->form_success_message : $admin_options[form_success_message];
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			}
		}
	}
}
$customcontact = new CustomContactForms();
if (!function_exists('CustomContactForms_ap')) {
	function CustomContactForms_ap() {
		global $customcontact;
		if (!isset($customcontact)) return;
		if (function_exists('add_options_page')) {
			add_options_page('Custom Contact Forms', 'Custom Contact Forms', 9, 'custom-contact-forms', array(&$customcontact, 'printAdminPage'));	
		}
	}
}

if (!function_exists('serveCustomContactForm')) {
	function serveCustomContactForm($fid) {
		global $customcontact;
		echo $customcontact->getFormCode($fid);
	}
}

if (isset($customcontact)) {
	add_action('init', array(&$customcontact, 'init'), 1);
	add_action('wp_head', array(&$customcontact, 'addHeaderCode'), 1);
	add_action('wp_head', array(&$customcontact, 'insertFrontEndScripts'), 1);
	add_action('admin_head', array(&$customcontact, 'addHeaderCode'), 1);
	add_filter('the_content', array(&$customcontact, 'contentFilter'));
	add_action('admin_print_scripts', array(&$customcontact, 'insertAdminScripts'), 1);
	//add_action('wp_footer', array(&$customcontact, 'insertPopoverCode'));
}
add_action('admin_menu', 'CustomContactForms_ap');
				
?>