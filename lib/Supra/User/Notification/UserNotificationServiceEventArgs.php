<?php

namespace Supra\User\Notification;

use Supra\Event\EventArgs;
use Supra\User\Entity\User;
use Supra\User\Entity\UserNotification;

class UserNotificationServiceEventArgs extends EventArgs
{

	/**
	 * @var UserNotification
	 */
	protected $notification;

	/**
	 * @return UserNotification
	 */
	public function getNotification()
	{
		return $this->notification;
	}

	/**
	 * @param UserNotification $notification 
	 */
	public function setNotification(UserNotification $notification)
	{
		$this->notification = $notification;
	}

}
