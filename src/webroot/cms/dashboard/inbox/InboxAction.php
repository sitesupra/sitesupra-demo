<?php

namespace Supra\Cms\Dashboard\Inbox;

use Supra\Cms\Dashboard\DasboardAbstractAction;
use Supra\User\Notification\UserNotificationService;

class InboxAction extends DasboardAbstractAction
{
	
	public function inboxAction()
	{
		
		$notificationService = new UserNotificationService();
		$userNotifications = $notificationService->getUserNotifications($this->currentUser, null);
		
		$response = array();
		
		foreach($userNotifications as $notification) {
			/* @var $notification \Supra\User\Entity\UserNotification */
			$response[] = array(
				'title' => $notification->getMessage(),
				'buy' => false,
				'new' => ( ! $notification->getIsRead()),
			);
		}
				
		$this->getResponse()
				->setResponseData($response);	
	}
	
}