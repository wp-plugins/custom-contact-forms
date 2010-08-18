<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsMailer')) {
	class CustomContactFormsMailer {
		var $to;
		var $from;
		var $subject;
		var $body;
		var $headers;
	
		 function CustomContactFormsMailer($to, $from, $subject, $body){
			$this->to = $to;
			$this->from = $from;
			$this->subject = $subject;
			$this->body = $body;
		}
	
		function send(){
		  $this->addHeader('From: '.$this->from."\r\n");
			$this->addHeader('Reply-To: '.$this->from."\r\n");
			$this->addHeader('Return-Path: '.$this->from."\r\n");
			$this->addHeader('X-mailer: ZFmail 1.0'."\r\n");
			wp_mail($this->to, $this->subject, $this->body, $this->headers);
		}
	
		function addHeader($header){
			$this->headers .= $header;
		}
	}
}
?>