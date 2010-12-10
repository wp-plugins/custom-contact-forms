<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsFront')) {
	class CustomContactFormsFront extends CustomContactForms {
		var $form_errors = array();
		var $error_return;
		var $current_form;
		var $current_thank_you_message;

		function frontInit() {
			CustomContactFormsStatic::startSession();
			$this->processForms();
		}
	
		function insertFrontEndStyles() {
            wp_register_style('CCFStandardsCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms-standards.css');
           	wp_register_style('CCFFormsCSS', WP_PLUGIN_URL . '/custom-contact-forms/css/custom-contact-forms.css');
           	wp_enqueue_style('CCFStandardsCSS');
			wp_enqueue_style('CCFFormsCSS');
		}
		
		function insertFrontEndScripts() { 
			$admin_options = parent::getAdminOptions();
			if ($admin_options['enable_jquery'] == 1) {
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-tools', WP_PLUGIN_URL . '/custom-contact-forms/js/jquery.tools.min.js');
				wp_enqueue_script('ccf-main', WP_PLUGIN_URL . '/custom-contact-forms/js/custom-contact-forms.js', array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-resizable'), '1.0');
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
		
		function insertFormSuccessCode() {
			$admin_options = parent::getAdminOptions();
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
		
		function validEmail($email) {
		  if (!@preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) return false;
		  $email_array = explode("@", $email);
		  $local_array = explode(".", $email_array[0]);
		  for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!@preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
			  return false;
			}
		  } if (!@preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) {
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) return false;
			for ($i = 0; $i < sizeof($domain_array); $i++) {
			  if (!@preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
				return false;
			  }
			}
		  }
		  return true;
		}
		
		function getFormCode($fid, $is_sidebar = false, $popover = false) {
			$admin_options = parent::getAdminOptions();
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
			if (!empty($form_title) && !$is_sidebar) $out .= '<h4 id="h4-' . $form->id . '-' . $form_key . '">' . $form_title . '</h4>' . "\n";
			$fields = parent::getAttachedFieldsArray($fid);
			$hiddens = '';
			$code_type = ($admin_options['code_type'] == 'XHTML') ? ' /' : '';
			$add_reset = '';
			foreach ($fields as $field_id) {
				$field = parent::selectField($field_id, '');
				$req = ($field->field_required == 1 or $field->field_slug == 'ishuman') ? '* ' : '';
				$req_long = ($field->field_required == 1) ? ' ' . __('(required)', 'custom-contact-forms') : '';
				$input_id = 'id="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'-'.$form_key.'"';
				$field_value = CustomContactFormsStatic::decodeOption($field->field_value, 1, 1);
				$instructions = (empty($field->field_instructions)) ? '' : 'title="' . $field->field_instructions . $req_long . '" ';
				$tooltip_class = (empty($field->field_instructions)) ? '' : 'ccf-tooltip-field';
				if ($admin_options['enable_widget_tooltips'] == 0 && $is_sidebar) $instructions = '';
				if ($_SESSION['fields'][$field->field_slug]) {
					if ($admin_options['remember_field_values'] == 1)
						$field_value = $_SESSION['fields'][$field->field_slug];
				} if ($field->field_slug == 'captcha') {
					$out .= '<div>' . "\n" . $this->getCaptchaCode($form->id) . "\n" . '</div>' . "\n";
				} elseif ($field->field_slug == 'resetButton') {
					$add_reset = ' <input type="reset" '.$instructions.' class="reset-button '.$field->field_class.'" value="' . $field->field_value . '" />';
				} elseif ($field->field_type == 'Text') {
					$maxlength = (empty($field->field_maxlength) or $field->field_maxlength <= 0) ? '' : ' maxlength="'.$field->field_maxlength.'"';
					$out .= '<div>'."\n".'<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<input class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' '.$input_id.' type="text" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'"'.$maxlength.''.$code_type.'>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Hidden') {
					$hiddens .= '<input type="hidden" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'" '.$input_id.''.$code_type.'>' . "\n";
				} elseif ($field->field_type == 'Checkbox') {
					$out .= '<div>'."\n".'<input class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' type="checkbox" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.CustomContactFormsStatic::decodeOption($field->field_value, 1, 1).'" '.$input_id.''.$code_type.'> '."\n".'<label class="checkbox" for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">' . $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Textarea') {
					$out .= '<div>'."\n".'<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<textarea class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' '.$input_id.' rows="5" cols="40" name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'.$field_value.'</textarea>'."\n".'</div>' . "\n";
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
						$out .= '<div>'."\n".'<label for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'. $req .CustomContactFormsStatic::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<select class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' '.$input_id.' name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">'."\n".$field_options.'</select>'."\n".'</div>' . "\n";
					}
				} elseif ($field->field_type == 'Radio') {
					$field_options = '';
					$options = parent::getAttachedFieldOptionsArray($field->id);
					foreach ($options as $option_id) {
						$option = parent::selectFieldOption($option_id);
						$option_sel = ($field->field_value == $option->option_slug) ? ' checked="checked"' : '';
						$field_options .= '<div><input'.$option_sel.' class="'.$field->field_class.' '.$tooltip_class.'" type="radio" '.$instructions.' name="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'" value="'.CustomContactFormsStatic::decodeOption($option->option_value, 1, 1).'"'.$code_type.'> <label class="select" for="'.CustomContactFormsStatic::decodeOption($field->field_slug, 1, 1).'">' . CustomContactFormsStatic::decodeOption($option->option_label, 1, 1) . '</label></div>' . "\n";
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
			
			return $form_styles . $out;
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
			if ($_POST['ccf_customhtml'] || $_POST['customcontactforms_submit']) {
				// BEGIN define common language vars
				$lang = array();
				$lang['field_blank'] = __('You left this field blank: ', 'custom-contact-forms');
				$lang['form_page'] = __('Form Displayed on Page: ', 'custom-contact-forms');
				$lang['sender_ip'] = __('Sender IP: ', 'custom-contact-forms');
				// END define common language vars
			} if ($_POST['ccf_customhtml']) {
				$admin_options = parent::getAdminOptions();
				$fixed_customhtml_fields = array('required_fields', 'success_message', 'thank_you_page', 'destination_email', 'ccf_customhtml');
				$req_fields = $this->requiredFieldsArrayFromList($_POST['required_fields']);
				$req_fields = array_map('trim', $req_fields);
				$body = '';
				foreach ($_POST as $key => $value) {
					if (!in_array($key, $fixed_customhtml_fields)) {
						if (in_array($key, $req_fields) && !empty($value)) {
							unset($req_fields[array_search($key, $req_fields)]);
						}
						$body .= ucwords(str_replace('_', ' ', htmlspecialchars($key))) . ': ' . htmlspecialchars($value) . "<br />\n";
						$data_array[$key] = $value;
					}
				} foreach($req_fields as $err)
					$this->setFormError($err, $lang['field_blank'] . '"' . $err . '"');
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					require_once('modules/export/custom-contact-forms-user-data.php');
					$data_object = new CustomContactFormsUserData(array('data_array' => $data_array, 'form_page' => $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'], 'form_id' => 0, 'data_time' => time()));
					parent::insertUserData($data_object);
					$body .= "<br />\n" . htmlspecialchars($lang['form_page']) . $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'] . "<br />\n" . $lang['sender_ip'] . $_SERVER['REMOTE_ADDR'] . "<br />\n";
					if ($admin_options['email_form_submissions'] == 1) {
						if (!class_exists('PHPMailer'))
							require_once(ABSPATH . "wp-includes/class-phpmailer.php"); 
						$mail = new PHPMailer(false);
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
						$mail->From = $admin_options['default_from_email'];
						$mail->FromName = 'Custom Contact Forms';
						$mail->AddAddress($_POST['destination_email']);
						$mail->Subject = $admin_options['default_form_subject'];
						$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
						$mail->MsgHTML(stripslashes($body));
						$mail->Send();
					} if ($_POST['thank_you_page'])
						CustomContactFormsStatic::redirect($_POST['thank_you_page']);
					$this->current_thank_you_message = (!empty($_POST['success_message'])) ? $_POST['success_message'] : $admin_options['form_success_message'];
					$this->current_form = 0;
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			} elseif ($_POST['customcontactforms_submit']) {
				CustomContactFormsStatic::startSession();
				$this->error_return = $_POST['form_page'];
				$admin_options = parent::getAdminOptions();
				$fields = parent::getAttachedFieldsArray($_POST['fid']);
				$form = parent::selectForm($_POST['fid']);
				$checks = array();
				$reply = ($_POST['fixedEmail']) ? $_POST['fixedEmail'] : NULL;
				$cap_name = 'captcha_' . $_POST['fid'];
				foreach ($fields as $field_id) {
					$field = parent::selectField($field_id, '');
					 if ($field->field_slug == 'ishuman') {
						if ($_POST['ishuman'] != 1) {
							if (empty($field->field_error))
								$this->setFormError('ishuman', __('Only humans can use this form.', 'custom-contact-forms'));
							else $this->setFormError('ishuman', $field->field_error);
						}
					} elseif ($field->field_slug == 'captcha') {
						if ($_POST['captcha'] != $_SESSION[$cap_name]) {
							if (empty($field->field_error))
								$this->setFormError('captcha', __('You copied the number from the captcha field incorrectly.', 'custom-contact-forms'));
							else $this->setFormError('captcha', $field->field_error);
						}
					} elseif ($field->field_slug == 'fixedEmail' && $field->field_required == 1 && !empty($_POST['fixedEmail'])) {
						if (!$this->validEmail($_POST['fixedEmail'])) {
							if (empty($field->field_error))
								$this->setFormError('fixedEmail', __('The email address you provided is not valid.', 'custom-contact-forms'));
							else $this->setFormError('fixedEmail', $field->field_error);
						}
					} else {
						if ($field->field_required == 1 && empty($_POST[$field->field_slug])) {
							$field_error_label = (empty($field->field_label)) ? $field->field_slug : $field->field_label;
							if (empty($field->field_error))
								$this->setFormError($field->field_slug, $lang['field_blank'] . '"'.$field_error_label.'"');
							else $this->setFormError($field->field_slug, $field->field_error);
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
						$body .= htmlspecialchars($mail_field_label) . ': ' . htmlspecialchars($value) . "<br />\n";
						$data_array[$key] = $value;
					} if (in_array($key, $checks)) {
						$checks_key = array_search($key, $checks);
						unset($checks[$checks_key]);
					}
				} foreach ($checks as $check_key) {
					$field = parent::selectField('', $check_key);
					$lang['not_checked'] = __('Not Checked', 'custom-contact-forms');
					$data_array[$check_key] = $lang['not_checked'];
					$body .= ucwords(str_replace('_', ' ', htmlspecialchars($field->field_label))) . ': ' . $lang['not_checked'] . "\n";
				}
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					require_once('modules/export/custom-contact-forms-user-data.php');
					unset($_SESSION['captcha_' . $_POST['fid']]);
					unset($_SESSION['fields']);
					$data_object = new CustomContactFormsUserData(array('data_array' => $data_array, 'form_page' => $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 'form_id' => $form->id, 'data_time' => time()));
					parent::insertUserData($data_object);
					if ($admin_options['email_form_submissions'] == '1') {
						$body .= "<br />\n" . htmlspecialchars($lang['form_page']) . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . "<br />\n" . $lang['sender_ip'] . $_SERVER['REMOTE_ADDR'] . "<br />\n";
						if (!class_exists('PHPMailer'))
							require_once(ABSPATH . "wp-includes/class-phpmailer.php"); 
						$mail = new PHPMailer(false);
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
						$dest_email_array = $this->getDestinationEmailArray($form->form_email);
						if (empty($dest_email_array)) $mail->AddAddress($admin_options['default_to_email']);
						else {
							foreach ($dest_email_array as $em)
								$mail->AddAddress($em);
						}
						$mail->FromName = 'Custom Contact Forms';
						if ($reply != NULL && $this->validEmail($reply)) {
							$mail->From = $reply;
						} else {
							$mail->From = $admin_options['default_from_email'];
						}
						$mail->Subject = $admin_options['default_form_subject'];
						$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
						$mail->MsgHTML(stripslashes($body));
						$mail->Send();
					} if (!empty($form->form_thank_you_page))
						CustomContactFormsStatic::redirect($form->form_thank_you_page);
					$this->current_form = $form->id;
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			}
		}
		
		function getCaptchaCode($form_id) {
			$admin_options = parent::getAdminOptions();
			$code_type = ($admin_options['code_type'] == 'XHTML') ? ' /' : '';
			$captcha = parent::selectField('', 'captcha');
			$instructions = (empty($captcha->field_instructions)) ? '' : 'title="'.$captcha->field_instructions.'" ';
			$tooltip_class = (empty($captcha->field_instructions)) ? '' : 'ccf-tooltip-field';
			$out = '<img width="96" height="24" alt="' . __('Captcha image for Custom Contact Forms plugin. You must type the numbers shown in the image', 'custom-contact-forms') . '" id="captcha-image" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/custom-contact-forms/image.php?fid='.$form_id.'"'.$code_type.'> 
			<div><label for="captcha'.$form_id.'">* '.$captcha->field_label.'</label> <input class="'.$captcha->field_class.' '.$tooltip_class.'" type="text" '.$instructions.' name="captcha" id="captcha'.$form_id.'" maxlength="20"'.$code_type.'></div>';
			return $out;
		}
		
		function getDestinationEmailArray($str) {
			$str = str_replace(',', ';', $str);
			$email_array = explode(';', $str);
			$email_array2 = array();
			foreach ($email_array as $k => $v) {
				if (!empty($email_array[$k])) $email_array2[] = trim($v);
			}
			return $email_array2;
		}
	}
}
?>