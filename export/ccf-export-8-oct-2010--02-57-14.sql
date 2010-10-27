INSERT INTO `wp281_customcontactforms_forms` (id, form_slug, form_title, form_action, form_method, form_fields, submit_button_text, custom_code, form_style, form_email, form_success_message, form_thank_you_page, form_success_title) VALUES ('1', 'ccf_contact_form', 'Contact Form', '', 'Post', '4,6,3,7,8,9,5,', 'Send Message1', '', '3', 'admin@taylorlovett.com', 'Thank you for filling out our contact form. We will contact you very soon by the way you specified.', '', 'Thank You!!!');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('1', 'captcha', 'Type the numbers.', 'Text', '', '100', '0', 'Type the numbers displayed in the image above.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('2', 'ishuman', 'Check if you are human.', 'Checkbox', '1', '0', '0', 'This helps us prevent spam.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('3', 'fixedEmail', 'Your Email', 'Text', '', '100', '0', 'Please enter your email address.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('4', 'ccf_name', 'Your Name:', 'Text', '', '100', '1', 'Please enter your full name.', '', '1');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('5', 'ccf_message', 'Your Message:', 'Textarea', '', '0', '1', 'Enter any message or comment.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('6', 'ccf_website', 'Your Website:', 'Text', '', '200', '1', 'If you have a website, please enter it here.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('7', 'ccf_phone', 'Your Phone Number:', 'Text', '', '30', '1', 'Please enter your phone number.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('8', 'ccf_google', 'Did you find my website through Google?', 'Checkbox', 'Yes', '0', '1', 'If you found my website through Google, check this box.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('9', 'ccf_contact_method', 'How should we contact you?', 'Dropdown', '', '0', '1', 'By which method we should contact you?', '1,2,3,', '1');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('10', 'test_field', '1', 'Hidden', '2', '66', '1', 'ons! &quot\\; &#039\\; %', '', '1');

INSERT INTO `wp281_customcontactforms_styles` (id, style_slug, input_width, textarea_width, textarea_height, form_borderwidth, label_width, form_width, submit_width, submit_height, label_fontsize, title_fontsize, field_fontsize, submit_fontsize, field_bordercolor, form_borderstyle, form_bordercolor, field_fontcolor, label_fontcolor, title_fontcolor, submit_fontcolor, form_fontfamily, field_backgroundcolor, field_borderstyle, form_padding, form_margin, title_margin, label_margin, textarea_backgroundcolor, success_popover_bordercolor, dropdown_width, success_popover_fontsize, success_popover_title_fontsize, success_popover_height, success_popover_fontcolor, success_popover_title_fontcolor, form_backgroundcolor, field_borderround) VALUES ('3', 'test2', '200px', '200px', '100px', '1px', '110px', '500px', '80px', '35px', '.8em', '1.2em', '1em', '1em', 'ba0707', 'none', '333333', '333333', 'c71cc7', '333333', '333333', 'Verdana, tahoma, arial', 'efefef', 'solid', '1em', '5px', '2px', '4px', 'ffffff', 'efefef', 'auto', '1em', '12px', '200px', '333333', '333333', 'fcf7dd', '4px');

INSERT INTO `wp281_customcontactforms_user_data` (id, data_time, data_formid, data_formpage, data_value) VALUES ('1', '1286474894', '1', 'designandbuildmaryland.com/contact-us/', 's:8:"ccf_name"\\;s:13:"Taylor Lovett"\\;s:11:"ccf_website"\\;s:8:"test.com"\\;s:10:"fixedEmail"\\;s:13:"test@test.com"\\;s:9:"ccf_phone"\\;s:3:"301"\\;s:10:"ccf_google"\\;s:3:"Yes"\\;s:18:"ccf_contact_method"\\;s:17:"Do Not Contact Me"\\;s:11:"ccf_message"\\;s:21:"this is my message !!"\\;');

INSERT INTO `wp281_customcontactforms_user_data` (id, data_time, data_formid, data_formpage, data_value) VALUES ('3', '1286476845', '0', 'designandbuildmaryland.com/contact-us/', 's:11:"field_name1"\\;s:40:"&#039\\; &quot\\; &lt\\;table&gt\\;&lt\\;/body&gt\\;"\\;s:11:"field_name2"\\;s:3:"two"\\;');

INSERT INTO `wp281_customcontactforms_user_data` (id, data_time, data_formid, data_formpage, data_value) VALUES ('4', '1286477480', '0', 'designandbuildmaryland.com/contact-us/', 's:11:"field_name1"\\;s:34:"dsfdsf &quot\\; &#039\\; &lt\\;table&gt\\;"\\;s:11:"field_name2"\\;s:3:"two"\\;');

INSERT INTO `wp281_customcontactforms_user_data` (id, data_time, data_formid, data_formpage, data_value) VALUES ('5', '1286478663', '0', 'designandbuildmaryland.com/contact-us/', 's:11:"field_name1"\\;s:34:"dsfdsf &quot\\; &#039\\; &lt\\;table&gt\\;"\\;s:11:"field_name2"\\;s:3:"two"\\;');

INSERT INTO `wp281_customcontactforms_user_data` (id, data_time, data_formid, data_formpage, data_value) VALUES ('6', '1286484942', '1', 'localhost/wp2-8-1/', 's:8:"ccf_name"\\;s:4:"name"\\;s:11:"ccf_website"\\;s:7:"website"\\;s:10:"fixedEmail"\\;s:15:"email@email.com"\\;s:9:"ccf_phone"\\;s:4:"2342"\\;s:10:"ccf_google"\\;s:3:"Yes"\\;s:18:"ccf_contact_method"\\;s:8:"By Phone"\\;s:11:"ccf_message"\\;s:63:"this is my message &quot\\; &#039\\;&#039\\; &lt\\; &gt\\; FROM LOCLAHOST"\\;');

INSERT INTO `wp281_customcontactforms_field_options` (id, option_slug, option_label, option_value) VALUES ('1', 'ccf_email', 'By Email', '');

INSERT INTO `wp281_customcontactforms_field_options` (id, option_slug, option_label, option_value) VALUES ('2', 'ccf_phone', 'By Phone', '');

INSERT INTO `wp281_customcontactforms_field_options` (id, option_slug, option_label, option_value) VALUES ('3', 'ccf_no_contact', 'Do Not Contact Me', '');

INSERT INTO `wp281_customcontactforms_field_options` (id, option_slug, option_label, option_value) VALUES ('5', 'LOCALHOST', 'LOCALHOST', 'loc');

