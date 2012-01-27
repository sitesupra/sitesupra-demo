<?php

namespace Supra\Mailer\MassMail;

use Supra\ObjectRepository\ObjectRepository;

class MassMail
{
	private $entityManager;
	
	public function __construct(){
		$this->entityManager = ObjectRepository::getEntityManager($this);		
	}
	
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
			$this->subscriberListManager = new Manager\SubscriberListManager($this->entityManager);
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
			$this->campaignManager = new Manager\CampaignManager($this->entityManager);
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
			$this->sendQueueManager = new Manager\SendQueueManager($this->entityManager);
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
			$this->subscriberManager = new Manager\SubscriberManager($this->entityManager);
		}

		return $this->subscriberManager;	
	}

	/**
	 * Flush entities changes
	 */
	public function flush(){
	
		$this->entityManager->flush();
		
	}
	
	/**
	 * Populate send queueu by camaign
	 * @param Entity\Campaign $campaign 
	 */
	public function populateSendQueue(Entity\Campaign $campaign)
	{
		
		$this->getCampaignManager();		
		$subscribers = $campaign->getActiveSubscribers();
		
		$massMailContentHtml = new MassMaillContent(MassMaillContent::TYPE_HTML_CONTENT, 
									$campaign->getHtmlContent());
		$massMailContentText = new MassMaillContent(MassMaillContent::TYPE_TEXT_CONTENT, 
									$campaign->getTextContent());
		$massMailContentSubject = new MassMaillContent(MassMaillContent::TYPE_SUBJECT, 
									$campaign->getSubject());
		
		
		foreach($subscribers as $subscriber){
			
			$sendQueueItem = $this->getSendQueueManager()->createQueueItem();
			$sendQueueItem->setNameTo($subscriber->getName());
			$sendQueueItem->setEmailTo($subscriber->getEmailAddress());
			$sendQueueItem->setEmailFrom($campaign->getFromEmail());
			$sendQueueItem->setNameFrom($campaign->getFromName());
			$sendQueueItem->setReplyTo($campaign->getReplyTo());
						
			$subject = $massMailContentSubject->getPreparedContent($subscriber);
			$htmlContent = $massMailContentHtml->getPreparedContent($subscriber);
			$textContent = $massMailContentText->getPreparedContent($subscriber);

			$sendQueueItem->setSubject($subject);
			$sendQueueItem->setTextContent($textContent);
			$sendQueueItem->setHtmlContent($htmlContent);
			$sendQueueItem->setStatus(Entity\SendQueueItem::STATUS_PREPARED);
			$sendQueueItem->setCreateDateTime();
			
		}
		
	}
}
