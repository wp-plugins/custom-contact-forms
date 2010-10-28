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
		var $reply_to;
		var $wp_mail_function;
	
		 function CustomContactFormsMailer($to, $from, $subject, $body, $wp_mail_function, $reply_to = NULL){
			$this->to = $to;
			$this->from = $from;
			$this->subject = $subject;
			$this->body = $body;
			$this->reply_to = $reply_to;
			$this->wp_mail_function = $wp_mail_function;
		}
	
		function send(){
		  	$reply = ($this->reply_to != NULL) ? $this->reply_to : $this->from;
		  	$this->addHeader('From: '.$this->from."\r\n");
			$this->addHeader('Reply-To: '.$reply."\r\n");
			$this->addHeader('Return-Path: '.$this->from."\r\n");
			$this->addHeader('X-mailer: ZFmail 1.0'."\r\n");
			$this->body .= "\n\n\n----------\n" . __("Sent by Custom Contact Forms\nA WordPress Plugin created by Taylor Lovett", 'custom-contact-forms') . "\n".__('Report a Bug/Get Support:', 'custom-contact-forms')." http://www.taylorlovett.com\n";
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