=== Custom Contact Forms ===
Contributors: Taylor Lovett
Donate link: http://www.taylorlovett.com
Tags: contact form, web form, custom contact form, custom forms, captcha form, contact fields, form mailers
Requires at least: 2.7.1
Tested up to: 3.1
Stable tag: 3.1.2

Gauranteed to be the most customizable and intuitive contact form plugin for Wordpress.

== Description ==
Guaranteed to be 1000X more customizable and intuitive than Fast Secure Contact Forms or Contact Form 7. Customize every aspect of your forms without any knowledge of CSS: borders, padding, sizes, colors. Ton's of great features. Required fields, captchas, tooltip popovers, unlimited fields/forms/form styles, use a custom thank you page or built-in popover with a custom success message set for each form.

Special Features:
------------------
*	The most customizable form plugin for Wordpress, guaranteed
*	Create __unlimited__ forms
*	Create __unlimited__ fields
*	Required Fields (New!)
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
*	Choose between XHTML or HTML. All code is clean and valid!
*	No javascript required
*	Stylish field tooltips powered by jquery
*	Popover forms with Jquery (Coming soon!)
*	Free unlimited support
*	Ability to disable JQuery if it is conflicting with other plugins.

== Installation ==
1. Upload to /wp-content/plugins
2. Activate the plugin from your Wordpress Admin Panel
3. Configure the plugin, create fields, and create forms in the Settings page called Custom Contact Forms
4. Display those forms in posts and pages by inserting the code: __[customcontact form=FORMID]__

== Configuring and Using the Plugin ==
1. Create as many forms as you want.
2. Create fields and attach those fields to the forms of your choice. Attach the fields in the order that you want them to show up in the form. If you mess up you can detach and reattach them.
3. Display those forms in posts and pages by inserting the code: __[customcontact form=FORMID]__. Replace __FORMID__ with the id listed to the left of the form slug next to the form of your choice above. You can also __display forms in theme files__; the code for this is provided within each forms admin section.
4. Prevent spam by attaching the fixed field, captcha or ishuman. Captcha requires users to type in a number shown on an image. Ishuman requires users to check a box to prove they aren't a spam bot.
5. Add a form to your sidebar, by dragging the Custom Contact Form reusable widget in to your sidebar.
6. Configure the General Settings appropriately; this is important if you want to receive your web form messages!
7. Create form styles to change your forms appearances. The image below explains how each style field can change the look of your forms.
8. (advanced) If you are confident in your HTML and CSS skills, you can use the Custom HTML Forms feature as a framework and write your forms from scratch. This allows you to use this plugin simply to process your form requests. The Custom HTML Forms feature will process and email any form variables sent to it regardless of whether they are created in the fields manager.

== Support ==
For questions, feature requests, and support concerning the Custom Contact Forms plugin, please email me at:
admin@taylorlovett.com
I respond to emails same-day!

== Upgrade Notice ==
Popover forms will be added in September 2010.

== Screenshots ==
Visit http://www.taylorlovett.com/wordpress-plugins for screenshots.

== Changelog ==
= 1.0.0 =
*	Plugin Release
= 1.0.1 =
*	custom-contact-forms.css - Form style changes
= 1.1.0 =
*	custom-contact-forms-db.php - Table upgrade functions added
*	custom-contact-forms.php - New functions for error handling and captcha
*	custom-contact-forms.css - Forms restyled
*	custom-contact-forms-images.php - Image handling class added
*	image.php, images/ - Image for captcha displaying
= 1.1.1 =
*	custom-contact-forms.css - Label styles changed
*	custom-contact-forms.php - Admin option added to remember field values
= 1.1.2 =
*	custom-contact-forms-db.php - create_tables function edited to work for Wordpress MU due to error in wp-admin/includes/upgrade.php
= 1.1.3 =
*	custom-contact-forms.php - Captcha label bug fixed
*	custom-contact-forms-db.php - Default captcha label changed
= 1.2.0 =
*	custom-contact-forms.php - Option to update to Custom Contact Forms Pro
= 1.2.1 =
*	custom-contact-forms.php - Upgrade options changed
*	custom-contact-forms-css.php - CSS bug corrected
= 2.0.0 =
*	custom-contact-forms.php - Style manager added
*	custom-contact-forms.css - style manager styles added
*	custom-contact-forms-db.php - Style manager db functions added
= 2.0.1 =
*	custom-contact-forms.php - Duplicate form slug bug fixed, default style values added, stripslahses on form messages
*	custom-contact-forms-db.php - default style values added
= 2.0.2 =
*	custom-contact-forms.php - Form li's changed to p's
*	images/ - folder readded to correct captcha error
= 2.0.3 =
*	custom-contact-forms.php - custom style checkbox display:block error fixed
*	custom-contact-forms.css - li's converted to p's
= 2.1.0 =
*	custom-contact-forms.php - New fixed field added, plugin news, bug fixes
*	custom-contact-forms.css - New styles added and style bugs fixed
*	custom-contact-forms-db.php - New fixed field added
= 2.2.0 =
*	custom-contact-forms.php - Plugin nav, hide plugin author link, bug reporting, suggest a feature
*	custom-contact-forms.css - New styles added and style bugs fixed
= 2.2.3 =
*	custom-contact-forms.php - Remember fields bug fixed, init rearranged, field instructions
*	custom-contact-forms.css
*	custom-contact-forms-db.php
= 2.2.4 =
*	custom-contact-forms.php - Textarea field instruction bug fixed
= 2.2.5 =
*	custom-contact-forms.php - Fixed field insert bug fixed
= 3.0.0 =
*	custom-contact-forms.php - Required fields, admin panel changed, style manager bugs fixed, custom html feature added, much more
*	custom-contact-forms-db.php - New functions added and old ones fixed
*	custom-contact-forms.css - New styles added and old ones modified
= 3.0.1 =
*	custom-contact-forms.php - Php tags added to theme form display code
= 3.0.2 =
*	custom-contact-forms.php - Bugs fixed
= 3.1.0 =
*	custom-contact-forms.php - Success message title, disable jquery, choose between xhmtl and html, and more
*	custom-contact-forms-db.php - Success message title added
*	custom-contact-forms.css - Form styles rewritten
= 3.1.1 =
*	custom-contact-forms-db.php - Style manager bug fixed
= 3.1.2 =
*	custom-contact-forms-db.php - Fixed email field bug fixed