<?php

use Supra\Mailer\Exception;

namespace Supra\Mailer;

/**
 * Mailer
 *
 */
class Mailer extends \Swift_Mailer
{

	/**
	 * Create a new Mailer instance
	 *
	 * @param \Swift_Transport $transport
	 * @return self
	 */
	public static function newInstance(\Swift_Transport $transport)
	{
		$self = get_called_class();
		$instance = new $self($transport);
		return $instance;
	}

	/**
	 * Send the given Message like it would be sent in a mail client.
	 * 
	 * All recipients (with the exception of Bcc) will be able to see the other
	 * recipients this message was sent to.
	 * 
	 * Recipient/sender data will be retreived from the Message object.
	 * 
	 * The return value is the number of recipients who were accepted for
	 * delivery.
	 * 
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

		return parent::send($message, $failedRecipients);
	}

}
