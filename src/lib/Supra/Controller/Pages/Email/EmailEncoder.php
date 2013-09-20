<?php

namespace Supra\Controller\Pages\Email;

use Supra\Controller\Pages\PageController;

/**
 */
class EmailEncoder
{
	/**
	 * @var self
	 */
	private static $instance;
	
	/**
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			
			self::$instance = new self();

			$em = \Supra\ObjectRepository\ObjectRepository::getEventManager();
			
			$alreadySubscribed = $em->getListeners(PageController::EVENT_POST_PREPARE_CONTENT);
			
			foreach ($alreadySubscribed as $listener) {
				if ($listener instanceof EncoderEventListener) {
					$em->removeListener(PageController::EVENT_POST_PREPARE_CONTENT, $listener);
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
}