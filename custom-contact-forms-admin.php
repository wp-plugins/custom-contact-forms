<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsAdmin')) {
	class CustomContactFormsAdmin extends CustomContactForms {
		
		function adminInit() {
			$this->downloadExportFile();
			$this->runImport();
		}
		
		function insertUsagePopover() {
			require_once('modules/usage_popover/custom-contact-forms-usage-popover.php');
		}
		
		function isPluginAdminPage() {
			return ($GLOBALS['ccf_current_page'] == 'custom-contact-forms');
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
		
		function downloadExportFile() {
			if ($_POST['ccf_export']) {
				//chmod('modules/export/', 0777);
				require_once('modules/export/custom-contact-forms-export.php');
				$transit = new CustomContactFormsExport(parent::getAdminOptionsName());
				$transit->exportAll();
				$file = $transit->exportToFile();
				CustomContactFormsStatic::redirect(WP_PLUGIN_URL . '/custom-contact-forms/download.php?location=export/' . $file);
			}
		}
		
		function runImport() {
			if ($_POST['ccf_clear_import'] || $_POST['ccf_merge_import']) {
				//chmod('modules/export/', 0777);
				require_once('modules/export/custom-contact-forms-export.php');
				$transit = new CustomContactFormsExport(parent::getAdminOptionsName());
				$settings['import_general_settings'] = ($_POST['ccf_import_overwrite_settings'] == 1) ? true : false;
				$settings['import_forms'] = ($_POST['ccf_import_forms'] == 1) ? true : false;
				$settings['import_fields'] = ($_POST['ccf_import_fields'] == 1) ? true : false;
				$settings['import_field_options'] = ($_POST['ccf_import_field_options'] == 1) ? true : false;
				$settings['import_styles'] = ($_POST['ccf_import_styles'] == 1) ? true : false;
				$settings['import_saved_submissions'] = ($_POST['ccf_import_saved_submissions'] == 1) ? true : false;
				$settings['mode'] = ($_POST['ccf_clear_import']) ? 'clear_import' : 'merge_import';
				$transit->importFromFile($_FILES['import_file'], $settings);
				CustomContactFormsStatic::redirect('options-general.php?page=custom-contact-forms');
			}
		}
		
		function contactAuthor($name, $email, $website, $message, $type) {
			if (empty($message)) return false;
			if (!class_exists('PHPMailer'))
				require_once(ABSPATH . "wp-includes/class-phpmailer.php");
			$mail = new PHPMailer(false);
			$body = "Name: $name<br />\n";
			$body .= "Email: $email<br />\n";
			$body .= "Website: $website<br />\n";
			$body .= "Message: $message<br />\n";
			$body .= "Message Type: $type<br />\n";
			$body .= 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "<br />\n";
			$admin_options = parent::getAdminOptions();
			if ($admin_options['mail_function'] == 'smtp') {
				$mail->IsSMTP();
				$mail->Host = $admin_options['smtp_host'];
				if ($admin_options['smtp_authentication'] == 1) {
					$mail->SMTPAuth = true;
					$mail->Username = $admin_options['smtp_username'];
					$mail->Password = $admin_options['smtp_password'];
					$mail->Port = $admin_options['smtp_port'];
				} else
					$mail->SMTPAuth = false;
			}
			$mail->From = $email;
			$mail->FromName = 'Custom Contact Forms';
			$mail->AddAddress('admin@taylorlovett.com');
			$mail->Subject = "CCF Message: $type";
			$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
			$mail->MsgHTML($body);
			$mail->Send();
			return true;
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
		
		function insertBackEndStyles() {
            wp_register_style('CCFStandardsCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms-standards.css');
            wp_register_style('CCFAdminCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms-admin.css');
			wp_register_style('CCFColorPickerCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/colorpicker.css');
            wp_enqueue_style('CCFStandardsCSS');
			wp_enqueue_style('CCFAdminCSS');
			wp_enqueue_style('CCFColorPickerCSS');
		}
		
		function insertAdminScripts() {
			$admin_options = parent::getAdminOptions();
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
		
		function printAdminPage() {
			$admin_options = parent::getAdminOptions();
			if ($admin_options['show_install_popover'] == 1) {
				$admin_options['show_install_popover'] = 0;
				?>
                <script type="text/javascript" language="javascript">
					$j(document).ready(function() {
						showCCFUsagePopover();
					});
				</script>
                <?php
				update_option(parent::getAdminOptionsName(), $admin_options);
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
				update_option(parent::getAdminOptionsName(), $admin_options);
			} elseif($_POST['configure_mail']) {
				$admin_options = $_POST['mail_config'];
				update_option(parent::getAdminOptionsName(), $admin_options);
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
			require_once('modules/export/custom-contact-forms-export.php');
			?>
			<div id="customcontactforms-admin">
			  <div id="icon-themes" class="icon32"></div>
			  <h2>
				<?php _e("Custom Contact Forms", 'custom-contact-forms'); ?>
			  </h2>
			  <ul id="plugin-nav">
				<li><a href="#instructions"><?php _e("Plugin Instructions", 'custom-contact-forms'); ?></a></li>
				<li><a href="#general-settings"><?php _e("General Settings", 'custom-contact-forms'); ?></a></li>
				<li><a href="#configure-mail"><?php _e("Mail Settings", 'custom-contact-forms'); ?></a></li>
				<li><a href="#create-fields"><?php _e("Create Fields", 'custom-contact-forms'); ?></a></li>
				<li><a href="#create-forms"><?php _e("Create Forms", 'custom-contact-forms'); ?></a></li>
				<li><a href="#manage-fields"><?php _e("Manage Fields", 'custom-contact-forms'); ?></a></li>
				<li><a href="#manage-fixed-fields"><?php _e("Manage Fixed Fields", 'custom-contact-forms'); ?></a></li>
				<li><a href="#manage-forms"><?php _e("Manage Forms", 'custom-contact-forms'); ?></a></li>
				<li><a href="#form-submissions"><?php _e("Saved Form Submissions (New!)", 'custom-contact-forms'); ?></a></li>
				<li><a href="#create-styles"><?php _e("Create Styles", 'custom-contact-forms'); ?></a></li>
				<li><a href="#manage-styles"><?php _e("Manage Styles", 'custom-contact-forms'); ?></a></li>
				<li><a href="#manage-field-options"><?php _e("Manage Field Options", 'custom-contact-forms'); ?></a></li>
				<li><a class="red" href="#contact-author"><?php _e("Suggest a Feature", 'custom-contact-forms'); ?></a></li>
				<li><a href="#contact-author"><?php _e("Bug Report", 'custom-contact-forms'); ?></a></li>
				<li><a href="#custom-html"><?php _e("Custom HTML Forms", 'custom-contact-forms'); ?></a></li>
				<li><a href="#import-export"><?php _e("Import / Export (New!)", 'custom-contact-forms'); ?></a></li>
				<li class="last"><a href="#plugin-news"><?php _e("Plugin News", 'custom-contact-forms'); ?></a></li>
			  </ul>
			  <form class="rate-me" action="https://www.paypal.com/cgi-bin/webscr" method="post">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="TXYVDCH955V28">
                <a href="http://wordpress.org/extend/plugins/custom-contact-forms" title="<?php _e("Rate This Plugin", 'custom-contact-forms'); ?>">
                <?php _e("We need your help to continue development! Please <span>rate this plugin</span> to show your support.", 'custom-contact-forms'); ?></a>
			    <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                <img alt="Donate to Custom Contact Forms plugin" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
              </form>
			  <a class="genesis" href="https://www.e-junkie.com/ecom/gb.php?ii=717791&c=ib&aff=125082&cl=10214">Custom Contact Forms works best with any of the 20+ <span>Genesis</span> Wordpress child themes. The <span>Genesis Framework</span> empowers you to quickly and easily build incredible websites with WordPress.</a>

              </a> <a name="create-fields"></a>
			  <div id="create-fields" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Create A Form Field", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<ul>
					  <li>
						<label for="field_slug">*
						<?php _e("Field Slug:", 'custom-contact-forms'); ?>
						</label>
						<input name="field[field_slug]" type="text" maxlength="40" />
						<br />
						<?php _e("This is just a unique way for CCF to refer to your field. Must be unique from other slugs and contain only underscores and alphanumeric characters.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_label">
						<?php _e("Field Label:", 'custom-contact-forms'); ?>
						</label>
						<input name="field[field_label]" type="text" maxlength="100" />
						<br />
						<?php _e("The field label is displayed next to the field and is visible to the user.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_type">*
						<?php _e("Field Type:", 'custom-contact-forms'); ?>
						</label>
						<select name="field[field_type]">
						  <option>Text</option>
						  <option>Textarea</option>
						  <option>Hidden</option>
						  <option>Checkbox</option>
						  <option>Radio</option>
						  <option>Dropdown</option>
						</select>
					  </li>
					  <li>
						<label for="field_value">
						<?php _e("Initial Value:", 'custom-contact-forms'); ?>
						</label>
						<input name="field[field_value]" type="text" maxlength="50" />
						<br />
						(
						<?php _e("This is the initial value of the field. If you set the type as checkbox, it is recommend you set this to what the checkbox is implying. For example if I were creating the checkbox 
						'Are you human?', I would set the initial value to 'Yes'.", 'custom-contact-forms'); ?>
						<?php _e("If you set the field type as 'Dropdown' or 'Radio', you should enter the slug of the", 'custom-contact-forms'); ?>
						<a href="#manage-field-options" title="<?php _e("Create a Field Option", 'custom-contact-forms'); ?>"><?php _e("field option", 'custom-contact-forms'); ?></a>
						<?php _e("you would like initially selected.", 'custom-contact-forms'); ?>
						) </li>
					  <li>
						<label for="field_maxlength">
						<?php _e("Max Length:", 'custom-contact-forms'); ?>
						</label>
						<input class="width50" size="10" name="field[field_maxlength]" type="text" maxlength="4" />
						<br />
						<?php _e("0 for no limit; only applies to Text fields", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_required">*
						<?php _e("Required Field:", 'custom-contact-forms'); ?>
						</label>
						<select name="field[field_required]">
						  <option value="0">
						  <?php _e("No", 'custom-contact-forms'); ?>
						  </option>
						  <option value="1">
						  <?php _e("Yes", 'custom-contact-forms'); ?>
						  </option>
						</select>
						<br />
						<?php _e("If a field is required and a user leaves it blank, the plugin will display an error message (which you can customize using 'Field Error') explaining the problem.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_instructions">
						<?php _e("Field Instructions:", 'custom-contact-forms'); ?>
						</label>
						<input name="field[field_instructions]" type="text" />
						<br />
						<?php _e("If this is filled out, a tooltip popover displaying this text will show when the field is selected.", 'custom-contact-forms'); ?>
					  </li>
                      <li>
						<label for="field_class">
						<?php _e("Field Class:", 'custom-contact-forms'); ?>
						</label>
						<input name="field[field_class]" type="text" />
						<br />
						<?php _e("If you manage your own .css stylesheet, you can use this to attach a class to this field. Leaving this blank will do nothing.", 'custom-contact-forms'); ?>
					  </li>
                      <li>
						<label for="field_error">
						<?php _e("Field Error:", 'custom-contact-forms'); ?>
						</label>
						<input name="field[field_error]" type="text" />
						<br />
						<?php _e("If a user leaves this field blank and the field is required, this error message will be shown. A generic default will show if left blank.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<input type="hidden" name="field[user_field]" value="1" />
						<input type="submit" value="<?php _e("Create Field", 'custom-contact-forms'); ?>" name="field_create" />
					  </li>
					</ul>
				  </form>
				</div>
			  </div>
			  <a name="create-forms"></a>
			  <div id="create-forms" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Create A Form", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<ul>
					  <li>
						<label for="form[form_slug]">*
						<?php _e("Form Slug:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="100" name="form[form_slug]" />
						<br />
						<?php _e("This is just a unique way for CCF to refer to your form. Must be unique from other slugs and contain only underscores and alphanumeric characters.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_title]">
						<?php _e("Form Title:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="200" name="form[form_title]" />
						<?php _e("This text is displayed above the form as the heading.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_method]">*
						<?php _e("Form Method:", 'custom-contact-forms'); ?>
						</label>
						<select name="form[form_method]">
						  <option>Post</option>
						  <option>Get</option>
						</select>
						<?php _e("If unsure, leave as is.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_action]">
						<?php _e("Form Action:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="form[form_action]" value="" />
						<br />
						<?php _e("If unsure, leave blank. Enter a URL here, if and only if you want to process your forms somewhere else, for example with a service like Aweber or InfusionSoft.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_action]">
						<?php _e("Form Style:", 'custom-contact-forms'); ?>
						</label>
						<select name="form[form_style]" class="form_style_input">
						  <?php echo $style_options; ?>
						</select>
						(<a href="#create-styles"><?php _e("Click to create a style", 'custom-contact-forms'); ?></a>)</li>
					  <li>
						<label for="form[submit_button_text]">
						<?php _e("Submit Button Text:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="200" name="form[submit_button_text]" />
					  </li>
					  <li>
						<label for="form[custom_code]">
						<?php _e("Custom Code:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="form[custom_code]" />
						<br />
						<?php _e("If unsure, leave blank. This field allows you to insert custom HTML directly after the starting form tag.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_email]">
						<?php _e("Form Destination Email:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="form[form_email]" />
						<br />
						<?php _e("Will receive all submissions from this form; if left blank it will use the default specified in general settings.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_success_message]">
						<?php _e("Form Success Message:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="form[form_success_message]" />
						<br />
						<?php _e("Will be displayed in a popover when the form is filled out successfully when no custom success page is specified; if left blank it will use the default specified in general settings.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_success_title]">
						<?php _e("Form Success Message Title:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="form[form_success_title]" />
						<br />
						<?php _e("Will be displayed in a popover when the form is filled out successfully when no custom success page is specified; if left blank it will use the default specified in general settings.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form[form_thank_you_page]">
						<?php _e("Custom Success URL:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="form[form_thank_you_page]" />
						<br />
						<?php _e("If this is filled out, users will be sent to this page when they successfully fill out this form. If it is left blank, a popover showing the form's 'success message' will be displayed on form success.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<input type="submit" value="<?php _e("Create Form", 'custom-contact-forms'); ?>" name="form_create" />
					  </li>
					</ul>
				  </form>
				</div>
			  </div>
			  <a name="manage-fields"></a>
			  <h3 class="manage-h3">
				<?php _e("Manage User Fields", 'custom-contact-forms'); ?>
			  </h3>
			  <table class="widefat post" id="manage-fields" cellspacing="0">
				<thead>
				  <tr>
					<th scope="col" class="manage-column field-slug"><?php _e("Slug", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-label"><?php _e("Label", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-type"><?php _e("Type", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-value"><?php _e("Initial Value", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-required"><?php _e("Required", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-maxlength"><?php _e("Maxlength", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-action"><?php _e("Action", 'custom-contact-forms'); ?></th>
				  </tr>
				</thead>
				<tbody>
				  <?php
			$fields = parent::selectAllFields();
			for ($i = 0, $z = 0; $i < count($fields); $i++, $z++) {
			if ($fields[$i]->user_field == 0) { $z--; continue; }
			$attached_options = parent::getAttachedFieldOptionsArray($fields[$i]->id);
			$field_types = '<option>Text</option><option>Textarea</option><option>Hidden</option><option>Checkbox</option><option>Radio</option><option>Dropdown</option>';
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
					<td><select name="field[field_required]">
						<option value="1">
						<?php _e("Yes", 'custom-contact-forms'); ?>
						</option>
						<option value="0" <?php if ($fields[$i]->field_required != 1) echo 'selected="selected"'; ?>>
						<?php _e("No", 'custom-contact-forms'); ?>
						</option>
					  </select></td>
					<td><?php if ($fields[$i]->field_type == 'Dropdown' || $fields[$i]->field_type == 'Radio') { ?>
					  <b>-</b>
					  <?php } else { ?>
					  <input type="text" class="width50" name="field[field_maxlength]" value="<?php echo $fields[$i]->field_maxlength; ?>" />
					  <?php } ?>
					</td>
					<td><input type="hidden" class="object-type" name="object_type" value="field" />
					  <input type="hidden" class="object-id" name="fid" value="<?php echo $fields[$i]->id; ?>" />
					  <span class="fields-options-expand"></span>
					  <input type="submit" class="edit-button" name="field_edit" value="<?php _e("Save", 'custom-contact-forms'); ?>" />
					  <input type="submit" name="field_delete" class="delete-button" value="<?php _e("Delete", 'custom-contact-forms'); ?>" /></td>
				  </tr>
				  <?php $show_field_options = ($fields[$i]->field_type == 'Radio' || $fields[$i]->field_type == 'Dropdown') ? true : false; ?>
				  <tr<?php if ($z % 2 == 1) echo ' class="evenrow"'; ?>>
					<td class="fields-extra-options" colspan="7">
                      <div class="row-one">
						<a href="javascript:void(0)" class="toollink" title="<?php _e('If this is filled out, a tooltip popover displaying this text will show when the field is selected.', 'custom-contact-forms'); ?>">(?)</a>
						<label for="field_instructions">
						<?php _e("Field Instructions:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" class="width150" name="field[field_instructions]" value="<?php echo $fields[$i]->field_instructions; ?>" />
						<a href="javascript:void(0)" class="toollink" title="<?php _e('If you manage a .CSS file for your theme, you could create a class in that file and add it to this field. If the form attaching this field is using a "Form Style" other than the default, styles inherited from the "Field Class" might be overwritten.', 'custom-contact-forms'); ?>">(?)</a>
					  	<label for="field_class">
						<?php _e("Field Class:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" class="width100" name="field[field_class]" value="<?php echo $fields[$i]->field_class; ?>" />
						<a href="javascript:void(0)" class="toollink" title="<?php _e('This lets you customize the error message displayed when this field is required and left blank.', 'custom-contact-forms'); ?>">(?)</a>
					    <label for="field_error">
						<?php _e("Field Error:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" class="width200" name="field[field_error]" value="<?php echo $fields[$i]->field_error; ?>" /> 
						</div>
					  <?php 
			if ($show_field_options) { ?>
					  <div class="dettach-field-options">
						<?php if (empty($attached_options)) { ?>
						<select class="onObject<?php echo $fields[$i]->id ?> objectTypeField" name="dettach_object_id">
						  <option value="-1">Nothing Attached!</option>
						</select>
						<?php } else { ?>
						<select name="dettach_object_id" class="onObject<?php echo $fields[$i]->id ?> objectTypeField">
						  <?php
			foreach ($attached_options as $option_id) {
			$option = parent::selectFieldOption($option_id);
			?>
						  <option value="<?php echo $option_id; ?>"><?php echo $option->option_slug; ?></option>
						  <?php
			}
			?>
						</select>
						<?php } ?>
						<input type="submit" class="dettach-button" name="dettach_field_option" value="<?php _e("Dettach Field Option", 'custom-contact-forms'); ?>" />
						<br />
						<span class="red bold">*</span>
						<?php _e("Dettach field options you", 'custom-contact-forms'); ?>
						<a href="#create-field-options">
						<?php _e("create", 'custom-contact-forms'); ?>
						</a>. </div>
					  <?php $all_options = $this->getFieldOptionsForm(); ?>
					  <div class="attach-field-options">
						<?php if (empty($all_options)) { ?>
						<b>No Field Options to Attach</b>
						<?php } else { ?>
						<select name="attach_object_id" class="onObject<?php echo $fields[$i]->id ?> objectTypeField">
						  <?php echo $all_options; ?>
						</select>
						<input type="submit" class="attach-button" name="attach_field_option" value="<?php _e("Attach Field Option", 'custom-contact-forms'); ?>" />
						<?php } ?>
						<br />
						<span class="red bold">*</span>
						<?php _e("Attach field options in the order you want them to display.", 'custom-contact-forms'); ?>
					  </div>
					  <?php } ?>
					</td>
				  </tr>
				</form>
				<?php
			}
			?>
				</tbody>
				
				<tfoot>
				  <tr>
					<th scope="col" class="manage-column field-slug"><?php _e("Slug", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-label"><?php _e("Label", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-type"><?php _e("Type", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-value"><?php _e("Initial Value", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-required"><?php _e("Required", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-maxlength"><?php _e("Maxlength", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-action"><?php _e("Action", 'custom-contact-forms'); ?></th>
				  </tr>
				</tfoot>
			  </table>
			  <a name="manage-fixed-fields"></a>
			  <h3 class="manage-h3">
				<?php _e("Manage Fixed Fields", 'custom-contact-forms'); ?>
			  </h3>
			  <table class="widefat post" id="manage-fixed-fields" cellspacing="0">
				<thead>
				  <tr>
					<th scope="col" class="manage-column field-slug"><?php _e("Slug", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-label"><?php _e("Label", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-type"><?php _e("Type", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-value"><?php _e("Initial Value", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-value"><?php _e("Required", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-maxlength"><?php _e("Maxlength", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-action"><?php _e("Action", 'custom-contact-forms'); ?></th>
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
				  <tr <?php if ($z % 2 == 0) echo ' class="evenrow"'; ?>>
					<td><?php echo $fields[$i]->field_slug; ?></td>
					<td><?php if ($fields[$i]->field_slug == 'resetButton') { _e('None', 'custom-contact-forms'); } else { ?>
					  <input type="text" name="field[field_label]" maxlength="100" value="<?php echo $fields[$i]->field_label; ?>" />
					  <?php } ?></td>
					<td><?php echo $fields[$i]->field_type; ?>
					<td><?php if ($fields[$i]->field_type != 'Checkbox') { ?>
					  <input type="text" name="field[field_value]" class="width75" maxlength="50" value="<?php echo $fields[$i]->field_value; ?>" />
					  <?php } else {
			echo $fields[$i]->field_value;
			?>
					  <?php } ?>
					</td>
					<td><?php if ($fields[$i]->field_slug == 'fixedEmail') { ?>
					  <select name="field[field_required]">
						<option value="1">
						<?php _e("Yes", 'custom-contact-forms'); ?>
						</option>
						<option <?php if($fields[$i]->field_required != 1) echo 'selected="selected"'; ?> value="0">
						<?php _e("No", 'custom-contact-forms'); ?>
						</option>
					  </select>
					  <?php } else {
			if ($fields[$i]->field_slug == 'resetButton') {
			echo '-';
			} else {
			_e("Yes", 'custom-contact-forms');
			}
			}
			?>
					</td>
					<td><?php if ($fields[$i]->field_type != 'Checkbox' && $fields[$i]->field_slug != 'resetButton') { ?>
					  <input type="text" class="width50" name="field[field_maxlength]" value="<?php echo $fields[$i]->field_maxlength; ?>" />
					  <?php } else { _e('None', 'custom-contact-forms'); } ?>
					</td>
					<td><input type="hidden" class="object-type" name="object_type" value="field" />
					  <input type="hidden" class="object-id" name="fid" value="<?php echo $fields[$i]->id; ?>" />
					  <span class="fixed-fields-options-expand"></span>
					  <input type="submit" name="field_edit" class="edit-button" value="<?php _e("Save", 'custom-contact-forms'); ?>" /></td>
				  </tr>
				  <tr <?php if ($z % 2 == 0) echo ' class="evenrow"'; ?>>
					<td class="fixed-fields-extra-options" colspan="7"><label for="field_class">
					  <a href="javascript:void(0)" class="toollink" title="<?php _e('If you manage a .CSS file for your theme, you could create a class in that file and add it to this field. If the form attaching this field is using a "Form Style" other than the default, styles inherited from the "Field Class" might be overwritten.', 'custom-contact-forms'); ?>">(?)</a>
					  <?php _e('Field Class:', 'custom-contact-forms'); ?>
					  </label>
					  <input type="text" value="<?php echo $fields[$i]->field_class; ?>" name="field[field_class]" />
					  <?php if ($fields[$i]->field_slug != 'resetButton') { ?>
					  <a href="javascript:void(0)" class="toollink" title="<?php _e('If this is filled out, a tooltip popover displaying this text will show when the field is selected.', 'custom-contact-forms'); ?>">(?)</a>
					  <label for="field_instructions">
					  <?php _e("Field Instructions:", 'custom-contact-forms'); ?>
					  </label>
					  <input type="text" name="field[field_instructions]" class="width200" value="<?php echo $fields[$i]->field_instructions; ?>" />
					  <a href="javascript:void(0)" class="toollink" title="<?php _e('This lets you customize the error message displayed when this field is required and left blank.', 'custom-contact-forms'); ?>">(?)</a>
					  <label for="field_error">
					  <?php _e("Field Error:", 'custom-contact-forms'); ?>
					  </label>
					  <input type="text" class="width200" name="field[field_error]" value="<?php echo $fields[$i]->field_error; ?>" /> 
					  <br />
					  <?php } ?>
                      <div class="field_descrip"><?php echo $GLOBALS['ccf_fixed_fields'][$fields[$i]->field_slug]; ?></div></td>
				  </tr>
				</form>
				<?php
			}
			?>
				</tbody>
				
				<tfoot>
				  <tr>
					<th scope="col" class="manage-column field-slug"><?php _e("Slug", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-label"><?php _e("Label", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-type"><?php _e("Type", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-value"><?php _e("Initial Value", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-value"><?php _e("Required", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-maxlength"><?php _e("Maxlength", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column field-action"><?php _e("Action", 'custom-contact-forms'); ?></th>
				  </tr>
				</tfoot>
			  </table>
			  <a name="manage-field-options"></a>
			  <div id="field-options" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Manage Field Options (for Dropdown and Radio Fields)", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <div class="option-header">
					<div class="slug">
					  <?php _e("Slug", 'custom-contact-forms'); ?>
					</div>
					<div class="label">
					  <?php _e("Label", 'custom-contact-forms'); ?>
					</div>
					<div class="option-value">
					  <?php _e("Value", 'custom-contact-forms'); ?>
					</div>
					<div class="action">
					  <?php _e("Action", 'custom-contact-forms'); ?>
					</div>
				  </div>
				  <table id="edit-field-options">
					<?php
			$options = parent::selectAllFieldOptions();
			$i = 0;
			foreach ($options as $option) {
			?>
					<tr<?php if ($i % 2 == 1) echo ' class="evenrow-field-options"'; ?>>
					  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
						<td class="slug"><input type="text" maxlength="20" name="option[option_slug]" value="<?php echo $option->option_slug; ?>" class="width50" /></td>
						<td class="label"><input type="text" name="option[option_label]" value="<?php echo $option->option_label; ?>" class="width100" /></td>
						<td class="option-value"><input type="text" name="option[option_value]" value="<?php echo $option->option_value; ?>" class="width100" /></td>
						<td class="action"><input type="submit" value="<?php _e("Save", 'custom-contact-forms'); ?>" class="edit-button" name="edit_field_option" />
						  <input type="submit" class="delete-button" value="<?php _e("Delete", 'custom-contact-forms'); ?>" name="delete_field_option" />
						</td>
						<input type="hidden" class="object-type" name="object_type" value="field_option" />
						<input type="hidden" class="object-id" name="oid" value="<?php echo $option->id; ?>" />
					  </form>
					</tr>
					<?php
			$i++;
			} if (empty($options)) {
			?>
					<tr>
					  <td class="ccf-center"><?php _e("No field options have been created.", 'custom-contact-forms'); ?></td>
					</tr>
					<?php
			}
			?>
				  </table>
				  <div class="option-header">
					<div class="slug">
					  <?php _e("Slug", 'custom-contact-forms'); ?>
					</div>
					<div class="label">
					  <?php _e("Label", 'custom-contact-forms'); ?>
					</div>
					<div class="option-value">
					  <?php _e("Value", 'custom-contact-forms'); ?>
					</div>
					<div class="action">
					  <?php _e("Action", 'custom-contact-forms'); ?>
					</div>
				  </div>
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
					<input type="hidden" name="object_type" value="field_option" />
					<div class="create-field-options-header">
					  <?php _e("Create a Field Option", 'custom-contact-forms'); ?>
					</div>
					<ul id="create-field-options">
					  <li>
						<label for="option[option_slug]">*
						<?php _e("Option Slug:", 'custom-contact-forms'); ?>
						</label>
						<input maxlength="20" type="text" name="option[option_slug]" />
						<br />
						<?php _e("Used to identify this option, solely for admin purposes; must be unique, and contain only letters, numbers, and underscores. Example: 'slug_one'", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="option[option_label]">*
						<?php _e("Option Label:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="option[option_label]" />
						<br />
						<?php _e("This is what is shown to the user in the dropdown or radio field. Example: 'United States'", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="option[option_value]">
						<?php _e("Option Value:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" name="option[option_value]" /> <a href="javascript:void(0)" class="toollink" title="<?php _e("This is the actual value of the option which is not shown to the user. This can be the same thing as the label. An example pairing of label => value is: 'The color green' => 'green' or 'Yes' => '1'.", 'custom-contact-forms'); ?>">(?)</a>
						<br />
						<?php _e('This is the actual value of the option which is not shown to the user. This can be the same thing as the label. An example pairing of label => value is: "The color green" => "green" or "Yes" => "1".', 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<input type="submit" class="create-button" name="create_field_option" value="<?php _e("Create Field Option", 'custom-contact-forms'); ?>" />
					  </li>
					</ul>
				  </form>
				</div>
			  </div>
			  <a name="manage-forms"></a>
			  <h3 class="manage-h3">
				<?php _e("Manage Forms", 'custom-contact-forms'); ?>
			  </h3>
			  <table class="widefat post" id="manage-forms" cellspacing="0">
				<thead>
				  <tr>
					<th scope="col" class="manage-column form-code"><?php _e("Form Display Code", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-slug"><?php _e("Slug", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-title"><?php _e("Title", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-submit"><?php _e("Button Text", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-submit"><?php _e("Style", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-submit"><?php _e("Action", 'custom-contact-forms'); ?></th>
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
					<td><select name="form[form_style]" class="form_style_input">
						<?php echo $sty_opt; ?>
					  </select></td>
					<td><input type="hidden" class="object-id" name="fid" value="<?php echo $forms[$i]->id; ?>" />
					  <input type="hidden" class="object-type" name="object_type" value="form" />
					  <span class="form-options-expand"></span>
					  <input type="submit" name="form_edit" class="edit-button" value="<?php _e("Save", 'custom-contact-forms'); ?>" />
					  <input type="submit" name="form_delete" class="delete-button" value="<?php _e("Delete", 'custom-contact-forms'); ?>" />
					</td>
				  </tr>
				  <tr class="<?php if ($i % 2 == 0) echo 'evenrow'; ?>">
					<td class="form-extra-options textcenter" colspan="8"><table class="form-extra-options-table">
						<tbody>
						  <tr>
							<td class="bold"><?php _e("Method", 'custom-contact-forms'); ?></td>
							<td class="bold"><?php _e("Form Action", 'custom-contact-forms'); ?></td>
							<td class="bold"><?php _e("Destination Email", 'custom-contact-forms'); ?></td>
							<td class="bold"><?php _e("Success Message Title", 'custom-contact-forms'); ?></td>
							<td class="bold"><?php _e("Success Message", 'custom-contact-forms'); ?></td>
							<td class="bold"><?php _e("Custom Success URL", 'custom-contact-forms'); ?></td>
						  </tr>
						  <tr>
							<td><a href="javascript:void(0)" class="toollink" title="<?php _e("The Form Method is the method by which information is transfer through your form. If you aren't an expert with HTML and PHP, leave this as Post.", 'custom-contact-forms'); ?>">(?)</a>
							  <select name="form[form_method]">
								<?php echo $form_methods; ?>
							  </select></td>
							<td><a href="javascript:void(0)" class="toollink" title="<?php _e("This lets you process your forms through alternate scripts. If you use a service like InfusionSoft or Aweber, set this to be the same form action as the code provided to you by that service, otherwise leave this blank.", 'custom-contact-forms'); ?>">(?)</a>
							  <input class="width100" type="text" name="form[form_action]" value="<?php echo $forms[$i]->form_action; ?>" /></td>
							<td><a href="javascript:void(0)" class="toollink" title="<?php _e("Specify the email address(es) that you wish to receive form submission emails (provided that Email Form Submissions is set to Yes in general settings). Seperate multiple email addresses with semi-colons (ex: email1@gmail.com;email2@gmail.com;email3@gmail.com).", 'custom-contact-forms'); ?>">(?)</a>
							  <input class="width100" type="text" name="form[form_email]" value="<?php echo $forms[$i]->form_email; ?>" /></td>
							<td><a href="javascript:void(0)" class="toollink" title="<?php _e("This will be displayed as the header in a popover when the form is filled out successfully when no custom success page is specified; if left blank it will use the default specified in general settings.", 'custom-contact-forms'); ?>">(?)</a>
							  <input class="width100" type="text" name="form[form_success_title]" value="<?php echo $forms[$i]->form_success_title; ?>" /></td>
							<td><a href="javascript:void(0)" class="toollink" title="<?php _e("This will be displayed in a popover when the form is filled out successfully when no custom success page is specified; if left blank it will use the default specified in general settings.", 'custom-contact-forms'); ?>">(?)</a>
							  <input type="text" name="form[form_success_message]" class="width100" value="<?php echo $forms[$i]->form_success_message; ?>" /></td>
							<td><a href="javascript:void(0)" class="toollink" title="<?php _e("If this is filled out, users will be sent to this thank you page when they successfully fill out this form. If it is left blank, a popover showing the form's 'success message' will be displayed on form success.", 'custom-contact-forms'); ?>">(?)</a>
							  <input type="text" class="width100" name="form[form_thank_you_page]" value="<?php echo $forms[$i]->form_thank_you_page; ?>" /></td>
						  </tr>
						  <tr>
							<td colspan="3"><label for="dettach_object_id"><span>
							  <?php _e("Attached Fields:", 'custom-contact-forms'); ?>
							  </span></label>
							  <?php
				$attached_fields = parent::getAttachedFieldsArray($forms[$i]->id);
				if (empty($attached_fields)) echo '<select class="onObject' . $forms[$i]->id . ' objectTypeForm" name="dettach_object_id"><option value="-1">Nothing Attached!</option></select> ';
				else {
					echo '<select name="dettach_object_id" class="onObject' . $forms[$i]->id . ' objectTypeForm">';
					foreach($attached_fields as $attached_field) {
						$this_field = parent::selectField($attached_field, '');
						echo $this_field->field_slug . ' <option value="'.$this_field->id.'">'.$this_field->field_slug.'</option>';
					}
					echo '</select>';
				}
			  ?>
							  <input type="submit" class="dettach-button" value="<?php _e("Dettach Field", 'custom-contact-forms'); ?>" name="dettach_field" />
							  <br />
							  <span class="red bold">*</span>
							  <?php _e("Attach fields in the order you want them displayed.", 'custom-contact-forms'); ?>
							</td>
							<td colspan="3"><label for="field_id"><span>
							  <?php _e("Attach Field:", 'custom-contact-forms'); ?>
							  </span></label>
							  <select class="onObject<?php echo $forms[$i]->id; ?> objectTypeForm" name="attach_object_id">
								<?php echo $add_fields; ?>
							  </select>
							  <input class="attach-button" type="submit" name="form_add_field" value="<?php _e("Attach Field", 'custom-contact-forms'); ?>" />
							  <br />
							  <span class="red bold">*</span>
							  <?php _e("Attach fixed fields or ones you", 'custom-contact-forms'); ?>
							  <a href="#create-fields">
							  <?php _e("create", 'custom-contact-forms'); ?>
							  </a>. </td>
						  </tr>
						  <tr>
							<td colspan="6"><label for="theme_code_<?php echo $forms[$i]->id; ?>"><a href="javascript:void(0)" class="toollink" title="<?php _e("The form display code above ([customcontact form=x]) will only work in Wordpress pages and posts. If you want to display this form in a theme file such as page.php, header.php, index.php, category.php, etc, then insert this PHP snippet.", 'custom-contact-forms'); ?>">(?)</a> <span>
							  <?php _e("Code to Display Form in Theme Files:", 'custom-contact-forms'); ?>
							  </span></label>
							  <input type="text" class="width225" value="&lt;?php if (function_exists('serveCustomContactForm')) { serveCustomContactForm(<?php echo $forms[$i]->id; ?>); } ?&gt;" name="theme_code_<?php echo $forms[$i]->id; ?>" />
							  <label for="form[custom_code]"><a href="javascript:void(0)" class="toollink" title="<?php _e("This field allows you to insert HTML directly after the starting <form> tag.", 'custom-contact-forms'); ?>">(?)</a>
							  <?php _e("Custom Code:", 'custom-contact-forms'); ?>
							  </label>
							  <input name="form[custom_code]" type="text" value="<?php echo $forms[$i]->custom_code; ?>" /></td>
						  </tr>
						</tbody>
					  </table></td>
				  </tr>
				</form>
				<?php
			}
			$remember_check = ($admin_options['remember_field_values'] == 0) ? 'selected="selected"' : '';
			$remember_fields = '<option value="1">'.__('Yes', 'custom-contact-forms').'</option><option '.$remember_check.' value="0">'.__('No', 'custom-contact-forms').'</option>';
			$border_style_options = '<option>solid</option><option>dashed</option>
			<option>grooved</option><option>double</option><option>dotted</option><option>ridged</option><option>none</option>
			<option>inset</option><option>outset</option>';
			?>
				</tbody>
				
				<tfoot>
				  <tr>
				  <tr>
					<th scope="col" class="manage-column form-code"><?php _e("Form Code", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-slug"><?php _e("Slug", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-title"><?php _e("Title", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-submit"><?php _e("Button Text", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-submit"><?php _e("Style", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column form-submit"><?php _e("Action", 'custom-contact-forms'); ?></th>
				  </tr>
				  </tr>
				  
				</tfoot>
			  </table>
			  <?php
			require_once('modules/export/custom-contact-forms-user-data.php');
			$user_data_array = parent::selectAllUserData();
			?>
			  <a name="form-submissions"></a>
			  <h3 class="manage-h3">
				<?php _e("Saved Form Submissions", 'custom-contact-forms'); ?>
			  </h3>
			  <table class="widefat post" id="form-submissions" cellspacing="0">
				<thead>
				  <tr>
					<th scope="col" class="manage-column width250"><?php _e("Date Submitted", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column width150"><?php _e("Form Submitted", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column width250"><?php _e("Form Page", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column "><div class="alignright">
						<?php _e("Action", 'custom-contact-forms'); ?>
					  </div></th>
				  </tr>
				</thead>
				<tbody>
				  <?php
			$i = 0;
			foreach ($user_data_array as $data_object) {
			$data = new CustomContactFormsUserData(array('form_id' => $data_object->data_formid, 'data_time' => $data_object->data_time, 'form_page' => $data_object->data_formpage, 'encoded_data' => $data_object->data_value));	
			?>
				  <tr class="submission-top <?php if ($i % 2 == 0) echo 'evenrow'; ?>">
					<td><?php echo date('F d, Y h:i:s A', $data->getDataTime()); ?></td>
					<td><?php
			if ($data->getFormID() > 0) {
			$data_form = parent::selectForm($data->getFormID());
			echo $data_form->form_slug;
			} else
			_e('Custom HTML Form', 'custom-contact-forms');
			?>
					</td>
					<td><?php echo $data->getFormPage(); ?> </td>
					<td class="alignright"><form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
						<span class="submission-content-expand"></span>
						<input type="submit" class="delete-button" value="<?php _e("Delete", 'custom-contact-forms'); ?>" name="form_submission_delete" />
						<input type="hidden" class="object-type" name="object_type" value="form_submission" />
						<input type="hidden" class="object-id" value="<?php echo $data_object->id; ?>" name="uid" />
					  </form></td>
				  </tr>
				  <tr class="submission-content <?php if ($i % 2 == 0) echo 'evenrow'; ?>">
					<td  colspan="4"><ul>
						<?php
			$data_array = $data->getDataArray();
			foreach ($data_array as $item_key => $item_value) {
			?>
						<li>
						  <div><?php echo $item_key; ?></div>
						  <p><?php echo $item_value; ?></p>
						</li>
						<?php
			}
			?>
					  </ul></td>
				  </tr>
				  <?php
			$i++;
			}
			?>
				</tbody>
				<tfoot>
				  <tr>
					<th scope="col" class="manage-column width250"><?php _e("Date Submitted", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column width150"><?php _e("Form Submitted", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column width250"><?php _e("Form Page", 'custom-contact-forms'); ?></th>
					<th scope="col" class="manage-column "><div class="alignright">
						<?php _e("Action", 'custom-contact-forms'); ?>
					  </div></th>
				  </tr>
				</tfoot>
			  </table>
			  <a name="general-settings"></a>
			  <div id="general-settings" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("General Settings", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<ul>
					  <li>
						<label for="email_form_submissions">
						<?php _e("Email Form Submissions:", 'custom-contact-forms'); ?>
						</label>
						<select name="email_form_submissions">
						  <option value="1">
						  <?php _e("Yes", 'custom-contact-forms'); ?>
						  </option>
						  <option value="0" <?php if ($admin_options['email_form_submissions'] == 0) echo 'selected="selected"'; ?>>
						  <?php _e("No", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					  <li class="descrip">
						<?php _e("When a user fills out one of your forms, the info submitted is saved in the Saved Form Submission section of the admin panel for you to view. If this is enabled, you will also be sent an email containing the submission info.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="default_to_email">
						<?php _e("Default Email:", 'custom-contact-forms'); ?>
						</label>
						<input name="default_to_email" value="<?php echo $admin_options['default_to_email']; ?>" type="text" maxlength="100" />
					  </li>
					  <li class="descrip">
						<?php _e("Form emails will be sent <span>to</span> this address, if no destination email is specified by the form.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="enable_jquery">
						<?php _e("Front End JQuery:", 'custom-contact-forms'); ?>
						</label>
						<select name="enable_jquery">
						  <option value="1">
						  <?php _e("Enabled", 'custom-contact-forms'); ?>
						  </option>
						  <option <?php if ($admin_options['enable_jquery'] != 1) echo 'selected="selected"'; ?> value="0">
						  <?php _e("Disabled", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					  <li class="descrip">
						<?php _e("Some plugins don't setup JQuery correctly, so when any other plugin uses JQuery (whether correctly or not), JQuery works for neither plugin. This plugin uses JQuery correctly. If another plugin isn't using JQuery correctly but is more important to you than this one: disable this option. 99% of this plugin's functionality will work without JQuery, just no field instruction tooltips.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="default_from_email">
						<?php _e("Default From Email:", 'custom-contact-forms'); ?>
						</label>
						<input name="default_from_email" value="<?php echo $admin_options['default_from_email']; ?>" type="text" maxlength="100" />
					  </li>
					  <li class="descrip">
						<?php _e("Form emails will be sent <span>from</span> this address. It is recommended you provide a real email address that has been created through your host.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="default_form_subject">
						<?php _e("Default Email Subject:", 'custom-contact-forms'); ?>
						</label>
						<input name="default_form_subject" value="<?php echo $admin_options['default_form_subject']; ?>" type="text" />
					  </li>
					  <li class="descrip">
						<?php _e("Default subject to be included in all form emails.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_success_message_title">
						<?php _e("Default Form Success Message Title:", 'custom-contact-forms'); ?>
						</label>
						<input name="form_success_message_title" value="<?php echo $admin_options['form_success_message_title']; ?>" type="text"/>
					  </li>
					  <li class="descrip">
						<?php _e("If someone fills out a form for which a success message title is not provided and a custom success page is not provided, the plugin will show a popover using this field as the window title.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_success_message">
						<?php _e("Default Form Success Message:", 'custom-contact-forms'); ?>
						</label>
						<input name="form_success_message" value="<?php echo $admin_options['form_success_message']; ?>" type="text"/>
					  </li>
					  <li class="descrip">
						<?php _e("If someone fills out a form for which a success message is not provided and a custom success page is not provided, the plugin will show a popover containing this message.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="remember_field_values">
						<?php _e("Remember Field Values:", 'custom-contact-forms'); ?>
						</label>
						<select name="remember_field_values">
						  <option value="1">
						  <?php _e("Yes", 'custom-contact-forms'); ?>
						  </option>
						  <option <?php if ($admin_options['remember_field_values'] == 0) echo 'selected="selected"'; ?> value="0">
						  <?php _e("No", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					  <li class="descrip">
						<?php _e("Selecting yes will make form fields remember how they were last filled out.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="enable_widget_tooltips">
						<?php _e("Tooltips in Widget:", 'custom-contact-forms'); ?>
						</label>
						<select name="enable_widget_tooltips">
						  <option value="1">
						  <?php _e("Enabled", 'custom-contact-forms'); ?>
						  </option>
						  <option <?php if ($admin_options['enable_widget_tooltips'] == 0) echo 'selected="selected"'; ?> value="0">
						  <?php _e("Disabled", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					  <li class="descrip">
						<?php _e("Enabling this shows tooltips containing field instructions on forms in the widget.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="author_link">
						<?php _e("Hide Plugin Author Link in Code:", 'custom-contact-forms'); ?>
						</label>
						<select name="author_link">
						  <option value="1">
						  <?php _e("Yes", 'custom-contact-forms'); ?>
						  </option>
						  <option <?php if ($admin_options['author_link'] == 0) echo 'selected="selected"'; ?> value="0">
						  <?php _e("No", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					  <li>
						<label for="code_type">
						<?php _e("Use Code Type:", 'custom-contact-forms'); ?>
						</label>
						<select name="code_type">
						  <option>XHTML</option>
						  <option <?php if ($admin_options['code_type'] == 'HTML') echo 'selected="selected"'; ?>>HTML</option>
						</select>
					  </li>
					  <li class="descrip">
						<?php _e("This lets you switch the form code between HTML and XHTML.", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="admin_ajax">
						<?php _e("Fancy Admin AJAX Abilities:", 'custom-contact-forms'); ?>
						</label>
						<select name="admin_ajax">
						  <option value="1">
						  <?php _e("Enabled", 'custom-contact-forms'); ?>
						  </option>
						  <option <?php if ($admin_options['admin_ajax'] == 0) echo 'selected="selected"'; ?>>
						  <?php _e("Disabled", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					  <li class="descrip">
						<?php _e("If you enable this, creating, editing and modifying forms, fields, styles, etc in the admin panel will be done using AJAX. This means that clicking things like 'Edit' or 'Delete' will not cause the page to reload thus managing your forms will be much smoother and quicker. If you are having problems with things not saving, deleting, or inserting correctly, then disable this and fill out a bug report below.", 'custom-contact-forms'); ?>
					  </li>
					  <li class="show-widget"><b>
						<?php _e("Show Sidebar Widget:", 'custom-contact-forms'); ?>
						</b></li>
					  <li>
						<label>
						<input value="1" type="checkbox" name="show_widget_home" <?php if ($admin_options['show_widget_home'] == 1) echo 'checked="checked"'; ?> />
						<?php _e("On Homepage", 'custom-contact-forms'); ?>
						</label>
						<label>
						<input value="1" type="checkbox" name="show_widget_pages" <?php if ($admin_options['show_widget_pages'] == 1) echo 'checked="checked"'; ?> />
						<?php _e("On Pages", 'custom-contact-forms'); ?>
						</label>
						<label>
						<input value="1" type="checkbox" name="show_widget_singles" <?php if ($admin_options['show_widget_singles'] == 1) echo 'checked="checked"'; ?> />
						<?php _e("On Single Posts", 'custom-contact-forms'); ?>
						</label>
						<br />
						<label>
						<input value="1" type="checkbox" name="show_widget_categories" <?php if ($admin_options['show_widget_categories'] == 1) echo 'checked="checked"'; ?> />
						<?php _e("On Categories", 'custom-contact-forms'); ?>
						</label>
						<label>
						<input value="1" type="checkbox" name="show_widget_archives" <?php if ($admin_options['show_widget_archives'] == 1) echo 'checked="checked"'; ?> />
						<?php _e("On Archives", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="submit" value="<?php _e("Update", 'custom-contact-forms'); ?>" name="general_settings" />
					  </li>
					</ul>
				  </form>
				</div>
			  </div>
			  <a name="instructions"></a>
			  <div id="instructions" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Instructions", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <p><b>
					<?php _e("The default content will help you get a better feel of ways this plugin can be used and is the best way to learn.", 'custom-contact-forms'); ?>
					</b></p>
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<div class="ccf-center">
					  <input type="submit" value="<?php _e("Insert Default Content", 'custom-contact-forms'); ?>" name="insert_default_content" />
					</div>
				  </form>
				  <p>
					<?php _e("1. Create a form.", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("2. Create fields and attach those fields to the forms of your choice.", 'custom-contact-forms'); ?>
					<span class="red bold">*</span>
					<?php _e("Attach the fields in the order that you want them to show up in the form. If you mess up you can detach and reattach them. Create field options in the field option manager; field options should be attached to radio and dropdown fields.", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("3. Display those forms in posts and pages by inserting the code: [customcontact form=<b>FORMID</b>]. Replace <b>FORMID</b> with the id listed to the left of the form slug next to the form of your choice above. You can also display forms in theme files; the code for this is provided within each forms admin section.", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("4. Prevent spam by attaching the fixed field, captcha or ishuman. Captcha requires users to type in a number shown on an image. Ishuman requires users to check a box to prove they aren't a spam bot.", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("5. Add a form to your sidebar, by dragging the Custom Contact Form widget in to your sidebar.", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("6. Configure the General Settings appropriately; this is important if you want to receive your web form messages!", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("7. Create form styles to change your forms appearances. The image below explains how each style field can change the look of your forms.", 'custom-contact-forms'); ?>
				  </p>
				  <p>
					<?php _e("8. (advanced) If you are confident in your HTML and CSS skills, you can use the", 'custom-contact-forms'); ?>
					<a href="#custom-html">
					<?php _e("Custom HTML Forms feature", 'custom-contact-forms'); ?>
					</a>
					<?php _e("as a framework and write your forms from scratch. This allows you to use this plugin simply to process your form requests. The Custom HTML Forms feature will process and email any form variables sent to it regardless of whether they are created in the fields manager.", 'custom-contact-forms'); ?>
				  </p>
				  <p><span class="red bold">*</span>
					<?php _e("These instructions briefly tell you in which order you should use forms, fields, field options, and styles.", 'custom-contact-forms'); ?>
					<b>
					<?php _e("If you want to read in detail about using forms, fields, field options, styles and the rest of this plugin, click the button below.", 'custom-contact-forms'); ?>
					</b></p>
				  <div class="ccf-center">
					<input type="button" class="usage-popover-button" value="<?php _e("View Plugin Usage Popover", 'custom-contact-forms'); ?>" />
				  </div>
				  <div class="ccf-style-example"></div>
				  <div class="ccf-success-popover-example"></div>
				</div>
			  </div>
              <a name="configure-mail"></a>
			  <div id="configure-mail" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Mail Settings", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
                	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                	<p><?php _e("There are two ways you can send emails: using the PHP mail() function or using SMTP (secure/insecure). If you choose to use the PHP mail() function you can ignore all the other options. For some people Wordpress's default way of sending mail does not work; if for some reason your mail is being sent you should try the SMTP option.", 'custom-contact-forms'); ?></p>
                	<label for="mail_function"><?php _e("* Send My Emails Using the Following:", 'custom-contact-forms'); ?></label>
                    <select name="mail_config[mail_function]">
					  <option value="default"><?php _e("Wordpress Default", 'custom-contact-forms'); ?></option>
					  <option <?php if ($admin_options['mail_function'] == 'smtp') echo 'selected="selected"'; ?> value="smtp"><?php _e("SMTP", 'custom-contact-forms'); ?></option>
					</select> <?php _e("(If mail isn't sending, try toggling this option.)", 'custom-contact-forms'); ?>
                    <div>
                        <ul class="left">
                            <li><label for="smtp_host"><?php _e("SMTP Host:", 'custom-contact-forms'); ?></label> <input class="width125" type="text" size="10" name="mail_config[smtp_host]" value="<?php echo $admin_options['smtp_host']; ?>" /></li>
                            <li><label for="smtp_port"><?php _e("SMTP Port:", 'custom-contact-forms'); ?></label> <input class="width125" type="text" size="10" name="mail_config[smtp_port]" value="<?php echo $admin_options['smtp_port']; ?>" /></li>
                            <li><label for="smtp_encryption"><?php _e("Encryption:", 'custom-contact-forms'); ?></label> <select name="mail_config[smtp_encryption]">
                            <option value="none"><?php _e("None", 'custom-contact-forms'); ?></option>
                            <option <?php if ($admin_options['smtp_encryption'] == 'ssl') echo 'selected="selected"'; ?> value="ssl"><?php _e("SSL", 'custom-contact-forms'); ?></option>
                            <option <?php if ($admin_options['smtp_encryption'] == 'tls') echo 'selected="selected"'; ?> value="tls"><?php _e("TLS", 'custom-contact-forms'); ?></option>
                            </select></li>
                        </ul>
                        <ul class="right">
                            <li><label for="smtp_authentication"><?php _e("SMTP Authentication:", 'custom-contact-forms'); ?></label> <select name="mail_config[smtp_authentication]"><option value="0"><?php _e("None Needed", 'custom-contact-forms'); ?></option><option <?php if ($admin_options['smtp_authentication'] == 1) echo 'selected="selected"'; ?> value="1"><?php _e("Use SMTP Username/Password", 'custom-contact-forms'); ?></option></select></li>
                            <li><label for="smtp_username"><?php _e("SMTP Username:", 'custom-contact-forms'); ?></label> <input class="width125" type="text" size="10" name="mail_config[smtp_username]" value="<?php echo $admin_options['smtp_username']; ?>" /></li>
                            <li><label for="smtp_password"><?php _e("SMTP Password:", 'custom-contact-forms'); ?></label> <input class="width125" type="text" size="10" name="mail_config[smtp_password]" value="<?php echo $admin_options['smtp_password']; ?>" /></li>
                        </ul>
                    </div>
                    <input type="submit" name="configure_mail" value="<?php _e("Save Mail Sending Options", 'custom-contact-forms'); ?>" />
                    </form>
                </div>
              </div>
			  <a name="create-styles"></a>
			  <div id="create-styles" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Create A Style for Your Forms", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <p>
					<?php _e("Use this manager to create styles for your forms. Each field is already filled out with nice look defaults. It is recommended you simply input a slug and click create to see the defaults before you start changing values.", 'custom-contact-forms'); ?>
				  </p>
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<ul class="style_left">
					  <li>
						<label for="style_slug">*
						<?php _e("Style Slug:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="30" class="width75" name="style[style_slug]" />
						<?php _e("(Must be unique)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="title_fontsize">
						<?php _e("Title Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="1.2em" class="width75" name="style[title_fontsize]" />
						<?php _e("(ex: 10pt, 10px, 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="title_fontcolor">
						<?php _e("Title Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="333333" class="width75 colorfield" name="style[title_fontcolor]" />
						<?php _e("(ex: FF0000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="label_width">
						<?php _e("Label Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="200px" class="width75" name="style[label_width]" />
						<?php _e("(ex: 100px or 20%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="label_fontsize">
						<?php _e("Label Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="1em" class="width75" name="style[label_fontsize]" />
						<?php _e("(ex: 10px, 10pt, 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="label_fontcolor">
						<?php _e("Label Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="333333" class="width75 colorfield" name="style[label_fontcolor]" />
						<?php _e("(ex: FF0000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="input_width">
						<?php _e("Text Field Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="200px" class="width75" name="style[input_width]" />
						<?php _e("(ex: 100px or 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="textarea_width">
						<?php _e("Textarea Field Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="200px" class="width75" name="style[textarea_width]" />
						<?php _e("(ex: 100px or 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="textarea_height">
						<?php _e("Textarea Field Height:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="90px" class="width75" name="style[textarea_height]" />
						<?php _e("(ex: 100px or 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_fontsize">
						<?php _e("Field Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="1.3em" class="width75" name="style[field_fontsize]" />
						<?php _e("(ex: 10px, 10pt, 1em", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_fontcolor">
						<?php _e("Field Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="333333" class="width75 colorfield" name="style[field_fontcolor]" />
						<?php _e("(ex: 333333)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_borderstyle">
						<?php _e("Field Border Style:", 'custom-contact-forms'); ?>
						</label>
						<select class="width75" name="style[field_borderstyle]">
						  <?php echo str_replace('<option>solid</option>', '<option selected="selected">solid</option>', $border_style_options); ?>
						</select>
					  </li>
					  <li>
						<label for="form_margin">
						<?php _e("Form Margin:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="7px" class="width75" name="style[form_margin]" />
						<?php _e("(ex: 5px or 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="label_margin">
						<?php _e("Label Margin:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="5px" class="width75" name="style[label_margin]" />
						<?php _e("(ex: 5px or 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="textarea_backgroundcolor">
						<?php _e("Textarea Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="f5f5f5" class="width75 colorfield" name="style[textarea_backgroundcolor]" />
						<?php _e("(ex: FF0000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="success_popover_fontcolor">
						<?php _e("Success Popover Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="333333" class="width75 colorfield" name="style[success_popover_fontcolor]" />
						<?php _e("(ex: 333333)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="success_popover_title_fontsize">
						<?php _e("Success Popover Title Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="15px" class="width75" name="style[success_popover_title_fontsize]" />
						<?php _e("(ex: 12px, 1em, 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_backgroundcolor">
						<?php _e("Form Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="ffffff" class="width75 colorfield" name="style[form_backgroundcolor]" />
						<?php _e("(ex: 12px, 1em, 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="tooltip_backgroundcolor">
						<?php _e("Tooltip Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="000000" class="width75 colorfield" name="style[tooltip_backgroundcolor]" />
						<?php _e("(ex: 000000 or black)", 'custom-contact-forms'); ?>
					  </li>
					</ul>
					<ul class="style_right">
					  <li>
						<label for="input_width">
						<?php _e("Field Border Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="999999" class="width75 colorfield" name="style[field_bordercolor]" />
						<?php _e("(ex: 100px or 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_borderstyle">
						<?php _e("Form Border Style:", 'custom-contact-forms'); ?>
						</label>
						<select class="width75" name="style[form_borderstyle]">
						  <?php echo $border_style_options; ?>
						</select>
					  </li>
					  <li>
						<label for="form_bordercolor">
						<?php _e("Form Border Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="ffffff" class="width75 colorfield" name="style[form_bordercolor]" />
						<?php _e("(ex: 000000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_borderwidth">
						<?php _e("Form Border Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="0px" class="width75" name="style[form_borderwidth]" />
						<?php _e("(ex: 1px)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_borderwidth">
						<?php _e("Form Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="100%" class="width75" name="style[form_width]" />
						<?php _e("(ex: 100px or 50%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_borderwidth">
						<?php _e("Form Font Family:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="120" value="Verdana, tahoma, arial" class="width75" name="style[form_fontfamily]" />
						<?php _e("(ex: Verdana, Tahoma, Arial)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="submit_width">
						<?php _e("Button Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="auto" class="width75" name="style[submit_width]" />
						<?php _e("(ex: 100px, 30%, auto)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="submit_height">
						<?php _e("Button Height:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="30px" class="width75" name="style[submit_height]" />
						<?php _e("(ex: 100px or 30%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="submit_fontsize">
						<?php _e("Button Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="1.1em" class="width75" name="style[submit_fontsize]" />
						<?php _e("(ex: 10px, 10pt, 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="submit_fontcolor">
						<?php _e("Button Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="333333" class="width75 colorfield" name="style[submit_fontcolor]" />
						<?php _e("(ex: FF0000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_backgroundcolor">
						<?php _e("Field Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="f5f5f5" class="width75 colorfield" name="style[field_backgroundcolor]" />
						<?php _e("(ex: FF0000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="form_padding">
						<?php _e("Form Padding:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="8px" class="width75" name="style[form_padding]" />
						<?php _e("(ex: 5px or 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="title_margin">
						<?php _e("Title Margin:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="5px" class="width75" name="style[title_margin]" />
						<?php _e("(ex: 5px or 1em)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="title_margin">
						<?php _e("Dropdown Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="auto" class="width75" name="style[dropdown_width]" />
						<?php _e("(ex: 30px, 20%, or auto)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="success_popover_bordercolor">
						<?php _e("Success Popover Border Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="efefef" class="width75 colorfield" name="style[success_popover_bordercolor]" />
						<?php _e("(ex: FF0000)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="success_popover_fontsize">
						<?php _e("Success Popover Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="12px" class="width75" name="style[success_popover_fontsize]" />
						<?php _e("(ex: 12px, 1em, 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="success_popover_height">
						<?php _e("Success Popover Height:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="200px" class="width75" name="style[success_popover_height]" />
						<?php _e("(ex: 200px, 6em, 50%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="field_borderround">
						<?php _e("Field Border Roundness:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="6px" class="width75" name="style[field_borderround]" />
						<?php _e("(ex: 6px, or 0px)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="tooltip_fontsize">
						<?php _e("Tooltip", 'custom-contact-forms'); ?>
						<a href="javascript:void(0)" class="toollink" title="<?php _e("A tooltip is the little box that fades in displaying 'Field Instructions' when a user selects a particular field.", 'custom-contact-forms'); ?>">(?)</a>
						<?php _e("Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="12px" class="width75" name="style[tooltip_fontsize]" />
						<?php _e("(ex: 12px, 1em, 100%)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<label for="tooltip_fontcolor">
						<?php _e("Tooltip Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="ffffff" class="width75 colorfield" name="style[tooltip_fontcolor]" />
						<?php _e("(ex: ffffff or white)", 'custom-contact-forms'); ?>
					  </li>
					  <li>
						<input type="submit" value="<?php _e("Create Style", 'custom-contact-forms'); ?>" name="style_create" />
					  </li>
					</ul>
				  </form>
				</div>
			  </div>
			  <a name="manage-styles"></a>
			  <h3 class="manage-h3">
				<?php _e("Manage Form Styles", 'custom-contact-forms'); ?>
			  </h3>
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
					  <td><label>*
						<?php _e("Slug:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="30" value="<?php echo $style->style_slug; ?>" name="style[style_slug]" />
						<br />
						<label>
						<?php _e("Font Family:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="120" value="<?php echo $style->form_fontfamily; ?>" name="style[form_fontfamily]" />
						<br />
						<label>
						<?php _e("Textarea Background", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->textarea_backgroundcolor; ?>" name="style[textarea_backgroundcolor]" />
						<br />
						<label>
						<?php _e("Success Popover", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Border Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->success_popover_bordercolor; ?>" name="style[success_popover_bordercolor]" />
						<br />
						<label>
						<?php _e("Tooltip", 'custom-contact-forms'); ?>
						<a href="javascript:void(0)" class="toollink" title="<?php _e("A tooltip is the little box that fades in displaying 'Field Instructions' when a user selects a particular field.", 'custom-contact-forms'); ?>">(?)</a>
						<?php _e("Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->tooltip_fontcolor; ?>" name="style[tooltip_fontcolor]" />
						<br />
						<input type="submit" class="submit-styles edit-button" name="style_edit" value="<?php _e("Save", 'custom-contact-forms'); ?>" />
						<br />
						<input type="submit" class="submit-styles delete-button" name="style_delete" value="<?php _e("Delete Style", 'custom-contact-forms'); ?>" />
					  </td>
					  <td><label>
						<?php _e("Form Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->form_width; ?>" name="style[form_width]" />
						<br />
						<label>
						<?php _e("Text Field Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->input_width; ?>" name="style[input_width]" />
						<br />
						<label>
						<?php _e("Textarea Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->textarea_width; ?>" name="style[textarea_width]" />
						<br />
						<label>
						<?php _e("Textarea Height:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->textarea_height; ?>" name="style[textarea_height]" />
						<br />
						<label>
						<?php _e("Dropdown Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->dropdown_width; ?>" name="style[dropdown_width]" />
						<br />
						<label>
						<?php _e("Label Margin:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->label_margin; ?>" name="style[label_margin]" />
						<br />
						<label>
						<?php _e("Success Popover", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Height:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->success_popover_height; ?>" name="style[success_popover_height]" />
						<br />
					  </td>
					  <td><label>
						<?php _e("Label Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->label_width; ?>" name="style[label_width]" />
						<br />
						<label>
						<?php _e("Button Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->submit_width; ?>" name="style[submit_width]" />
						<br />
						<label>
						<?php _e("Button Height:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->submit_height; ?>" name="style[submit_height]" />
						<br />
						<label>
						<?php _e("Field Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->field_backgroundcolor; ?>" name="style[field_backgroundcolor]" />
						<br />
						<label>
						<?php _e("Title Margin:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->title_margin; ?>" name="style[title_margin]" />
						<br />
						<label>
						<?php _e("Success Popover", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Title Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->success_popover_title_fontsize; ?>" name="style[success_popover_title_fontsize]" />
						<label>
						<?php _e("Form Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" class="colorfield" maxlength="20" value="<?php echo $style->form_backgroundcolor; ?>" name="style[form_backgroundcolor]" />
					  </td>
					  <td><label>
						<?php _e("Title Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->title_fontsize; ?>" name="style[title_fontsize]" />
						<br />
						<label>
						<?php _e("Label Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->label_fontsize; ?>" name="style[label_fontsize]" />
						<br />
						<label>
						<?php _e("Field Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->field_fontsize; ?>" name="style[field_fontsize]" />
						<br />
						<label>
						<?php _e("Button Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->submit_fontsize; ?>" name="style[submit_fontsize]" />
						<br />
						<label>
						<?php _e("Form Padding:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->form_padding; ?>" name="style[form_padding]" />
						<br />
						<label>
						<?php _e("Success Popover", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->success_popover_fontsize; ?>" name="style[success_popover_fontsize]" />
						<br />
						<label>
						<?php _e("Tooltip", 'custom-contact-forms'); ?>
						<a href="javascript:void(0)" class="toollink" title="<?php _e("A tooltip is the little box that fades in displaying 'Field Instructions' when a user selects a particular field.", 'custom-contact-forms'); ?>">(?)</a>
						<?php _e("Background Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->tooltip_backgroundcolor; ?>" name="style[tooltip_backgroundcolor]" />
					  </td>
					  <td><label>
						<?php _e("Title Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->title_fontcolor; ?>" name="style[title_fontcolor]" />
						<br />
						<label>
						<?php _e("Label Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->label_fontcolor; ?>" name="style[label_fontcolor]" />
						<br />
						<label>
						<?php _e("Field Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->field_fontcolor; ?>" name="style[field_fontcolor]" />
						<br />
						<label>
						<?php _e("Button Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->submit_fontcolor; ?>" name="style[submit_fontcolor]" />
						<br />
						<label>
						<?php _e("Form Margin:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->form_margin; ?>" name="style[form_margin]" />
						<br />
						<label>
						<?php _e("Success Popover", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->success_popover_fontcolor; ?>" name="style[success_popover_fontcolor]" />
						<br />
						<label>
						<?php _e("Tooltip Font Size:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->tooltip_fontsize; ?>" name="style[tooltip_fontsize]" />
					  </td>
					  <td><label>
						<?php _e("Form Border Style:", 'custom-contact-forms'); ?>
						</label>
						<select name="style[form_borderstyle]">
						  <?php echo str_replace('<option>'.$style->form_borderstyle.'</option>', '<option selected="selected">'.$style->form_borderstyle.'</option>', $border_style_options); ?>
						</select>
						<br />
						<label>
						<?php _e("Form Border Width:", 'custom-contact-forms'); ?>
						</label>
						<input type="text" maxlength="20" value="<?php echo $style->form_borderwidth; ?>" name="style[form_borderwidth]" />
						<br />
						<label>
						<?php _e("Form Border Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->form_bordercolor; ?>" name="style[form_bordercolor]" />
						<br />
						<label>
						<?php _e("Field Border Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->field_bordercolor; ?>" name="style[field_bordercolor]" />
						<br />
						<label>
						<?php _e("Field Border Style:", 'custom-contact-forms'); ?>
						</label>
						<select name="style[field_borderstyle]">
						  <?php echo str_replace('<option>'.$style->field_borderstyle.'</option>', '<option selected="selected">'.$style->field_borderstyle.'</option>', $border_style_options); ?>
						</select>
						<br />
						<label>
						<?php _e("Success Popover", 'custom-contact-forms'); ?>
						<br />
						<?php _e("Title Font Color:", 'custom-contact-forms'); ?>
						</label>
						<input class="colorfield" type="text" maxlength="20" value="<?php echo $style->success_popover_title_fontcolor; ?>" name="style[success_popover_title_fontcolor]" />
						<br />
						<label>
						<?php _e("Field Border Roundness:", 'custom-contact-forms'); ?>
						</label>
						<input name="style[field_borderround]" value="<?php echo $style->field_borderround; ?>" type="text" maxlength="20" />
						<br />
						<input type="hidden" class="object-type" name="object_type" value="style" />
						<input name="sid" type="hidden" class="object-id" value="<?php echo $style->id; ?>" />
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
			  </table>
			  <a name="contact-author"></a>
			  <div id="contact-author" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Report a Bug/Suggest a Feature", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<ul>
					  <li>
						<label for="name">
						<?php _e("Your Name:", 'custom-contact-forms'); ?>
						</label>
						<input id="name" type="text" name="name" maxlength="100" />
					  </li>
					  <li>
						<label for="email">
						<?php _e("Your Email:", 'custom-contact-forms'); ?>
						</label>
						<input id="email" type="text" value="<?php echo get_option('admin_email'); ?>" name="email" maxlength="100" />
					  </li>
					  <li>
						<label for="message">*
						<?php _e("Your Message:", 'custom-contact-forms'); ?>
						</label>
						<textarea id="message" name="message"></textarea>
					  </li>
					  <li>
						<label for="type">*
						<?php _e("Purpose of this message:", 'custom-contact-forms'); ?>
						</label>
						<select id="type" name="type">
						  <option>
						  <?php _e("Bug Report", 'custom-contact-forms'); ?>
						  </option>
						  <option>
						  <?php _e("Suggest a Feature", 'custom-contact-forms'); ?>
						  </option>
						  <option>
						  <?php _e("Plugin Question", 'custom-contact-forms'); ?>
						  </option>
						</select>
					  </li>
					</ul>
					<p>
					  <input type="submit" name="contact_author" value="<?php _e("Send Message", 'custom-contact-forms'); ?>" />
					</p>
				  </form>
				</div>
			  </div>
			  <a name="custom-html"></a>
			  <div id="custom-html" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Custom HTML Forms (Advanced)", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <p>
					<?php _e("If you know HTML and simply want to use this plugin to process form requests, this feature is for you. 
					The following HTML is a the framework to which you must adhere. In order for your form to work you MUST do the following: a) Keep the form action/method the same (yes the action is supposed to be empty), b) Include all the hidden fields shown below, c) provide a 
					hidden field with a success message or thank you page (both hidden fields are included below, you must choose one or the other and fill in the value part of the input field appropriately.", 'custom-contact-forms'); ?>
				  </p>
				  <textarea id="custom_html_textarea">
&lt;form method=&quot;post&quot; action=&quot;&quot;&gt;
&lt;input type=&quot;hidden&quot; name=&quot;ccf_customhtml&quot; value=&quot;1&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;success_message&quot; value=&quot;<?php _e("Thank you for filling out our form!", 'custom-contact-forms'); ?>&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;thank_you_page&quot; value=&quot;http://www.google.com&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;destination_email&quot; value=&quot;<?php echo $admin_options['default_to_email']; ?>&quot; /&gt;
&lt;input type=&quot;hidden&quot; name=&quot;required_fields&quot; value=&quot;field_name1, field_name2&quot; /&gt;

&lt;!-- <?php _e("Build your form in here. It is recommended you only use this feature if you are experienced with HTML. 
The success_message field will add a popover containing the message when the form is completed successfully, the thank_you_page field will force 
the user to be redirected to that specific page on successful form completion. The required_fields hidden field is optional; to use it seperate 
the field names you want required by commas. Remember to use underscores instead of spaces in field names!", 'custom-contact-forms'); ?> --&gt;

&lt;/form&gt;</textarea>
				</div>
			  </div>
			  <a name="import-export"></a>
			  <div id="export" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Export", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<p>
					  <?php _e("Preforming a Custom Contact Forms export will create a file of the form 
						ccf-export-xxxx.sql on your web server. The file created contains SQL that 
						will recreate all the plugin data on any Wordpress installation. After Custom Contact Forms creates the export file, you will be prompted to download it. You can use this file as a backup in case your Wordpress database gets ruined.", 'custom-contact-forms'); ?>
					</p>
					<input type="submit" name="ccf_export" value="<?php _e("Export All CCF Plugin Content", 'custom-contact-forms'); ?>" />
				  </form>
				</div>
			  </div>
			  <div id="import" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Import", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
					<p>
					  <?php _e("Browse to a CCF .sql export file to import Custom Contact Form data from another Wordpress installation to this one. Pressing the 'Clear and Import' button deletes all current data and then imports the selected file; this will not work for merging to data!. Clearing all CCF data before importing prevents any conflicts from occuring. Before you attempt an import, you should always download a backup, by clicking the 'Export All' button.", 'custom-contact-forms'); ?>
					</p>
					<p class="choose_import">
					  <?php _e("Choose What You Want to Use from the Import File:", 'custom-contact-forms'); ?>
					</p>
					<ul>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_overwrite_settings" value="1" />
						<label for="ccf_import_overwrite_settings">
						<?php _e("Use General Settings", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_forms" value="1" />
						<label for="ccf_import_forms">
						<?php _e("Forms", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_saved_submissions" value="1" />
						<label for="ccf_import_saved_submissions">
						<?php _e("Form Submissions", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_fields" value="1" />
						<label for="ccf_import_fields">
						<?php _e("Fields", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_forms" value="1" />
						<label for="ccf_import_forms">
						<?php _e("Forms", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_field_options" value="1" />
						<label for="ccf_import_field_options">
						<?php _e("Field Options", 'custom-contact-forms'); ?>
						</label>
					  </li>
					  <li>
						<input type="checkbox" checked="checked" name="ccf_import_styles" value="1" />
						<label for="ccf_import_styles">
						<?php _e("Styles", 'custom-contact-forms'); ?>
						</label>
					  </li>
					</ul>
					<p class="choose_import">
					  <label for="import_file">
					  <?php _e("Choose an Import File:", 'custom-contact-forms'); ?>
					  </label>
					  <input type="file" name="import_file" />
					</p>
					<input name="ccf_clear_import" type="submit" value="<?php _e("Clear and Import", 'custom-contact-forms'); ?>" />
					<input type="checkbox" name="ccf_import_confirm" value="1" />
					<?php _e('Yes, I want to do this and have created a backup.', 'custom-contact-forms'); ?>
				  </form>
				</div>
			  </div>
			  <a name="plugin-news"></a>
			  <div id="plugin-news" class="postbox">
				<h3 class="hndle"><span>
				  <?php _e("Custom Contact Forms Plugin News", 'custom-contact-forms'); ?>
				  </span></h3>
				<div class="inside">
				  <?php $this->displayPluginNewsFeed(); ?>
				</div>
			  </div>
			</div>
<?php
		}
	}
}
?>