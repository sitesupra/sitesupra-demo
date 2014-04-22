<?php

namespace Supra\Controller\Pages\Email;

use Supra\Controller\Pages\PageController;
use Supra\Response\ResponseContext;

class EmailEncoder
{
	const DECODER_JAVASCRIPT_FILE_URI = '/cms/lib/public/decipher-email.min.js';
	
	/**
	 * @var EmailEncoder
	 */
	private static $instance;
	
	/**
	 * @return EmailEncoder
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			
			self::$instance = new self();

			$em = \Supra\ObjectRepository\ObjectRepository::getEventManager();
			
			$alreadySubscribed = $em->getListeners(PageController::EVENT_POST_PREPARE_CONTENT);
			
			foreach ($alreadySubscribed as $listener) {
				if ($listener[0] instanceof EncoderEventListener) {
					$em->removeListener(PageController::EVENT_POST_PREPARE_CONTENT, $listener[0]);
				}
			}
			
			$em->listen(PageController::EVENT_POST_PREPARE_CONTENT, new EncoderEventListener());
		}
		
		return self::$instance;
	}
	
	/**
	 * @param string $email
	 * @return string
	 */
	public function encode($email)
	{		
		return str_rot13($email);
	}
	
	/**
	 * @param ResponseContext $context
	 */
	public function bindResponseContext(ResponseContext $context)
	{
		$context->addJsUrlToLayoutSnippet('js', self::DECODER_JAVASCRIPT_FILE_URI);
	}
}