<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
header("Content-type: image/png");
require_once('custom-contact-forms-images.php');
$image = new CustomContactFormsImages();
$str = rand(10000, 99999);
if (!session_id())
	session_start();
$_SESSION[captcha] = $str;
$image->createImageWithText($str);
?>