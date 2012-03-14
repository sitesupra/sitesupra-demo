<?php

namespace Supra\Tests\Mailer\Mockup;

use Supra\Mailer\Mailer as SupraMailer;
use \Swift_Mime_Message;
use Supra\Mailer\Exception;

class Mailer extends SupraMailer
{
	public $receavedMessage;
	public $sentMessages;
	
	public function __construct()
	{
		
	}
	
	/**
	 * @param Swift_Mime_Message $message
	 * @param array &$failedRecipients, optional
	 * @return int
	 */
	public function send(Swift_Mime_Message $message, &$failedRecipients = null)
	{
		
		$subject = $message->getSubject();
		$body = $message->getBody();
		$childrenCount = count($message->getChildren());
		
		if (empty($subject)) {
			throw new Exception\RuntimeException('Empty message subject');
		}

		if ( empty($body) && empty($childrenCount) ) {
			throw new Exception\RuntimeException('Empty message body');
		}

		$this->receavedMessage[] = $message;
		
	}
	
	
	public function resetReceavedMessages(){
		$this->receavedMessage = array();
	}
	
}