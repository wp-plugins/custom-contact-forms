<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsStatic')) {
	class CustomContactFormsStatic {
		function encodeOption($option) {
			return htmlspecialchars(stripslashes($option), ENT_QUOTES);
		}
		
		function encodeOptionArray($option_array) {
			foreach ($option_array as $option) {
				if (is_array($option))
					$option = CustomContactFormsStatic::encodeOptionArray($option);
				else
					$option = CustomContactFormsStatic::encodeOption($option);
			}
			return $option_array;
		}
		
		function decodeOption($option, $strip_slashes = 1, $decode_html_chars = 1) {
			if ($strip_slashes == 1) $option = stripslashes($option);
			if ($decode_html_chars == 1) $option = html_entity_decode($option);
			return $option;
		}
		
		function strstrb($h, $n){
			return array_shift(explode($n, $h, 2));
		}
	}
}
?>