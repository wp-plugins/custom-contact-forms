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
		var $wp_mail_function;
	
		 function CustomContactFormsMailer($to, $from, $subject, $body, $wp_mail_function){
			$this->to = $to;
			$this->from = $from;
			$this->subject = $subject;
			$this->body = $body;
			$this->wp_mail_function = $wp_mail_function;
		}
	
		function send(){
		  $this->addHeader('From: '.$this->from."\r\n");
			$this->addHeader('Reply-To: '.$this->from."\r\n");
			$this->addHeader('Return-Path: '.$this->from."\r\n");
			$this->addHeader('X-mailer: ZFmail 1.0'."\r\n");
			$this->body .= "\n\n\n----------\nSent by Custom Contact Forms\nA WordPress Plugin created by Taylor Lovett\nReport a Bug/Get Support: http://www.taylorlovett.com\n";
			if ($this->wp_mail_function == 1)
				wp_mail($this->to, $this->subject, $this->body, $this->headers);
			else
				mail($this->to, $this->subject, $this->body, $this->headers);
		}
	
		function addHeader($header){
			$this->headers .= $header;
		}
	}
}
?>