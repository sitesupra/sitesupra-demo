<?php

namespace Supra\Controller\Pages\Email;

use Supra\Controller\Pages\Event\BlockEvents;

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

			$alreadySubscribed = $em->getListeners(BlockEvents::blockEndExecuteEvent);

			foreach ($alreadySubscribed as $listener) {
				if ($listener[0] instanceof EncoderEventListener) {
					$em->removeListener(BlockEvents::blockEndExecuteEvent, $listener[0]);
				}
			}

			$em->listen(BlockEvents::blockEndExecuteEvent, new EncoderEventListener());
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