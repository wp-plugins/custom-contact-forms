<?php
/*
	Plugin Name: Custom Contact Forms
	Plugin URI: http://taylorlovett.com/wordpress-plugins
	Description: Custom Contact Forms is a plugin for handling and displaying custom web forms [customcontact form=1] in any page, post, category, or archive in which you want the form to show. This plugin allows you to create fields with a variety of options and to attach them to specific forms you create; definitely allows for more customization than any other Wordpress Contact Form plugin out there today. Also comes with a web form widget to drag-and-drop in to your sidebar. <a href="options-general.php?page=custom-contact-forms" title="Maryland Wordpress Developer">Plugin Settings</a>
	Version: 1.0.0
	Author: <a href="http://www.taylorlovett.com" title="Maryland Wordpress Developer">Taylor Lovett</a>
	Author URI: http://www.taylorlovett.com
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

require('custom-contact-forms-db.php');
require_once('custom-contact-forms-mailer.php');
if (!class_exists('CustomContactForms')) {
	class CustomContactForms extends CustomContactFormsDB {
		var $adminOptionsName = 'customContactFormsAdminOptions';
		var $widgetOptionsName = 'widget_customContactForms';
		var $version = '1.0.0';
		
		function CustomContactForms() {
			parent::CustomContactFormsDB();
		}
		
		function getAdminOptions() {
			$admin_email = get_option('admin_email');
			$customcontactAdminOptions = array('show_widget_home' => 1, 'show_widget_pages' => 1, 'show_widget_singles' => 1, 'show_widget_categories' => 1, 'show_widget_archives' => 1, 'default_to_email' => $admin_email, 'default_from_email' => $admin_email, 'default_form_subject' => 'Someone Filled Out Your Contact Form!', 'default_thank_you' => ''); // defaults
			$customcontactOptions = get_option($this->adminOptionsName);
			if (!empty($customcontactOptions)) {
				foreach ($customcontactOptions as $key => $option)
					$customcontactAdminOptions[$key] = $option;
			}
			update_option($this->adminOptionsName, $customcontactAdminOptions);
			return $customcontactAdminOptions;
		}
		function init() {
			$this->getAdminOptions();
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
			echo $before_widget . $this->getFormCode($option[widget_form_id], true, $args) . $after_widget;
		}
		function addHeaderCode() {
			?>
<!-- WP Infusionsoft -->
<link rel="stylesheet" href="<?php echo get_option('siteurl'); ?>/wp-content/plugins/custom-contact-forms/custom-contact-forms.css" type="text/css" media="screen" />
<?php	
		}
		function printAdminPage() {
			parent::encodeOption('sfsfd');
			$admin_options = $this->getAdminOptions();
			if ($_POST[form_create]) {
				parent::insertForm($_POST[form_slug], $_POST[form_title], $_POST[form_action], $_POST[form_method], $_POST[submit_button_text], $_POST[custom_code]);
			} elseif ($_POST[field_create]) {
				parent::insertField($_POST[field_slug], $_POST[field_label], $_POST[field_type], $_POST[field_value], $_POST[field_maxlength]);
			} elseif ($_POST[general_settings]) {
				$admin_options[default_to_email] = $_POST[default_to_email];
				$admin_options[default_from_email] = $_POST[default_from_email];
				$admin_options[default_form_subject] = $_POST[default_form_subject];
				$admin_options[show_widget_categories] = $_POST[show_widget_categories];
				$admin_options[show_widget_singles] = $_POST[show_widget_singles];
				$admin_options[show_widget_pages] = $_POST[show_widget_pages];
				$admin_options[show_widget_archives] = $_POST[show_widget_archives];
				$admin_options[show_widget_home] = $_POST[show_widget_home];
				$admin_options[default_thank_you] = $_POST[default_thank_you];
				update_option($this->adminOptionsName, $admin_options);
			} elseif ($_POST[field_edit]) {
				parent::updateField($_POST[field_slug], $_POST[field_label], $_POST[field_type], $_POST[field_value], $_POST[field_maxlength], $_POST[fid]);
			} elseif ($_POST[field_delete]) {
				parent::deleteField($_POST[fid]);
			} elseif ($_POST[form_delete]) {
				parent::deleteForm($_POST[fid]);
			} elseif ($_POST[form_edit]) {
				parent::updateForm($_POST[form_slug], $_POST[form_title], $_POST[form_action], $_POST[form_method], $_POST[submit_button_text], $_POST[custom_code], $_POST[fid]);
			} elseif ($_POST[form_add_field]) {
				parent::addFieldToForm($_POST[field_id], $_POST[fid]);
			} elseif ($_POST[disattach_field]) {
				parent::disattachField($_POST[disattach_field_id], $_POST[fid]);
			}
			?>
<div id="customcontactforms-admin">
  <div id="icon-themes" class="icon32"></div>
  <h2>Custom Contact Forms</h2>
  <div id="create-fields" class="postbox">
    <h3 class="hndle"><span>Create A Form Field</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
          <li>
            <label for="field_slug">* Slug (Name):</label>
            <input name="field_slug" type="text" maxlength="50" />
            (Must be unique)</li>
          <li>
            <label for="field_label">Field Label:</label>
            <input name="field_label" type="text" maxlength="100" />
          </li>
          <li>
            <label for="field_type">* Field Type:</label>
            <select name="field_type">
              <option>Text</option>
              <option>Textarea</option>
              <option>Hidden</option>
              <option>Checkbox</option>
            </select>
          </li>
          <li>
            <label for="field_value">Initial Value:</label>
            <input name="field_value" type="text" maxlength="50" />
          </li>
          <li>
            <label for="field_maxlength">Max Length:</label>
            <input class="width50" size="10" name="field_maxlength" type="text" maxlength="4" />
            (0 for no limit; only applies to Text fields)</li>
          <li>
            <input type="submit" value="Create Field" name="field_create" />
          </li>
        </ul>
      </form>
    </div>
  </div>
  <div id="create-forms" class="postbox">
    <h3 class="hndle"><span>Create A Form</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
          <li>
            <label for="form_name">Form Slug:</label>
            <input type="text" maxlength="100" name="form_slug" />
            (Must be unique)</li>
          <li>
            <label for="form_title">Form Title:</label>
            <input type="text" maxlength="200" name="form_title" />
            (The form header text)</li>
          <li>
            <label for="form_method">Form Method:</label>
            <select name="form_method">
              <option>Post</option>
              <option>Get</option>
            </select>
            (If unsure, leave as is.)</li>
          <li>
            <label for="form_action">Form Action:</label>
            <input type="text" name="form_action" value="" />
            (If unsure, leave blank.)</li>
          <li>
            <label for="submit_button_text">Submit Button Text:</label>
            <input type="text" maxlength="200" name="submit_button_text" />
          </li>
          <li>
            <label for="custom_code">Custom Code:</label>
            <input type="text" name="custom_code" />
            (If unsure, leave blank.)</li>
          <li>
            <input type="submit" value="Create Form" name="form_create" />
          </li>
        </ul>
      </form>
    </div>
  </div>
  <h3 class="manage-h3">Manage Fields</h3>
  <table class="widefat post" id="manage-fields" cellspacing="0">
    <thead>
      <tr>
        <th scope="col" class="manage-column field-slug">Slug</th>
        <th scope="col" class="manage-column field-label">Label</th>
        <th scope="col" class="manage-column field-type">Type</th>
        <th scope="col" class="manage-column field-value">Initial Value</th>
        <th scope="col" class="manage-column field-maxlength">Maxlength</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
                $fields = parent::selectAllFields();
                for ($i = 0; $i < count($fields); $i++) {
                    $field_types = '<option>Text</option><option>Textarea</option><option>Hidden</option><option>Checkbox</option>';
                    $field_types = str_replace('<option>'.$fields[$i]->field_type.'</option>',  '<option selected="selected">'.$fields[$i]->field_type.'</option>', $field_types);
                    
                ?>
      <tr<?php if ($i % 2 == 0) echo ' class="evenrow"'; ?>>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
          <td><input type="text" name="field_slug" maxlength="50" value="<?php echo $fields[$i]->field_slug; ?>" /></td>
          <td><input type="text" name="field_label" maxlength="100" value="<?php echo $fields[$i]->field_label; ?>" /></td>
          <td><select name="field_type">
              <?php echo $field_types; ?>
            </select></td>
          <td><input type="text" name="field_value" maxlength="50" value="<?php echo $fields[$i]->field_value; ?>" /></td>
          <td><input type="text" class="width50" name="field_maxlength" value="<?php echo $fields[$i]->field_maxlength; ?>" /></td>
          <td><input type="hidden" name="fid" value="<?php echo $fields[$i]->id; ?>" />
            <input type="submit" name="field_edit" value="Edit" />
            <input type="submit" name="field_delete" value="Delete" /></td>
        </form>
      </tr>
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
        <th scope="col" class="manage-column field-maxlength">Maxlength</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </tfoot>
  </table>
  <h3 class="manage-h3">Manage Forms</h3>
  <table class="widefat post" id="manage-fields" cellspacing="0">
    <thead>
      <tr>
        <th scope="col" class="manage-column form-slug">Slug</th>
        <th scope="col" class="manage-column form-title">Title</th>
        <th scope="col" class="manage-column form-method">Method</th>
        <th scope="col" class="manage-column form-action">Form Action</th>
        <th scope="col" class="manage-column form-submit">Button Text</th>
        <th scope="col" class="manage-column form-submit">Custom Code</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php
                $forms = parent::selectAllForms();
                for ($i = 0; $i < count($forms); $i++) {
                    $form_methods = '<option>Post</option><option>Get</option>';
                    $form_methods = str_replace('<option>'.$forms[$i]->form_method.'</option>',  '<option selected="selected">'.$forms[$i]->form_method.'</option>', $form_methods);
                    $add_fields = $this->getFieldsForm();
                ?>
      <tr class="<?php if ($i % 2 == 0) echo 'evenrow'; ?>">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
          <td><input type="text" class="width75" name="form_slug" value="<?php echo $forms[$i]->form_slug; ?>" /></td>
          <td><input type="text" class="width125" name="form_title" value="<?php echo $forms[$i]->form_title; ?>" /></td>
          <td><select name="form_method">
              <?php echo $form_methods; ?>
            </select></td>
          <td><input class="width125" type="text" name="form_action" value="<?php echo $forms[$i]->form_action; ?>" /></td>
          <td><input class="width125" type="text" name="submit_button_text" value="<?php echo $forms[$i]->submit_button_text; ?>" /></td>
          <td><input type="text" class="width125" name="custom_code" value="<?php echo $forms[$i]->custom_code; ?>" /></td>
          <td style="text-align:right"><input type="hidden" name="fid" value="<?php echo $forms[$i]->id; ?>" />
            <input type="submit" name="form_edit" value="Edit" />
            <input type="submit" name="form_delete" value="Delete" /></td>
        </form>
      </tr>
      <tr class="<?php if ($i % 2 == 0) echo 'evenrow'; ?>">
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
          <td colspan="7"><div class="attached_fields">
              <label><span>Attached Fields:</span></label>
              <?php
                    $attached_fields = parent::getAttachedFieldsArray($forms[$i]->id);
                    if (empty($attached_fields)) echo 'None ';
                    else {
                        echo '<select name="disattach_field_id">';
                        foreach($attached_fields as $attached_field) {
                            $this_field = parent::selectField($attached_field, '');
                            echo $this_field->field_slug . ' <option value="'.$this_field->id.'">'.$this_field->field_slug.'</option>';
                            ?>
              <?php
                        }
                        echo '</select> <input type="submit" value="Disattach Field" name="disattach_field" />';
                    }
                    ?>
              <br />
              <span class="red bold">*</span> Code to Display Form: <b>[customcontact form=<?php echo $forms[$i]->id ?>]</b> </div>
            <div class="attach_field">
              <label for="field_id"><span>Attach Field:</span></label>
              <select name="field_id">
                <?php echo $add_fields; ?>
              </select>
              <input type="submit" name="form_add_field" value="Attach" />
              <input type="hidden" name="fid" value="<?php echo $forms[$i]->id; ?>" /><br />
              <span class="red bold">*</span> Attach in the order you want fields to display.
            </div></td>
        </form>
      </tr>
      <?php
                }
                ?>
    </tbody>
    <tfoot>
      <tr>
      <tr>
        <th scope="col" class="manage-column form-slug">Slug</th>
        <th scope="col" class="manage-column form-title">Title</th>
        <th scope="col" class="manage-column form-method">Method</th>
        <th scope="col" class="manage-column form-action">Form Action</th>
        <th scope="col" class="manage-column form-submit">Button Text</th>
        <th scope="col" class="manage-column form-submit">Custom Code</th>
        <th scope="col" class="manage-column field-action">Action</th>
      </tr>
        </tr>
      
    </tfoot>
  </table>
  <div id="general-settings" class="postbox">
    <h3 class="hndle"><span>General Settings</span></h3>
    <div class="inside">
      <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <ul>
          <li>
            <label for="default_to_email">Default Email:</label>
            <input name="default_to_email" value="<?php echo $admin_options[default_to_email]; ?>" type="text" maxlength="100" />
          </li>
          <li class="descrip">Form emails will be sent <span>to</span> this address.</li>
          <li>
            <label for="default_from_email">Default From Email:</label>
            <input name="default_from_email" value="<?php echo $admin_options[default_from_email]; ?>" type="text" maxlength="100" />
          </li>
          <li class="descrip">Form emails will be sent <span>from</span> this address.</li>
          <li>
            <label for="default_form_subject">Default Email Subject:</label>
            <input name="default_form_subject" value="<?php echo $admin_options[default_form_subject]; ?>" type="text" maxlength="150" />
          </li>
          <li class="descrip">Default subject to be included in all form emails.</li>
          <li>
            <label for="default_thank_you">Default Thank You Page:</label>
            <input name="default_thank_you" value="<?php echo $admin_options[default_thank_you]; ?>" type="text" maxlength="150" />
          </li>
          <li class="descrip">Leaving this blank will bring visitors back to where they filled out the form.</li>
          <li class="show-widget">Show Sidebar Widget:</li>
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
  </div>
  <div id="instructions" class="postbox">
    <h3 class="hndle"><span>Instructions</span></h3>
    <div class="inside">
      <p>1. Create a form.</p>
      <p>2. Create fields and attach those fields to the forms of your choice. <b>* Attach the fields in the order that you want them to show up in the form. If you mess up you can detach and reattach them.</b></p>
      <p>3. Display those forms in posts and pages by inserting the code: [customcontact form=<b>FORMID</b>]. Replace <b>FORMID</b> with the id listed to the left of the form slug next to the form of your choice above.</p>
      <p>4. Add a form to your sidebar, by dragging the Custom Contact Form widget in to your sidebar.</p>
      <p>5. Configure the General Settings appropriately; this is important if you want to receive your web form messages!</p>
    </div>
  </div>
</div>
<?php
		}
		
		function contentFilter($content) {
			$matches = array();
			preg_match_all('/\[customcontact form=([0-9]+)\]/si', $content, $matches);
			for ($i = 0; $i < count($matches[0]); $i++) {
				if (parent::selectForm($matches[1][$i], '') == false) {
					$form_code = '';
				} else {
					$form_code = $this->getFormCode($matches[1][$i], false, '');
				}
				$content = str_replace($matches[0][$i], $form_code, $content);	
			}
			return $content;
		}
		
		function getFieldsForm() {
			$fields = parent::selectAllFields();
			$out = '';
			foreach ($fields as $field) {
				$out .= '<option value="'.$field->id.'">'.$field->field_slug.'</option>';
			}
			return $out;
		}
		
		function getFormCode($fid, $is_sidebar, $args) {
			if ($is_sidebar) extract($args);
			$form = parent::selectForm($fid, '');
			$class = (!$is_sidebar) ? 'customcontactform' : 'customcontactform-sidebar';
			$action = (!empty($form->form_action)) ? $form->form_action : get_permalink();
			$out = '<form method="'.strtolower($form->form_method).'" action="'.$action.'" class="'.$class.'">' . "\n";
			$out .= parent::decodeOption($form->custom_code, 1, 1) . '<h4>' . parent::decodeOption($form->form_title, 1, 1) . '</h4>' . "\n" . '<ul>';
			$fields = parent::getAttachedFieldsArray($fid);
			$hiddens = '';
			foreach ($fields as $field_id) {
				$field = parent::selectField($field_id, '');
				if ($field->field_type == 'Text') {
					$maxlength = (empty($field->field_maxlength) or $field->field_maxlength <= 0) ? '' : ' maxlength="'.$field->field_maxlength.'"';
					$out .= '<li><label for="'.parent::decodeOption($field->field_slug, 1, 1).'">'.parent::decodeOption($field->field_label, 1, 1).'</label><input type="text" name="'.parent::decodeOption($field->field_slug, 1, 1).'" value="'.parent::decodeOption($field->field_value, 1, 1).'"'.$maxlength.' /></li>' . "\n";
				} elseif ($field->field_type == 'Hidden') {
					$hiddens .= '<li><input type="hidden" name="'.parent::decodeOption($field->field_slug, 1, 1).'" value="'.parent::decodeOption($field->field_value, 1, 1).'" /></li>' . "\n";
				} elseif ($field->field_type == 'Checkbox') {
					$out .= '<li><input type="checkbox" name="'.parent::decodeOption($field->field_slug, 1, 1).'" value="'.parent::decodeOption($field->field_value, 1, 1).'" /> <label class="checkbox" for="'.parent::decodeOption($field->field_slug, 1, 1).'">'.parent::decodeOption($field->field_label, 1, 1).'</label></li>' . "\n";
				} elseif ($field->field_type == 'Textarea') {
					$out .= '<li><label for="'.parent::decodeOption($field->field_slug, 1, 1).'">'.parent::decodeOption($field->field_label, 1, 1).'</label><textarea name="'.parent::decodeOption($field->field_slug, 1, 1).'">'.parent::decodeOption($field->field_value, 1, 1).'</textarea></li>' . "\n";
				}
			}
			$out .= '</ul>'."\n".'<p><input type="hidden" name="fid" value="'.$form->id.'" />'."\n".$hiddens."\n".'<input type="submit" class="submit" value="' . parent::decodeOption($form->submit_button_text, 1, 0) . '" name="customcontactforms_submit" /></p>' . "\n" . '</form>';
			return $out;
		}
		
		function processForms() {
			if ($_POST[customcontactforms_submit]) {
				$admin_options = $this->getAdminOptions();
				$fields = parent::getAttachedFieldsArray($_POST[fid]);
				$checks = array();
				foreach ($fields as $field_id) {
					$field = parent::selectField($field_id, '');
					if ($field->field_type == 'Checkbox')
						$checks[] = $field->field_slug;
				} 
				$body = '';
				foreach ($_POST as $key => $value) {
					$field = parent::selectField('', $key);
					if ($key != 'customcontactforms_submit' && $key != 'fid')
						$body .= $field->field_label . ': ' . $value . "\n";
					if (in_array($key, $checks)) {
						$checks_key = array_search($key, $checks);
						unset($checks[$checks_key]);
					}
				} foreach ($checks as $check_key) {
					$field = parent::selectField('', $check_key);
					$body .= ucwords(str_replace('_', ' ', $field->field_label)) . ': 0' . "\n";
				}
				$body .= 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
				$mailer = new CustomContactFormsMailer($admin_options[default_to_email], $admin_options[default_from_email], $admin_options[default_form_subject], $body);
				$mailer->send();
				unset($_POST);
				if (!empty($admin_options[default_thank_you])) {
					header("Location: " . $admin_options[default_thank_you]);
				}
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
if (isset($customcontact)) {
	add_action('init', array(&$customcontact, 'processForms'), 1);
	add_action('wp_head', array(&$customcontact, 'addHeaderCode'), 1);
	add_action('admin_head', array(&$customcontact, 'addHeaderCode'), 1);
	add_action('activate_customcontactforms/customcontactforms.php', array(&$customcontact, 'init'));
	add_action('plugins_loaded', array(&$customcontact, 'init'), 1);
	add_filter('the_content', array(&$customcontact, 'contentFilter'));
}
add_action('admin_menu', 'CustomContactForms_ap');
?>
