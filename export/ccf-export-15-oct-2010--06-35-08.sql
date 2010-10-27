INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('1', 'captcha', 'Type the numbers.', 'Text', '', '100', '0', 'Type the numbers displayed in the image above.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('2', 'ishuman', 'Check if you are human.', 'Checkbox', '1', '0', '0', 'This helps us prevent spam.', '', '0');

INSERT INTO `wp281_customcontactforms_fields` (id, field_slug, field_label, field_type, field_value, field_maxlength, user_field, field_instructions, field_options, field_required) VALUES ('3', 'fixedEmail', 'Your Email', 'Text', '', '100', '0', 'Please enter your email address.', '', '0');



### BEGIN WP Options Table Query

UPDATE `wp281_options` SET `option_value`='a:20:{s:16:"show_widget_home";s:1:"1";s:17:"show_widget_pages";s:1:"1";s:19:"show_widget_singles";s:1:"1";s:22:"show_widget_categories";s:1:"1";s:20:"show_widget_archives";s:1:"1";s:16:"default_to_email";s:22:"admin@taylorlovett.com";s:18:"default_from_email";s:22:"admin@taylorlovett.com";s:20:"default_form_subject";s:37:"Someone Filled Out Your Contact Form!";s:21:"remember_field_values";s:1:"0";s:11:"author_link";s:1:"1";s:22:"enable_widget_tooltips";s:1:"1";s:16:"wp_mail_function";s:1:"1";s:26:"form_success_message_title";s:13:"Form Success!";s:20:"form_success_message";s:69:"Thank you for filling out our web form. We will get back to you ASAP.";s:13:"enable_jquery";s:1:"1";s:9:"code_type";s:5:"XHTML";s:20:"show_install_popover";i:0;s:22:"email_form_submissions";s:1:"1";s:10:"admin_ajax";s:1:"1";s:16:"custom_thank_you";N;}' WHERE `option_name`='customContactFormsAdminOptions';

### END WP Options Table Query
