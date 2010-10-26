<?php
/*
	Plugin Name: Custom Contact Forms
	Plugin URI: http://taylorlovett.com/wordpress-plugins
	Description: Guaranteed to be 1000X more customizable and intuitive than Fast Secure Contact Forms or Contact Form 7. Customize every aspect of your forms without any knowledge of CSS: borders, padding, sizes, colors. Ton's of great features. Required fields, form submissions saved to database, captchas, tooltip popovers, unlimited fields/forms/form styles, import/export, use a custom thank you page or built-in popover with a custom success message set for each form.
	Version: 4.0.0.b5
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
error_reporting(E_ALL ^ E_NOTICE);
require_once('custom-contact-forms-static.php');
CustomContactFormsStatic::definePluginConstants();
require_once('custom-contact-forms-db.php');
if (!class_exists('CustomContactForms')) {
	class CustomContactForms extends CustomContactFormsDB {
		var $adminOptionsName = 'customContactFormsAdminOptions';
		var $form_errors = array();
		var $error_return;
		var $current_form;
		var $current_thank_you_message;
		var $current_page = NULL;
		
		function CustomContactForms() {
			if (is_admin())
				$this->current_page = $_GET['page'];
		}
		
		function isPluginAdminPage() {
			return ($this->current_page == 'custom-contact-forms');
		}
		
		function activatePlugin() {
			$admin_options = $this->getAdminOptions();
			$admin_options['show_install_popover'] = 1;
			update_option($this->adminOptionsName, $admin_options);
			parent::createTables();
			parent::updateTables();
			parent::insertFixedFields();
		}
		
		function getAdminOptions() {
			$admin_email = get_option('admin_email');
			$customcontactAdminOptions = array('show_widget_home' => 1, 'show_widget_pages' => 1, 'show_widget_singles' => 1, 'show_widget_categories' => 1, 'show_widget_archives' => 1, 'default_to_email' => $admin_email, 'default_from_email' => $admin_email, 'default_form_subject' => __('Someone Filled Out Your Contact Form!', 'custom-contact-forms'), 
			'remember_field_values' => 0, 'author_link' => 1, 'enable_widget_tooltips' => 1, 'wp_mail_function' => 1, 'form_success_message_title' => __('Successful Form Submission', 'custom-contact-forms'), 'form_success_message' => __('Thank you for filling out our web form. We will get back to you ASAP.', 'custom-contact-forms'), 'enable_jquery' => 1, 'code_type' => 'XHTML',
			'show_install_popover' => 0, 'email_form_submissions' => 1, 'admin_ajax' => 1); // default general settings
			$customcontactOptions = get_option($this->adminOptionsName);
			if (!empty($customcontactOptions)) {
				foreach ($customcontactOptions as $key => $option)
					$customcontactAdminOptions[$key] = $option;
			}
			update_option($this->adminOptionsName, $customcontactAdminOptions);
			return $customcontactAdminOptions;
		}
		function init() {
			if (!is_admin()) {
				$this->startSession();
				$this->processForms();
			} else {
				$this->downloadExportFile();
				$this->runImport();
			}
		}
		
		function downloadExportFile() {
			if ($_POST['ccf_export']) {
				require_once('custom-contact-forms-export.php');
				$transit = new CustomContactFormsExport($this->adminOptionsName);
				$transit->exportAll();
				$file = $transit->exportToFile();
				wp_redirect(WP_PLUGIN_URL . '/custom-contact-forms/download.php?location=export/' . $file);
			}
		}
		
		function runImport() {
			if ($_POST['ccf_clear_import'] || $_POST['ccf_merge_import']) {
				require_once('custom-contact-forms-export.php');
				$transit = new CustomContactFormsExport($this->adminOptionsName);
				$settings['import_general_settings'] = ($_POST['ccf_import_overwrite_settings'] == 1) ? true : false;
				$settings['import_forms'] = ($_POST['ccf_import_forms'] == 1) ? true : false;
				$settings['import_fields'] = ($_POST['ccf_import_fields'] == 1) ? true : false;
				$settings['import_field_options'] = ($_POST['ccf_import_field_options'] == 1) ? true : false;
				$settings['import_styles'] = ($_POST['ccf_import_styles'] == 1) ? true : false;
				$settings['import_saved_submissions'] = ($_POST['ccf_import_saved_submissions'] == 1) ? true : false;
				$settings['mode'] = ($_POST['ccf_clear_import']) ? 'clear_import' : 'merge_import';
				$transit->importFromFile($_FILES['import_file'], $settings);
				wp_redirect('options-general.php?page=custom-contact-forms');
			}
		}
		
		function insertFrontEndStyles() {
            wp_register_style('CCFStandardsCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms-standards.css');
           	wp_register_style('CCFFormsCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms.css');
           	wp_enqueue_style('CCFStandardsCSS');
			wp_enqueue_style('CCFFormsCSS');
		}
		
		function insertBackEndStyles() {
            wp_register_style('CCFStandardsCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms-standards.css');
            wp_register_style('CCFAdminCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms-admin.css');
			wp_register_style('CCFColorPickerCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/colorpicker.css');
            wp_enqueue_style('CCFStandardsCSS');
			wp_enqueue_style('CCFAdminCSS');
			wp_enqueue_style('CCFColorPickerCSS');
		}
		
		function insertAdminScripts() {
			$admin_options = $this->getAdminOptions();
			?>
			<script type="text/javascript" language="javascript">
				var attaching = "<?php _e('Attaching', 'custom-contact-forms'); ?>";
				var dettaching = "<?php _e('Dettaching', 'custom-contact-forms'); ?>";
				var saving = "<?php _e('Saving', 'custom-contact-forms'); ?>";
				var more_options = "<?php _e('More Options', 'custom-contact-forms'); ?>";
				var expand = "<?php _e('Expand', 'custom-contact-forms'); ?>";
				var click_to_confirm = "<?php _e('Click to Confirm', 'custom-contact-forms'); ?>";
				var delete_confirm = "<?php _e('Are you sure you want to delete this', 'custom-contact-forms'); ?>";
				var error = "<?php _e('An error has occured. Please try again later.', 'custom-contact-forms'); ?>";
				var ccf_plugin_dir = "<?php echo WP_PLUGIN_URL . '/custom-contact-forms'; ?>";
				var ccf_file = "<?php echo get_option('siteurl') . '/wp-admin/options-general.php?page=custom-contact-forms'; ?>";
			</script>
			<?php
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('jquery-tools', WP_PLUGIN_URL . '/custom-contact-forms/js/jquery.tools.min.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'), '1.0');
			wp_enqueue_script('ccf-admin-inc', WP_PLUGIN_URL . '/custom-contact-forms/js/custom-contact-forms-admin-inc.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'), '1.0');
			wp_enqueue_script('ccf-admin', WP_PLUGIN_URL . '/custom-contact-forms/js/custom-contact-forms-admin.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'), '1.0');
			if ($admin_options['admin_ajax'] == 1)
				wp_enqueue_script('ccf-admin-ajax', WP_PLUGIN_URL . '/custom-contact-forms/js/custom-contact-forms-admin-ajax.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'), '1.0');
			wp_enqueue_script('ccf-colorpicker', WP_PLUGIN_URL . '/custom-contact-forms/js/colorpicker.js');
			wp_enqueue_script('ccf-eye', WP_PLUGIN_URL . '/custom-contact-forms/js/eye.js');
			wp_enqueue_script('ccf-utils', WP_PLUGIN_URL . '/custom-contact-forms/js/utils.js');
			wp_enqueue_script('ccf-layout', WP_PLUGIN_URL . '/custom-contact-forms/js/layout.js?ver=1.0.2');
			wp_enqueue_script('ccf-pagination', WP_PLUGIN_URL . '/custom-contact-forms/js/jquery.pagination.js');
		}
		
		function insertFrontEndScripts() {
			if (!is_admin()) { 
				$admin_options = $this->getAdminOptions();
				if ($admin_options['enable_jquery'] == 1) {
					wp_enqueue_script('jquery');
					wp_enqueue_script('jquery-tools', WP_PLUGIN_URL . '/custom-contact-forms/js/jquery.tools.min.js');
					wp_enqueue_script('ccf-main', WP_PLUGIN_URL . '/custom-contact-forms/js/custom-contact-forms.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-resizable'), '1.0');
				}
			}
		}
		
		function setFormError($key, $message) {
			$this->form_errors[$key] = $message;
		}
		
		function getFormError($key) {
			return $this->form_errors[$key];
		}
		
		function getAllFormErrors() {
			return $this->form_errors;
		}
		
		function insertUsagePopover() {
			require_once('custom-contact-forms-usage-popover.php');
		}
		
		function printAdminPage() {
			$admin_options = $this->getAdminOptions();
			if ($admin_options['show_install_popover'] == 1) {
				$admin_options['show_install_popover'] = 0;
				?>
                <script type="text/javascript" language="javascript">
					$j(document).ready(function() {
						showCCFUsagePopover();
					});
				</script>
                <?php
				update_option($this->adminOptionsName, $admin_options);
			} if ($_POST['form_create']) {
				parent::insertForm($_POST['form']);
			} elseif ($_POST['field_create']) {
				parent::insertField($_POST['field']);
			} elseif ($_POST['general_settings']) {
				$admin_options['default_to_email'] = $_POST['default_to_email'];
				$admin_options['default_from_email'] = $_POST['default_from_email'];
				$admin_options['default_form_subject'] = $_POST['default_form_subject'];
				$admin_options['show_widget_categories'] = $_POST['show_widget_categories'];
				$admin_options['show_widget_singles'] = $_POST['show_widget_singles'];
				$admin_options['show_widget_pages'] = $_POST['show_widget_pages'];
				$admin_options['show_widget_archives'] = $_POST['show_widget_archives'];
				$admin_options['show_widget_home'] = $_POST['show_widget_home'];
				$admin_options['custom_thank_you'] = $_POST['custom_thank_you'];
				$admin_options['email_form_submissions'] = $_POST['email_form_submissions'];
				$admin_options['author_link'] = $_POST['author_link'];
				$admin_options['admin_ajax'] = $_POST['admin_ajax'];
				$admin_options['enable_jquery'] = $_POST['enable_jquery'];
				$admin_options['code_type'] = $_POST['code_type'];
				$admin_options['form_success_message'] = $_POST['form_success_message'];
				$admin_options['form_success_message_title'] = $_POST['form_success_message_title'];
				$admin_options['wp_mail_function'] = $_POST['wp_mail_function'];
				$admin_options['enable_widget_tooltips'] = $_POST['enable_widget_tooltips'];
				$admin_options['remember_field_values'] = $_POST['remember_field_values'];
				update_option($this->adminOptionsName, $admin_options);
			} elseif ($_POST['field_edit']) {
				parent::updateField($_POST['field'], $_POST['fid']);
			} elseif ($_POST['field_delete']) {
				parent::deleteField($_POST['fid']);
			} elseif ($_POST['insert_default_content']) {
				parent::insertDefaultContent();
			} elseif ($_POST['form_delete']) {
				parent::deleteForm($_POST['fid']);
			} elseif ($_POST['form_edit']) {
				parent::updateForm($_POST['form'], $_POST['fid']);
			} elseif ($_POST['form_add_field']) {
				parent::addFieldToForm($_POST['attach_object_id'], $_POST['fid']);
			} elseif ($_POST['attach_field_option']) {
				parent::addFieldOptionToField($_POST['attach_object_id'], $_POST['fid']);
			} elseif ($_POST['dettach_field']) {
				parent::dettachField($_POST['dettach_object_id'], $_POST['fid']);
			} elseif ($_POST['dettach_field_option']) {
				parent::dettachFieldOption($_POST['dettach_object_id'], $_POST['fid']);
			}  elseif ($_POST['style_create']) {
				parent::insertStyle($_POST['style']);
			}  elseif ($_POST['style_edit']) {
				parent::updateStyle($_POST['style'], $_POST['sid']);
			}  elseif ($_POST['style_delete']) {
				parent::deleteStyle($_POST['sid']);
			} elseif ($_POST['contact_author']) {
				$this_url = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $_SERVER['SERVER_NAME'];
				$this->contactAuthor($_POST['name'], $_POST['email'], $this_url, $_POST['message'], $_POST['type']);
			} elseif ($_POST['delete_field_option']) {
				parent::deleteFieldOption($_POST['oid']);
			} elseif ($_POST['edit_field_option']) {
				parent::updateFieldOption($_POST['option'], $_POST['oid']);
			} elseif ($_POST['create_field_option']) {
				parent::insertFieldOption($_POST['option']);
			} elseif ($_POST['form_submission_delete']) {
				parent::deleteUserData($_POST['uid']);
			} elseif ($_POST['ajax_action']) {
				switch ($_POST['ajax_action']) {
					case 'delete':
						if (empty($_POST['object_id'])) exit;
						switch($_POST['object_type']) {
							case 'form':
								parent::deleteForm($_POST['object_id']);
							break;
							case 'field':
								parent::deleteField($_POST['object_id']);
							break;
							case 'field_option':
								parent::deleteFieldOption($_POST['object_id']);
							break;
							case 'form_submission':
								parent::deleteUserData($_POST['object_id']);
							break;
							case 'style':
								parent::deleteStyle($_POST['object_id']);
							break;
						}
					break;
					case 'create_field_option':
						parent::insertFieldOption($_POST['option']);
					break;
					case 'attach':
						switch ($_POST['object_type']) {
							case 'form':
								parent::addFieldToForm($_POST['attach_object_id'], $_POST['attach_to']);
							break;
							case 'field':
								parent::addFieldOptionToField($_POST['attach_object_id'], $_POST['attach_to']);
							break;
						}
					break;
					case 'dettach':
						//echo '<div style="margin-left:20px;">';
						//print_r($_POST);
						//echo '</div>';
						switch ($_POST['object_type']) {
							case 'form':
								parent::dettachField($_POST['dettach_object_id'], $_POST['dettach_from']);
							break;
							case 'field':
								parent::dettachFieldOption($_POST['dettach_object_id'], $_POST['dettach_from']);
							break;
						}
					break;
					case 'edit':
						if (empty($_POST['object_id'])) exit;
						switch($_POST['object_type']) {
							case 'form':
								if (!empty($_POST['form'])) parent::updateForm($_POST['form'], $_POST['object_id']);
							break;
							case 'field':
								if (!empty($_POST['field'])) parent::updateField($_POST['field'], $_POST['object_id']);
							break;
							case 'field_option':
								if (!empty($_POST['option'])) parent::updateFieldOption($_POST['option'], $_POST['object_id']);
							break;
							case 'style':
								if (!empty($_POST['style'])) parent::updateStyle($_POST['style'], $_POST['object_id']);
							break;
						}
					break;
				}
				exit;
			} elseif ($_GET['clear_tables'] == 1) {
				parent::emptyAllTables();
			}
			
			$styles = parent::selectAllStyles();
			$style_options = '<option value="0">Default</option>';
			foreach ($styles as $style)
				$style_options .= '<option value="'.$style->id.'">'.$style->style_slug.'</option>';
			// Insert plugin admin page XHTML
			require_once('custom-contact-forms-export.php');
			require_once('custom-contact-forms-admin.php');
		}
		
		function contentFilter($content) {
			$errors = $this->getAllFormErrors();
			if (!empty($errors)) {
				$out = '<div id="custom-contact-forms-errors"><p>'.__('You filled out the form incorrectly.', 'custom-contact-forms').'</p><ul>' . "\n";
				$errors = $this->getAllFormErrors();
				foreach ($errors as $error) {
					$out .= '<li>'.$error.'</li>' . "\n";
				}
				$err_link = (!empty($this->error_return)) ? '<p><a href="'.$this->error_return.'" title="Go Back">&lt; ' . __('Go Back to Form.', 'custom-contact-forms') . '</a></p>' : '';
				return $out . '</ul>' . "\n" . $err_link . '</div>';
			}
			$matches = array();
			preg_match_all('/\[customcontact form=([0-9]+)\]/si', $content, $matches);
			$matches_count = count($matches[0]);
			for ($i = 0; $i < $matches_count; $i++) {
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
				$out .= '<option value="'.$field->id.'">'.$field->field_slug.'</option>' . "\n";
			}
			return $out;
		}
		
		function getFieldOptionsForm() {
			$options = parent::selectAllFieldOptions();
			$out = '';
			foreach ($options as $option) {
				$out .= '<option value="'.$option->id.'">'.$option->option_slug.'</option>' . "\n";
			}
			return $out;
		}
		
		function displayPluginNewsFeed() {
            include_once(ABSPATH . WPINC . '/feed.php');
            $rss = @fetch_feed('http://www.taylorlovett.com/category/custom-contact-forms/feed');
			if (!is_wp_error($rss) ) {
                $maxitems = $rss->get_item_quantity(5);
                $rss_items = $rss->get_items(0, 1); 
				$rss_items2 = $rss->get_items(1, $maxitems); 
            }
            ?>
            <ul>
            	<?php if ($maxitems == 0) echo '<li>' . __('Nothing to show.', 'custom-contact-forms') . '</li>';
                else
                foreach ( $rss_items as $item ) : ?>
                <li class="first">
                    <a href='<?php echo $item->get_permalink(); ?>'
                    title='<?php echo __('Posted', 'custom-contact-forms'). ' '.$item->get_date('j F Y | g:i a'); ?>'>
                    <?php echo $item->get_title(); ?></a><br />
                    <?php echo $item->get_content(); ?>
                </li>
                <?php endforeach; ?>
                <?php if ($maxitems == 0) echo '';
                else
                foreach ( $rss_items2 as $item ) : ?>
                <li>
                    <a href='<?php echo $item->get_permalink(); ?>'
                    title='<?php echo __('Posted', 'custom-contact-forms') . ' '.$item->get_date('j F Y | g:i a'); ?>'>
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
		  } if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
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
			$form_title = CustomContactFormsStatic::decodeOption($form->form_title, 1, 1);
			$action = (!empty($form->form_action)) ? $form->form_action : $_SERVER['REQUEST_URI'];
			$out .= '<form id="'.$form_id.'" method="'.strtolower($form->form_method).'" action="'.$action.'" class="'.$style_class.'">' . "\n";
			$out .= CustomContactFormsStatic::decodeOption($form->custom_code, 1, 1) . "\n";
			if (!empty($form_title)) $out .= '<h4 id="h4-' . $form->id . '-' . $form_key . '">' . $form_title . '</h4>' . "\n";
			$fields = parent::getAttachedFieldsArray($fid);
			$hiddens = '';
			$code_type = ($admin_options['code_type'] == 'XHTML') ? ' /' : '';
			foreach ($fields as $field_id) {
				$field = parent::selectField($field_id, '');
				$add_reset = '';
				$req = ($field->field_required == 1 or $field->field_slug == 'ishuman') ? '* ' : '';
				$req_long = ($field->field_required == 1) ? ' ' . __('(required)', 'custom-contact-forms') : '';
				$input_id = 'id="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'-'.$form_key.'"';
				$field_value = CustomContactFormsStatic::decodeOption($field->field_value, 1, 1);
				$instructions = (empty($field->field_instructions)) ? '' : 'title="' . $field->field_instructions . $req_long . '" class="ccf-tooltip-field"';
				if ($admin_options['enable_widget_tooltips'] == 0 && $is_sidebar) $instructions = '';
				if ($_SESSION['fields'][$field->field_slug]) {
					if ($admin_options['remember_field_values'] == 1)
						$field_value = $_SESSION['fields'][$field->field_slug];
				} if ($field->user_field == 0) {
					if ($field->field_slug == 'captcha') {
						$out .= '<div>' . "\n" . $this->getCaptchaCode($form->id) . "\n" . '</div>' . "\n";
					} elseif ($field->field_slug == 'resetButton') {
						$add_reset = ' <input type="reset" '.$instructions.' class="reset-button '.$field->field_class.'" value="' . $field->field_value . '" />';
					}
				} elseif ($field->field_type == 'Text') {
					$maxlength = (empty($field->field_maxlength) or $field->field_maxlength <= 0) ? '' : ' maxlength="'.$field->field_maxlength.'"';
					$out .= '<div>'."\n".'<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<input class="'.$field->field_class.'" '.$instructions.' '.$input_id.' type="text" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'"'.$maxlength.''.$code_type.'>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Hidden') {
					$hiddens .= '<input type="hidden" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'" '.$input_id.''.$code_type.'>' . "\n";
				} elseif ($field->field_type == 'Checkbox') {
					$out .= '<div>'."\n".'<input class="'.$field->field_class.'" '.$instructions.' type="checkbox" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.CustomContactFormsStatic::decodeOption($field->field_value, 1, 1).'" '.$input_id.''.$code_type.'> '."\n".'<label class="checkbox" for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">' . $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Textarea') {
					$out .= '<div>'."\n".'<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<textarea class="'.$field->field_class.'" '.$instructions.' '.$input_id.' rows="5" cols="40" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'.$field_value.'</textarea>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Dropdown') {
					$field_options = '';
					$options = parent::getAttachedFieldOptionsArray($field->id);
					foreach ($options as $option_id) {
						$option = parent::selectFieldOption($option_id);
						$option_sel = ($field->field_value == $option->option_slug) ? ' selected="selected"' : '';
						$option_value = (!empty($option->option_value)) ? ' value="' . $option->option_value . '"' : '';
						$field_options .= '<option'.$option_sel.''.$option_value.'>' . $option->option_label . '</option>' . "\n";
					}
					if (!empty($options)) {
						if (!$is_sidebar) $out .= '<div>'."\n".'<select '.$instructions.' '.$input_id.' name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" class="'.$field->field_class.'">'."\n".$field_options.'</select>'."\n".'<label class="checkbox" for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'</div>' . "\n";
						else  $out .= '<div>'."\n".'<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<select class="'.$field->field_class.'" '.$instructions.' '.$input_id.' name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'."\n".$field_options.'</select>'."\n".'</div>' . "\n";
					}
				} elseif ($field->field_type == 'Radio') {
					$field_options = '';
					$options = parent::getAttachedFieldOptionsArray($field->id);
					foreach ($options as $option_id) {
						$option = parent::selectFieldOption($option_id);
						$option_sel = ($field->field_value == $option->option_slug) ? ' checked="checked"' : '';
						$field_options .= '<div><input'.$option_sel.' class="'.$field->field_class.'" type="radio" '.$instructions.' name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.CustomContactFormsStatic::decodeOption($option->option_value, 1, 1).'"'.$code_type.'> <label class="select" for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">' . CustomContactFormsStatic::decodeOption($option->option_label, 1, 1) . '</label></div>' . "\n";
					}
					$field_label = (!empty($field->field_label)) ? '<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>' : '';
					if (!empty($options)) $out .= '<div>'."\n".$field_label."\n".$field_options."\n".'</div>' . "\n";
				}
			}
			$submit_text = (!empty($form->submit_button_text)) ? CustomContactFormsStatic::decodeOption($form->submit_button_text, 1, 0) : 'Submit';
			$out .= '<input name="form_page" value="'.$_SERVER['REQUEST_URI'].'" type="hidden"'.$code_type.'>'."\n".'<input type="hidden" name="fid" value="'.$form->id.'"'.$code_type.'>'."\n".$hiddens."\n".'<input type="submit" id="submit-' . $form->id . '-'.$form_key.'" class="submit" value="' . $submit_text . '" name="customcontactforms_submit"'.$code_type.'>';
			if (!empty($add_reset)) $out .= $add_reset;
			$out .= "\n" . '</form>';
			if ($admin_options['author_link'] == 1) $out .= "\n".'<a class="ccf-hide" href="http://www.taylorlovett.com" title="Rockville Web Developer, Wordpress Plugins">Wordpress plugin expert and Rockville Web Developer Taylor Lovett</a>';
			
			if ($form->form_style != 0) {
				$no_border = array('', '0', '0px', '0%', '0pt', '0em');
				$round_border = (!in_array($style->field_borderround, $no_border)) ? '-moz-border-radius:'.$style->field_borderround.'; -khtml-border-radius:'.$style->field_borderround.'; -webkit-border-radius:'.$style->field_borderround.'; ' : '';
				$round_border_none = '-moz-border-radius:0px; -khtml-border-radius:0px; -webkit-border-radius:0px; ';
				$form_styles .= '<style type="text/css">' . "\n";
				$form_styles .= '#' . $form_id . " { width: ".$style->form_width."; text-align:left; padding:".$style->form_padding."; margin:".$style->form_margin."; border:".$style->form_borderwidth." ".$style->form_borderstyle." #".parent::formatStyle($style->form_bordercolor)."; background-color:#".parent::formatStyle($style->form_backgroundcolor)."; font-family:".$style->form_fontfamily."; } \n";
				$form_styles .= '#' . $form_id . " div { margin-bottom:6px; background-color:inherit; }\n";
				$form_styles .= '#' . $form_id . " div div { margin:0; background-color:inherit; padding:0; }\n";
				$form_styles .= '#' . $form_id . " h4 { padding:0; background-color:inherit; margin:".$style->title_margin." ".$style->title_margin." ".$style->title_margin." 0; color:#".parent::formatStyle($style->title_fontcolor)."; font-size:".$style->title_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " label { padding:0; background-color:inherit; margin:".$style->label_margin." ".$style->label_margin." ".$style->label_margin." 0; display:block; color:#".parent::formatStyle($style->label_fontcolor)."; width:".$style->label_width."; font-size:".$style->label_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " div div input { margin-bottom:2px; line-height:normal; }\n";
				$form_styles .= '#' . $form_id . " input[type=checkbox] { margin:0; }\n";
				$form_styles .= '#' . $form_id . " label.checkbox, #" . $form_id . " label.radio, #" . $form_id . " label.select { display:inline; } \n";
				$form_styles .= '#' . $form_id . " input[type=text], #" . $form_id . " select { ".$round_border." color:#".parent::formatStyle($style->field_fontcolor)."; margin:0; width:".$style->input_width."; font-size:".$style->field_fontsize."; background-color:#".parent::formatStyle($style->field_backgroundcolor)."; border:1px ".$style->field_borderstyle." #".parent::formatStyle($style->field_bordercolor)."; } \n";
				$form_styles .= '#' . $form_id . " select { ".$round_border_none." width:".$style->dropdown_width."; }\n";
				$form_styles .= '#' . $form_id . " .submit { color:#".parent::formatStyle($style->submit_fontcolor)."; width:".$style->submit_width."; height:".$style->submit_height."; font-size:".$style->submit_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " .reset-button { color:#".parent::formatStyle($style->submit_fontcolor)."; width:".$style->submit_width."; height:".$style->submit_height."; font-size:".$style->submit_fontsize."; } \n";
				$form_styles .= '#' . $form_id . " textarea { ".$round_border." color:#".parent::formatStyle($style->field_fontcolor)."; width:".$style->textarea_width."; margin:0; background-color:#".parent::formatStyle($style->textarea_backgroundcolor)."; font-family:".$style->form_fontfamily."; height:".$style->textarea_height."; font-size:".$style->field_fontsize."; border:1px ".$style->field_borderstyle." #".parent::formatStyle($style->field_bordercolor)."; } \n";
				$form_styles .= '.ccf-tooltip { background-color:#'.parent::formatStyle($style->tooltip_backgroundcolor).'; font-family:'.$style->form_fontfamily.'; font-color:#'.parent::formatStyle($style->tooltip_fontcolor).'; font-size:'.$style->tooltip_fontsize.'; }' . "\n"; 
				$form_styles .= '</style>' . "\n";
			}
			
			return $form_styles . $out . $this->wheresWaldo();
		}
		
		function getCaptchaCode($form_id) {
			$admin_options = $this->getAdminOptions();
			$code_type = ($admin_options['code_type'] == 'XHTML') ? ' /' : '';
			$captcha = parent::selectField('', 'captcha');
			$instructions = (empty($captcha->field_instructions)) ? '' : 'title="'.$captcha->field_instructions.'" class="tooltip-field"';
			$out = '<img width="96" height="24" alt="' . __('Captcha image for Custom Contact Forms plugin. You must type the numbers shown in the image', 'custom-contact-forms') . '" id="captcha-image" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/custom-contact-forms/image.php?fid='.$form_id.'"'.$code_type.'> 
			<div><label for="captcha'.$form_id.'">* '.$captcha->field_label.'</label> <input class="'.$captcha->field_class.'" type="text" '.$instructions.' name="captcha" id="captcha'.$form_id.'" maxlength="20"'.$code_type.'></div>';
			return $out;
		}
		
		function startSession() {
			if (!session_id()) session_start();
		}
		
		function contactAuthor($name, $email, $website, $message, $type) {
			if (empty($message)) return false;
			require_once('custom-contact-forms-mailer.php');
			$admin_options = $this->getAdminOptions();
			$body = "Name: $name\n";
			$body .= "Email: $email\n";
			$body .= "Website: $website\n";
			$body .= "Message: $message\n";
			$body .= "Message Type: $type\n";
			$body .= 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "\n";
			$mailer = new CustomContactFormsMailer('admin@taylorlovett.com', $email, "CCF Message: $type", stripslashes($body), $admin_options['wp_mail_function']);
			$mailer->send();
			return true;
		}
		
		function insertFormSuccessCode() {
			$admin_options = $this->getAdminOptions();
			if ($this->current_form !== 0) {
				$form = parent::selectForm($this->current_form);
				$success_message = (!empty($form->form_success_message)) ? $form->form_success_message : $admin_options['form_success_message'];
				$success_title = (!empty($form->form_success_title)) ? $form->form_success_title : $admin_options['form_success_message_title'];
			} else {
				$success_title = $admin_options['form_success_message_title'];
				$success_message = (empty($this->current_thank_you_message)) ? $admin_options['form_success_message'] : $this->current_thank_you_message;
			} if ($form->form_style != 0) {
				$style = parent::selectStyle($form->form_style);
				?>
                <style type="text/css">
					<!--
					#ccf-form-success { border-color:#<?php echo parent::formatStyle($style->success_popover_bordercolor); ?>; height:<?php $style->success_popover_height; ?>; }
					#ccf-form-success div { background-color:#<?php echo parent::formatStyle($style->success_popover_bordercolor); ?>; }
					#ccf-form-success div h5 { color:#<?php echo parent::formatStyle($style->success_popover_title_fontcolor); ?>; font-size:<?php echo $style->success_popover_title_fontsize; ?>; }
					#ccf-form-success div a { color:#<?php echo parent::formatStyle($style->success_popover_title_fontcolor); ?>; }
					#ccf-form-success p { font-size:<?php echo $style->success_popover_fontsize; ?>; color:#<?php echo parent::formatStyle($style->success_popover_fontcolor); ?>; }
					-->
				</style>
                <?php
			}
		?>
        	<div id="ccf-form-success">
            	<div>
            		<h5><?php echo $success_title; ?></h5>
                	<a href="javascript:void(0)" class="close">&times;</a>
                </div>
                <p><?php echo $success_message; ?></p>
                
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
		
		function appendToActionLinks($action_links, $plugin_file) {
			static $link_added = false;
			if (!$link_added && basename($plugin_file) == 'custom-contact-forms.php') {
				$new_link = '<a style="font-weight:bold;" href="options-general.php?page=custom-contact-forms" title="' . __('Manage Custom Contact Forms', 'custom-contact-forms') . '">' . __('Settings', 'custom-contact-forms') . '</a>';
				array_unshift($action_links, $new_link);
				$link_added = true;
			}
			return $action_links;
		}
		
		
		function processForms() {
			if ($_POST['ccf_customhtml'] || $_POST['customcontactforms_submit']) {
				// BEGIN define common language vars
				$lang = array();
				$lang['field_blank'] = __('You left this field blank: ', 'custom-contact-forms');
				$lang['form_page'] = __('Form Displayed on Page: ', 'custom-contact-forms');
				$lang['sender_ip'] = __('Sender IP: ', 'custom-contact-forms');
				// END define common language vars
			} if ($_POST['ccf_customhtml']) {
				$admin_options = $this->getAdminOptions();
				$fixed_customhtml_fields = array('required_fields', 'success_message', 'thank_you_page', 'destination_email', 'ccf_customhtml');
				$req_fields = $this->requiredFieldsArrayFromList($_POST['required_fields']);
				$req_fields = array_map('trim', $req_fields);
				$body = '';
				foreach ($_POST as $key => $value) {
					if (!in_array($key, $fixed_customhtml_fields)) {
						if (in_array($key, $req_fields) && !empty($value)) {
							unset($req_fields[array_search($key, $req_fields)]);
						}
						$body .= ucwords(str_replace('_', ' ', $key)) . ': ' . $value . "\n";
						$data_array[$key] = $value;
					}
				} foreach($req_fields as $err)
					$this->setFormError($err, $lang['field_blank'] . '"' . $err . '"');
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					require_once('custom-contact-forms-user-data.php');
					require_once('custom-contact-forms-mailer.php');
					$data_object = new CustomContactFormsUserData(array('data_array' => $data_array, 'form_page' => $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'], 'form_id' => 0, 'data_time' => time()));
					parent::insertUserData($data_object);
					$body .= "\n" . $lang['form_page'] . $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'] . "\n" . $lang['sender_ip'] . $_SERVER['REMOTE_ADDR'] . "\n";
					if ($admin_options['email_form_submissions'] == 1) {
						$mailer = new CustomContactFormsMailer($_POST['destination_email'], $admin_options['default_from_email'], $admin_options['default_form_subject'], stripslashes($body), $admin_options['wp_mail_function']);
						$mailer->send();
					} if ($_POST['thank_you_page'])
						CustomContactFormsStatic::redirect($_POST['thank_you_page']);
					$this->current_thank_you_message = (!empty($_POST['success_message'])) ? $_POST['success_message'] : $admin_options['form_success_message'];
					$this->current_form = 0;
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			} elseif ($_POST['customcontactforms_submit']) {
				$this->startSession();
				$this->error_return = $_POST['form_page'];
				$admin_options = $this->getAdminOptions();
				$fields = parent::getAttachedFieldsArray($_POST['fid']);
				$form = parent::selectForm($_POST['fid']);
				$checks = array();
				$reply = ($_POST['fixedEmail']) ? $_POST['fixedEmail'] : NULL;
				$cap_name = 'captcha_' . $_POST['fid'];
				foreach ($fields as $field_id) {
					$field = parent::selectField($field_id, '');
					 if ($field->field_slug == 'ishuman') {
						if ($_POST['ishuman'] != 1)
							$this->setFormError('ishuman', __('Only humans can use this form.', 'custom-contact-forms'));
					} elseif ($field->field_slug == 'captcha') {
						if ($_POST['captcha'] != $_SESSION[$cap_name])
							$this->setFormError('captcha', __('You copied the number from the captcha field incorrectly.', 'custom-contact-forms'));
					} elseif ($field->field_slug == 'fixedEmail' && $field->field_required == 1 && !empty($_POST['fixedEmail'])) {
						if (!$this->validEmail($_POST['fixedEmail'])) $this->setFormError('bad_email', __('The email address you provided is not valid.', 'custom-contact-forms'));
					} else {
						if ($field->field_required == 1 && empty($_POST[$field->field_slug])) {
							$field_error_label = (empty($field->field_label)) ? $field->field_slug : $field->field_label;
							$this->setFormError($field->field_slug, $lang['field_blank'] . '"'.$field_error_label.'"');
						}
					} if ($field->field_type == 'Checkbox')
						$checks[] = $field->field_slug;
				} 
				$body = '';
				$data_array = array();
				foreach ($_POST as $key => $value) {
					$_SESSION['fields'][$key] = $value;
					$field = parent::selectField('', $key);
					if (!array_key_exists($key, $GLOBALS['ccf_fixed_fields']) or $key == 'fixedEmail') {
						$mail_field_label = (empty($field->field_label)) ? $field->field_slug : $field->field_label;
						$body .= $mail_field_label . ': ' . $value . "\n";
						$data_array[$key] = $value;
					} if (in_array($key, $checks)) {
						$checks_key = array_search($key, $checks);
						unset($checks[$checks_key]);
					}
				} foreach ($checks as $check_key) {
					$field = parent::selectField('', $check_key);
					$lang['not_checked'] = __('Not Checked', 'custom-contact-forms');
					$data_array[$check_key] = $lang['not_checked'];
					$body .= ucwords(str_replace('_', ' ', $field->field_label)) . ': ' . $lang['not_checked'] . "\n";
				}
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					require_once('custom-contact-forms-user-data.php');
					unset($_SESSION['captcha_' . $_POST['fid']]);
					unset($_SESSION['fields']);
					$data_object = new CustomContactFormsUserData(array('data_array' => $data_array, 'form_page' => $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 'form_id' => $form->id, 'data_time' => time()));
					parent::insertUserData($data_object);
					if ($admin_options['email_form_submission'] == 1) {
						require_once('custom-contact-forms-mailer.php');
						$body .= "\n" . $lang['form_page'] . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . "\n" . $lang['sender_ip'] . $_SERVER['REMOTE_ADDR'] . "\n";
						$to_email = (!empty($form->form_email)) ? $form->form_email : $admin_options['default_to_email'];
						$mailer = new CustomContactFormsMailer($to_email, $admin_options['default_from_email'], $admin_options['default_form_subject'], stripslashes($body), $admin_options['wp_mail_function'], $reply);
						$mailer->send();
					} if (!empty($form->form_thank_you_page))
						CustomContactFormsStatic::redirect($form->form_thank_you_page);
					$this->current_form = $form->id;
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			}
		}
	}
}
require_once('custom-contact-forms-widget.php');
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

if (!function_exists('CCFWidgetInit')) {
	function CCFWidgetInit() {
		register_widget('CustomContactFormsWidget');
	}
}

if (isset($customcontact)) {
	add_action('init', array(&$customcontact, 'init'), 1);
	register_activation_hook(__FILE__, array(&$customcontact, 'activatePlugin'));
	if (is_admin()) {
		if ($customcontact->isPluginAdminPage()) {
			add_action('admin_print_styles', array(&$customcontact, 'insertBackEndStyles'), 1);
			add_action('admin_print_scripts', array(&$customcontact, 'insertAdminScripts'), 1);
			add_action('admin_footer', array(&$customcontact, 'insertUsagePopover'));
		}
		add_filter('plugin_action_links', array(&$customcontact,'appendToActionLinks'), 10, 2);
		add_action('admin_menu', 'CustomContactForms_ap');
	} else {
		add_action('wp_print_scripts', array(&$customcontact, 'insertFrontEndScripts'), 1);
		add_action('wp_print_styles', array(&$customcontact, 'insertFrontEndStyles'), 1);
	}
	add_filter('the_content', array(&$customcontact, 'contentFilter'));
	add_action('widgets_init', 'CCFWidgetInit');
}
?>