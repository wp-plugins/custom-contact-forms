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
			ccf_utils::startSession();
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
		
		function shortCodeToForm($atts) {
			extract(shortcode_atts(array(
				'form' => 0,
			), $atts));
			$this_form = parent::selectForm($form);
			if (empty($this_form))
				return '';
			elseif (!$this->userCanViewForm($this_form)) {
				$admin_options = parent::getAdminOptions();
				return $admin_options['default_form_bad_permissions'];
			} else
				return $this->getFormCode($this_form);
		}
		
		function contentFilter($content) {
			$errors = $this->getAllFormErrors();
			if (!empty($errors)) {
				$admin_options = parent::getAdminOptions();
				$out = '<div id="custom-contact-forms-errors"><p>'.$admin_options['default_form_error_header'].'</p><ul>' . "\n";
				$errors = $this->getAllFormErrors();
				foreach ($errors as $error) {
					$out .= '<li>'.$error.'</li>' . "\n";
				}
				$err_link = (!empty($this->error_return)) ? '<p><a href="'.$this->error_return.'" title="Go Back">&lt; ' . __('Go Back to Form.', 'custom-contact-forms') . '</a></p>' : '';
				return $out . '</ul>' . "\n" . $err_link . '</div>';
			}
			return $content;
			/*
			$matches = array();
			preg_match_all('/\[customcontact form=([0-9]+)\]/si', $content, $matches);
			$matches_count = count($matches[0]);
			for ($i = 0; $i < $matches_count; $i++) {
				$this_form = parent::selectForm($matches[1][$i]);
				if ($this_form == false)
					$form_replace = '';
				if (!$this->userCanViewForm($this_form))
					$form_replace = __("You don't have the proper permissions to view this form.", 'custom-contact-forms');
				else
					$form_replace = $this->getFormCode($this_form);
				$content = str_replace($matches[0][$i], $form_replace, $content);
			}
			return $content;*/
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
					#ccf-form-success { z-index:10000; border-color:#<?php echo parent::formatStyle($style->success_popover_bordercolor); ?>; height:<?php $style->success_popover_height; ?>; }
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
		
		function validWebsite($website) {
			return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
		}
		
		function getFormCode($form, $is_widget_form = false) {
			if (empty($form)) return '';
			$admin_options = parent::getAdminOptions();
			$form_key = time();
			$out = '';
			$form_styles = '';
			$style_class = (!$is_widget_form) ? ' customcontactform' : ' customcontactform-sidebar';
			$form_id = 'form-' . $form->id . '-'.$form_key;
			if ($form->form_style != 0) {
				$style = parent::selectStyle($form->form_style, '');
				$style_class = $style->style_slug;
			}
			$form_method = (empty($form->form_method)) ? 'post' : strtolower($form->form_method);
			$form_title = ccf_utils::decodeOption($form->form_title, 1, 1);
			$action = (!empty($form->form_action)) ? $form->form_action : $_SERVER['REQUEST_URI'];
			$out .= '<form id="'.$form_id.'" method="'.$form_method.'" action="'.$action.'" class="'.$style_class.'">' . "\n";
			$out .= ccf_utils::decodeOption($form->custom_code, 1, 1) . "\n";
			if (!empty($form_title) && !$is_widget_form) $out .= '<h4 id="h4-' . $form->id . '-' . $form_key . '">' . $form_title . '</h4>' . "\n";
			$fields = parent::getAttachedFieldsArray($form->id);
			$hiddens = '';
			$code_type = ($admin_options['code_type'] == 'XHTML') ? ' /' : '';
			$add_reset = '';
			foreach ($fields as $field_id) {
				$field = parent::selectField($field_id, '');
				$req = ($field->field_required == 1 or $field->field_slug == 'ishuman') ? '* ' : '';
				$req_long = ($field->field_required == 1) ? ' ' . __('(required)', 'custom-contact-forms') : '';
				$input_id = 'id="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'-'.$form_key.'"';
				$field_value = ccf_utils::decodeOption($field->field_value, 1, 1);
				$instructions = (empty($field->field_instructions)) ? '' : 'title="' . $field->field_instructions . $req_long . '" ';
				$tooltip_class = (empty($field->field_instructions)) ? '' : 'ccf-tooltip-field';
				if ($admin_options['enable_widget_tooltips'] == 0 && $is_widget_form) $instructions = '';
				if ($_SESSION['fields'][$field->field_slug]) {
					if ($admin_options['remember_field_values'] == 1)
						$field_value = $_SESSION['fields'][$field->field_slug];
				} if ($field->field_slug == 'captcha') {
					$out .= '<div>' . "\n" . $this->getCaptchaCode($field, $form->id) . "\n" . '</div>' . "\n";
				} elseif ($field->field_slug == 'usaStates') {
					$out .= '<div>' . "\n" . $this->getStatesCode($field, $form->id) . "\n" . '</div>' . "\n";
				} elseif ($field->field_slug == 'allCountries') {
					$out .= '<div>' . "\n" . $this->getCountriesCode($field, $form->id) . "\n" . '</div>' . "\n";
				} elseif ($field->field_slug == 'resetButton') {
					$add_reset = ' <input type="reset" '.$instructions.' class="reset-button '.$field->field_class.' '.$tooltip_class.'" value="' . $field->field_value . '" />';
				} elseif ($field->field_type == 'Text') {
					$maxlength = (empty($field->field_maxlength) or $field->field_maxlength <= 0) ? '' : ' maxlength="'.$field->field_maxlength.'"';
					$out .= '<div>'."\n".'<label for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<input class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' '.$input_id.' type="text" name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'"'.$maxlength.''.$code_type.'>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Hidden') {
					$hiddens .= '<input type="hidden" name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'" value="'.$field_value.'" '.$input_id.''.$code_type.'>' . "\n";
				} elseif ($field->field_type == 'Checkbox') {
					$out .= '<div>'."\n".'<input class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' type="checkbox" name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'" value="'.ccf_utils::decodeOption($field->field_value, 1, 1).'" '.$input_id.''.$code_type.'> '."\n".'<label class="checkbox" for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">' . $req .ccf_utils::decodeOption($field->field_label, 1, 1).'</label>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Textarea') {
					$out .= '<div>'."\n".'<label for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<textarea class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' '.$input_id.' rows="5" cols="40" name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'.$field_value.'</textarea>'."\n".'</div>' . "\n";
				} elseif ($field->field_type == 'Dropdown') {
					$field_options = '';
					$options = parent::getAttachedFieldOptionsArray($field->id);
					foreach ($options as $option_id) {
						$option = parent::selectFieldOption($option_id);
						$option_sel = ($field->field_value == $option->option_slug) ? ' selected="selected"' : '';
						$option_value = (!empty($option->option_value)) ? ' value="' . $option->option_value . '"' : '';
						// Weird way of marking a state dead. TODO: Find another way.
						$option_value = ($option->option_dead == 1) ? ' value="' . CCF_DEAD_STATE_VALUE . '"' : $option_value;
						$field_options .= '<option'.$option_sel.''.$option_value.'>' . $option->option_label . '</option>' . "\n";
					}
					if (!empty($options)) {
						if (!$is_widget_form) $out .= '<div>'."\n".'<label class="select" for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<select '.$instructions.' '.$input_id.' name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'" class="'.$field->field_class.' '.$tooltip_class.'">'."\n".$field_options.'</select>'."\n".'</div>' . "\n";
						else  $out .= '<div>'."\n".'<label for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field->field_label, 1, 1).'</label>'."\n".'<select class="'.$field->field_class.' '.$tooltip_class.'" '.$instructions.' '.$input_id.' name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'."\n".$field_options.'</select>'."\n".'</div>' . "\n";
					}
				} elseif ($field->field_type == 'Radio') {
					$field_options = '';
					$options = parent::getAttachedFieldOptionsArray($field->id);
					foreach ($options as $option_id) {
						$option = parent::selectFieldOption($option_id);
						$option_sel = ($field->field_value == $option->option_slug) ? ' checked="checked"' : '';
						$field_options .= '<div><input'.$option_sel.' class="'.$field->field_class.' '.$tooltip_class.'" type="radio" '.$instructions.' name="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'" value="'.ccf_utils::decodeOption($option->option_value, 1, 1).'"'.$code_type.'> <label class="select" for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">' . ccf_utils::decodeOption($option->option_label, 1, 1) . '</label></div>' . "\n";
					}
					$field_label = (!empty($field->field_label)) ? '<label for="'.ccf_utils::decodeOption($field->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field->field_label, 1, 1).'</label>' : '';
					if (!empty($options)) $out .= '<div>'."\n".$field_label."\n".$field_options."\n".'</div>' . "\n";
				}
			}
			$submit_text = (!empty($form->submit_button_text)) ? ccf_utils::decodeOption($form->submit_button_text, 1, 0) : 'Submit';
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
						$body .= ucwords(str_replace('_', ' ', htmlspecialchars($key))) . ': ' . htmlspecialchars($value) . "<br /><br />\n";
						$data_array[$key] = $value;
					}
				} foreach($req_fields as $err)
					$this->setFormError($err, $lang['field_blank'] . '"' . $err . '"');
				$errors = $this->getAllFormErrors();
				if (empty($errors)) {
					ccf_utils::load_module('export/custom-contact-forms-user-data.php');
					$data_object = new CustomContactFormsUserData(array('data_array' => $data_array, 'form_page' => $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'], 'form_id' => 0, 'data_time' => time()));
					parent::insertUserData($data_object);
					$body .= "<br />\n" . htmlspecialchars($lang['form_page']) . $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI'] . "<br />\n" . $lang['sender_ip'] . $_SERVER['REMOTE_ADDR'] . "<br />\n";
					if ($admin_options['email_form_submissions'] == 1) {
						if (!class_exists('PHPMailer'))
							require_once(ABSPATH . "wp-includes/class-phpmailer.php"); 
						$mail = new PHPMailer();
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
						$dest_email_array = $this->getDestinationEmailArray($_POST['destination_email']);
						if (empty($dest_email_array)) $mail->AddAddress($admin_options['default_to_email']);
						else {
							foreach ($dest_email_array as $em)
								$mail->AddAddress($em);
						}
						$mail->Subject = $admin_options['default_form_subject'];
						$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
						$mail->MsgHTML(stripslashes($body));
						$mail->Send();
					} if ($_POST['thank_you_page']) {
						ccf_utils::redirect($_POST['thank_you_page']);
					}
					$this->current_thank_you_message = (!empty($_POST['success_message'])) ? $_POST['success_message'] : $admin_options['form_success_message'];
					$this->current_form = 0;
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			} elseif ($_POST['customcontactforms_submit']) {
				ccf_utils::startSession();
				$this->error_return = $_POST['form_page'];
				$admin_options = parent::getAdminOptions();
				$fields = parent::getAttachedFieldsArray($_POST['fid']);
				$form = parent::selectForm($_POST['fid']);
				$checks = array();
				$reply = ($_POST['fixedEmail']) ? $_POST['fixedEmail'] : NULL;
				$fixed_subject = ($_POST['emailSubject']) ? $_POST['emailSubject'] : NULL;
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
					} elseif ($field->field_slug == 'fixedWebsite' && $field->field_required == 1 && !empty($_POST['fixedWebsite'])) {
						if (!$this->validWebsite($_POST['fixedWebsite'])) {
							if (empty($field->field_error))
								$this->setFormError('fixedWebsite', __('The website address you provided is not valid.', 'custom-contact-forms'));
							else $this->setFormError('fixedWebsite', $field->field_error);
						}
					} else {
						$field_error_label = (empty($field->field_label)) ? $field->field_slug : $field->field_label;
						if ($field->field_required == 1 && !empty($_POST[$field->field_slug])) {
							if ($field->field_type == 'Dropdown' || $field->field_type == 'Radio') {
								// TODO: find better way to check for a dead state
								if ($_POST[$field->field_slug] == CCF_DEAD_STATE_VALUE) {
									if (empty($field->field_error))
										$this->setFormError($field->field_slug, $lang['field_blank'] . '"'.$field_error_label.'"');
									else $this->setFormError($field->field_slug, $field->field_error);
								}
							}
						} elseif ($field->field_required == 1 && empty($_POST[$field->field_slug])) {
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
					if (!array_key_exists($key, $GLOBALS['ccf_fixed_fields']) or $key == 'fixedEmail' or $key == 'usaStates' or $key == 'allCountries') {
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
					ccf_utils::load_module('export/custom-contact-forms-user-data.php');
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
						$from_name = (empty($admin_options['default_from_name'])) ? 'Custom Contact Forms' : $admin_options['default_from_name'];
						if (!empty($form->form_email_name)) $from_name = $form->form_email_name;
						if (empty($dest_email_array)) $mail->AddAddress($admin_options['default_to_email']);
						else {
							foreach ($dest_email_array as $em)
								$mail->AddAddress($em);
						}
						if ($reply != NULL && $this->validEmail($reply)) {
							$mail->From = $reply;
							$mail->FromName = 'Custom Contact Forms';
						} else {
							$mail->From = $admin_options['default_from_email'];
							$mail->FromName = $from_name;
						}
						$mail->Subject = ($fixed_subject != NULL) ? $fixed_subject : $admin_options['default_form_subject'];
						if (!empty($form->form_email_subject)) $mail->Subject = $form->form_email_subject;
						$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
						$mail->CharSet = "utf-8";
						$mail->MsgHTML(stripslashes($body));
						$mail->Send();
					} if (!empty($form->form_thank_you_page)) {
						ccf_utils::redirect($form->form_thank_you_page);
					}
					$this->current_form = $form->id;
					add_action('wp_footer', array(&$this, 'insertFormSuccessCode'), 1);
				}
				unset($_POST);
			}
		}
		
		function getCaptchaCode($field_object, $form_id) {
			$admin_options = parent::getAdminOptions();
			$code_type = ($admin_options['code_type'] == 'XHTML') ? ' /' : '';
			if (empty($field_object->field_instructions)) {
				$instructions = '';
				$tooltip_class = '';
			} else {
				$instructions = 'title="'.$field_object->field_instructions.'"';
				$tooltip_class = 'ccf-tooltip-field';
			}
			$out = '<img width="96" height="24" alt="' . __('Captcha image for Custom Contact Forms plugin. You must type the numbers shown in the image', 'custom-contact-forms') . '" id="captcha-image" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/custom-contact-forms/image.php?fid='.$form_id.'"'.$code_type.'> 
			<div><label for="captcha'.$form_id.'">* '.$field_object->field_label.'</label> <input class="'.$field_object->field_class.' '.$tooltip_class.'" type="text" '.$instructions.' name="captcha" id="captcha'.$form_id.'" maxlength="20"'.$code_type.'></div>';
			return $out;
		}
		
		function userCanViewForm($form_object) {
			if (is_user_logged_in()) {
				global $current_user;
				$user_roles = $current_user->roles;
				$user_role = array_shift($user_roles);
			} else
				$user_role = 'Non-Registered User';
			$form_access_array = parent::getFormAccessArray($form_object->form_access);
			return parent::formHasRole($form_access_array, $user_role);
		}
		
		function getStatesCode($field_object, $form_id) {
			ccf_utils::load_module('extra_fields/states_field.php');
			$req = ($field_object->field_required == 1) ? '* ' : '';
			$states_field = new ccf_states_field($field_object->field_class, $form_id, $field_object->field_value, $field_object->field_instructions);
			return "\n".'<label class="select" for="'.ccf_utils::decodeOption($field_object->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field_object->field_label, 1, 1).'</label>'.$states_field->getCode();
		}
		
		function getCountriesCode($field_object, $form_id) {
			ccf_utils::load_module('extra_fields/countries_field.php');
			$req = ($field_object->field_required == 1) ? '* ' : '';
			$countries_field = new ccf_countries_field($field_object->field_class, $form_id, $field_object->field_value, $field_object->field_instructions);
			return '<label class="select" for="'.ccf_utils::decodeOption($field_object->field_slug, 1, 1).'">'. $req .ccf_utils::decodeOption($field_object->field_label, 1, 1).'</label>' . "\n" . $countries_field->getCode();
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