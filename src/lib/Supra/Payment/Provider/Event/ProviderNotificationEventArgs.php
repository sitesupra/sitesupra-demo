<?php

namespace Supra\Payment\Provider\Event;

class ProviderNotificationEventArgs extends PaymentEventArgsAbstraction
{
	/**
	 * @var array
	 */
	protected $notificationData;

	/**
	 * @param array $notificationData 
	 */
	public function setNotificationData($notificationData)
	{
		$this->notificationData = $notificationData;
	}

	/**
	 * @return array
	 */
	public function getNotificationData()
	{
		return $this->getNotificationData();
	}

}
