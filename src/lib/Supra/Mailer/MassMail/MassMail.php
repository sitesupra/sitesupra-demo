<?php
namespace Supra\Mailer\MassMail;

class MassMail
{
	/**
	 * Campaign manager instance
	 * @var Manager\CampaignManager
	 */
	private $campaignManager = null;
	
	/**
	 * Subscriber manager instance
	 * @var Manager\SubscriberManager
	 */
	private $subscriberManager = null;
	
	/**
	 * Subscriber manager instance
	 * @var Manager\SubscriberListManager
	 */
	private $subscriberListManager = null;
	
	/**
	 * SendQueue manager instance 
	 * @var Manager\SendQueueManager 
	 */
	private $sendQueueManager = null;
	
	/**
	 * Returns instance of SubscriberListManager
	 * @return Manager\SubscriberListManager
	 */
	public function getSubscriberListManager()
	{
		if($this->subscriberListManager == null) {
			$this->subscriberListManager = new Manager\SubscriberListManager();
		}
		
		return $this->subscriberListManager;
	}
	
	/**
	 * Returns instance of CampaignManager
	 * @return Manager\CampaignManager
	 */
	public function getCampaignManager()
	{
		if($this->campaignManager == null) {
			$this->campaignManager = new Manager\CampaignManager();
		}
		
		return $this->campaignManager;
	}
	
	/**
	 * Returns instance of SendQueueManager
	 * @return Manager\SendQueueManager
	 */
	public function getSendQueueManager()
	{
		if($this->sendQueueManager == null) {
			$this->sendQueueManager = new Manager\SendQueueManager;
		}
		
		return $this->sendQueueManager;
	}
	
	/**
	 * Returns instance of SubscriberManager
	 * @return Manager\SubscriberManager
	 */
	public function getSubscriberManager()
	{
		
		if($this->subscriberManager == null) {
			$this->subscriberManager = new Manager\SubscriberManager();
		}

		return $this->subscriberManager;	
	}
	
}
