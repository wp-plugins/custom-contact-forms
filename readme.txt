=== Custom Contact Forms ===
Contributors: Taylor Lovett
Donate link: http://www.taylorlovett.com
Tags: contact form, web form, custom contact form, custom forms, captcha form, contact fields, form mailers
Requires at least: 2.8.1
Tested up to: 3.0.1
Stable tag: 4.0.7

Gauranteed to be the most customizable and intuitive contact form plugin for Wordpress.

== Description ==

Guaranteed to be 1000X more customizable and intuitive than Fast Secure Contact Forms or Contact Form 7. Customize every aspect of your forms without any knowledge of CSS: borders, padding, sizes, colors. Ton's of great features. Required fields, captchas, tooltip popovers, unlimited fields/forms/form styles, use a custom thank you page or built-in popover with a custom success message set for each form.

Special Features: 
------------------
Custom Contact Forms 4.0 will revolutionize the idea of a Wordpress plugin.

*	__NEW__ - All form submissions saved and displayed in admin panel as well as emailed to you
*	__NEW__ - Import and export forms/fields/styles/etc. with ease!
*	__NEW__ - This plugin can now be translated in to different languages - UTF8 character encoding.
*	__NEW__ - Error messages can be customized for each field
*	Choose between XHTML or HTML. All code is clean and valid!
*	Create __unlimited__ forms and fields
*	Required Fields
*	Custom Contact Forms now uses PHPMailer and thus supports STMP and SSL
*	__NEW__ Have your contact forms send mail to multiple email addresses
*	Create text fields, textareas, checkboxs, and dropdown fields!
*	Custom HTML Forms Feature - if you are a web developer you can write your own form html and __use this plugin simply to process your form requests__. Comes with a few useful features.
*	__Displays forms in theme files__ as well as pages and posts.
*	Set a different destination email address for each form
*	Customize every aspect of fields and forms: titles, labels, maxlength, initial value, form action, form method, form style, and much more
*	Create checkboxes, textareas, text fields, etc.
*	__Captcha__ and __"Are You Human?"__ spam blockers included and easily attached to any form
*	Create __custom styles in the style manager__ to change the appearance of your forms: borders, font sizes, colors, padding, margins, background, and more
*	You can create unlimited styles to use on as many forms as you want without any knowledge of css or html.
*	Show a stylish JQuery form thank you message or use a custom thank you page.
*	Custom error pages for when forms are filled out incorrectly
*	Option to have forms remember field values for when users hit the back button after an error
*	Easily report bugs and suggest new features
*	Script in constant development - new version released every week
*	Easily process your forms with 3rd party sites like Infusionsoft or Aweber
*	Set a __custom thank you page__ for each form or use the built in thank you page popover with a custom thank you message
*	No javascript required
*	Detailed guide for using the plugin as well as default content to help you understand how to use Custom COntact Forms
*	Stylish field tooltips powered by jquery
*	Manage options for your dropdowns and radio fields in an easy to use manager
*	Popover forms with Jquery (Coming soon!)
*	Free unlimited support
*	AJAX enabled admin panel
*	Assign different CSS classes to each field.
*	Ability to disable JQuery if it is conflicting with other plugins.

Restrictions/Requirements:
-------------------------
*	Works with Wordpress 2.8.1+, WPMU, and BuddyPress (Wordpress 3.0+ is highly recommended)
*	PHP 5
*	PHP register_globals and safe_mode should be set to "Off" (this is done in your php.ini file)

== Installation ==
1. Upload to /wp-content/plugins
2. Activate the plugin from your Wordpress Admin Panel
3. Configure the plugin, create fields, and create forms in the Settings page called Custom Contact Forms
4. Display those forms in posts and pages by inserting the code: __[customcontact form=FORMID]__
5. In the instruction section of the plugin. Press the button to insert the default content. The default content contains a very generic form that will help you understand the many ways you can use Custom Contact Forms.

== Configuring and Using the Plugin ==
1. Create as many forms as you want.
2. Create fields and attach those fields to the forms of your choice. Attach the fields in the order that you want them to show up in the form. If you mess up you can detach and reattach them.
3. Display those forms in posts and pages by inserting the code: __[customcontact form=FORMID]__. Replace __FORMID__ with the id listed to the left of the form slug next to the form of your choice above. You can also __display forms in theme files__; the code for this is provided within each forms admin section.
4. Prevent spam by attaching the fixed field, captcha or ishuman. Captcha requires users to type in a number shown on an image. Ishuman requires users to check a box to prove they aren't a spam bot.
5. Add a form to your sidebar, by dragging the Custom Contact Form reusable widget in to your sidebar.
6. Configure the General Settings appropriately; this is important if you want to receive your web form messages!
7. Create form styles to change your forms appearances. The image below explains how each style field can change the look of your forms.
8. (advanced) If you are confident in your HTML and CSS skills, you can use the Custom HTML Forms feature as a framework and write your forms from scratch. This allows you to use this plugin simply to process your form requests. The Custom HTML Forms feature will process and email any form variables sent to it regardless of whether they are created in the fields manager.

Custom Contact Forms is an extremely intuitive plugin allowing you to create any type of contact form you can image. CCF is very user friendly but with possibilities comes complexity. __It is recommend that you click the button in the instructions section of the plugin to add default fields, field options, and forms.__ The default content will help you get a feel for the amazing things you can accomplish with this plugin. __It is also recommended you click the "Show Plugin Usage Popover"__ in the instruction area of the admin page to read in detail about all parts of the plugin.

== Support ==
For questions, feature requests, and support concerning the Custom Contact Forms plugin, please email me at:
admin@taylorlovett.com

I respond to emails same-day!

== Frequently Asked Questions ==

= I'm not receiving any emails =
*	Check that the "Email Form Submissions" option is set to yes in General Settings.
*	Try filling out a form with the "Use Wordpress Mail Function" option set to "No".
*	Make sure the "Default From" email you are using within General Settings actually exists on your server.
*	Try deactivating other plugins to make sure there are no conflicts

= When I activate Custom Contact Forms, the Javascript for another plugin does not work. =
*	Disable the "Frontend JQuery" option in General Settings. Custom Contact Forms will still work without JQuery but won't be as pretty.

== Upgrade Notice ==
We are planning to add popover forms and file attachments soon.

== Screenshots ==
Visit http://www.taylorlovett.com/wordpress-plugins for screenshots. Right now all the screenshots are from Version 1, thus are quite out-dated. Install the plugin to see what it looks like. You won't regret it. I promise!

== Changelog ==

= 4.0.7 =
*	custom-contact-forms-admin.php - Admin panel updated

= 4.0.6 =
*	modules/widgets/custom-contact-forms-widget.php - Form title added via widget

= 4.0.5 =
*	modules/db/custom-contact-forms-db.php - Form email cutoff bug fixed

= 4.0.4 =
*	custom-contact-forms-admin.php - Bug reporting mail error fixed

= 4.0.3 =
*	custom-contact-forms-front.php - PHPMailer bug fixed, form redirect fixed
*	custom-contact-forms-static.php - Form redirect function added
*	custom-contact-forms-admin.php - redirects fixed, phpmailer bug fixed
*	widget/phpmailer - deleted
*	widget/db/custom-contact-forms-db.php - table charsets changed to UTF8

= 4.0.2 =
*	custom-contact-forms-front.php - Field instructions bug fixed
*	custom-contact-forms-admin.php - Display change

= 4.0.1 =
*	custom-contact-forms.php
*	custom-contact-forms-admin.php - support for multiple form destination emails added
*	custom-contact-forms-front.php - Mail bug fixed, email validation bug fixed
*	lang/custom-contact-forms.php - Phrases deleted/added

= 4.0.0 =
*	custom-contact-forms.php - Saved form submissions manager, form background color added to style manager, import/export feature
*	custom-contact-forms-user-data.php - Saved form submission
*	custom-contact-forms-db.php - DB methods reorganized for efficiency
*	custom-contact-forms-static.php - Methods added/removed for efficiency
*	custom-contact-forms-admin.php - Admin code seperated in to a different file
*	custom-contact-forms-popover.php - Popover code seperated in to a different file
*	custom-contact-forms-export.php - Functions for importing and exporting
*	css/custom-contact-forms-admin.css - AJAX abilities added
*	css/custom-contact-forms-standard.css - Classes renamed
*	js/custom-contact-forms-admin.js - AJAX abilities added to admin panel
*	download.php - Allows export file to be downloaded
*	lang/custom-contact-forms.po - Allows for translation to different languages
*	lang/custom-contact-forms.mo - Allows for translation to different languages

= 3.5.5 =
*	custom-contact-forms.php - Plugin usage popover reworded
*	css/custom-contact-forms-admin.css - Admin panel display problem fixed

= 3.5.4 =
*	custom-contact-forms.php - custom thank you redirect fix
*	custom-contact-forms-db.php - Style insert bug fixed, Unexpected header output bug fixed

= 3.5.3 =
*	custom-contact-forms.php - Style popover height option added to style manager. Form title heading not shown if left blank.
*	custom-contact-forms-db.php - New success popover height column added to styles table

= 3.5.2 =
*	custom-contact-forms.php - Plugin Usage popover added, insert default content button
*	custom-contact-forms-db.php - Insert default content function

= 3.5.1 =
*	custom-contact-forms.php - Style options added, color picker added, success popover styling bugs fixed
*	custom-contact-forms-db.php - Style format changed, new style fields added to tables
*	Lots of javascript files
*	Lots of images for the colorpicker

= 3.5.0 =
*	custom-contact-forms.php - Radio and dropdowns added via the field option manager
*	custom-contact-forms-mailer.php - Email body changed
*	custom-contact-forms-db.php - Field option methods added
*	custom-contact-forms.css - Form styles reorganized, file removed
*	css/custom-contact-forms.css - Form styles reorganized
*	css/custom-contact-forms-standards.css - Form styles reorganized
*	css/custom-contact-forms-admin.css - Form styles reorganized

= 3.1.0 =
*	custom-contact-forms.php - Success message title, disable jquery, choose between xhmtl and html, and more
*	custom-contact-forms-db.php - Success message title added
*	custom-contact-forms.css - Form styles rewritten

= 3.0.2 =
*	custom-contact-forms.php - Bugs fixed

= 3.0.1 =
*	custom-contact-forms.php - Php tags added to theme form display code

= 3.0.0 =
*	custom-contact-forms.php - Required fields, admin panel changed, style manager bugs fixed, custom html feature added, much more
*	custom-contact-forms-db.php - New functions added and old ones fixed
*	custom-contact-forms.css - New styles added and old ones modified

= 2.2.5 =
*	custom-contact-forms.php - Fixed field insert bug fixed

= 2.2.4 =
*	custom-contact-forms.php - Textarea field instruction bug fixed

= 2.2.3 =
*	custom-contact-forms.php - Remember fields bug fixed, init rearranged, field instructions
*	custom-contact-forms.css
*	custom-contact-forms-db.php

= 2.2.0 =
*	custom-contact-forms.php - Plugin nav, hide plugin author link, bug reporting, suggest a feature
*	custom-contact-forms.css - New styles added and style bugs fixed

= 2.1.0 =
*	custom-contact-forms.php - New fixed field added, plugin news, bug fixes
*	custom-contact-forms.css - New styles added and style bugs fixed
*	custom-contact-forms-db.php - New fixed field added

= 2.0.3 =
*	custom-contact-forms.php - custom style checkbox display:block error fixed
*	custom-contact-forms.css - li's converted to p's

= 2.0.2 =
*	custom-contact-forms.php - Form li's changed to p's
*	images/ - folder readded to correct captcha error

= 2.0.1 =
*	custom-contact-forms.php - Duplicate form slug bug fixed, default style values added, stripslahses on form messages
*	custom-contact-forms-db.php - default style values added

= 2.0.0 =
*	custom-contact-forms.php - Style manager added
*	custom-contact-forms.css - style manager styles added
*	custom-contact-forms-db.php - Style manager db functions added

= 1.2.1 =
*	custom-contact-forms.php - Upgrade options changed
*	custom-contact-forms-css.php - CSS bug corrected

= 1.2.0 =
*	custom-contact-forms.php - Option to update to Custom Contact Forms Pro

= 1.1.3 =
*	custom-contact-forms.php - Captcha label bug fixed
*	custom-contact-forms-db.php - Default captcha label changed

= 1.1.2 =
*	custom-contact-forms-db.php - create_tables function edited to work for Wordpress MU due to error in wp-admin/includes/upgrade.php

= 1.1.1 =
*	custom-contact-forms.css - Label styles changed
*	custom-contact-forms.php - Admin option added to remember field values

= 1.1.0 =
*	custom-contact-forms-db.php - Table upgrade functions added
*	custom-contact-forms.php - New functions for error handling and captcha
*	custom-contact-forms.css - Forms restyled
*	custom-contact-forms-images.php - Image handling class added
*	image.php, images/ - Image for captcha displaying

= 1.0.1 =
*	custom-contact-forms.css - Form style changes

= 1.0.0 =
*	Plugin Release