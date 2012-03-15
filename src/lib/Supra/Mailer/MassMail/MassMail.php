<?php

namespace Supra\Mailer\MassMail;

use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;

class MassMail
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	
	
	/**
	 * Sets current entity manager
	 * @param Doctrine\ORM\EntityManager $entityManager 
	 */
	public function setEntityManager(Doctrine\ORM\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	/**
	 * Return current or default entity manager
	 * @return Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		if (is_null($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}
		
		return $this->entityManager;
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
		if (is_null($this->subscriberListManager)) {
			$this->subscriberListManager = new Manager\SubscriberListManager($this->getEntityManager());
		}

		return $this->subscriberListManager;
	}

	/**
	 * Returns instance of CampaignManager
	 * @return Manager\CampaignManager
	 */
	public function getCampaignManager()
	{
		if (is_null($this->campaignManager)) {
			$this->campaignManager = new Manager\CampaignManager($this->getEntityManager());
		}

		return $this->campaignManager;
	}
	
	/**
	 * Returns instance of SendQueueManager
	 * @return Manager\SendQueueManager
	 */
	public function getSendQueueManager()
	{
		if (is_null($this->sendQueueManager)) {
			$this->sendQueueManager = new Manager\SendQueueManager($this->getEntityManager());
		}

		return $this->sendQueueManager;
	}

	/**
	 * Sets send queue manager
	 * @param Manager\SendQueueManager $queueManagerInstance 
	 */
	public function setSendQueuemanager(Manager\SendQueueManager $queueManagerInstance)
	{
		$this->sendQueueManager = $queueManagerInstance;
	}
	
	/**
	 * Returns instance of SubscriberManager
	 * @return Manager\SubscriberManager
	 */
	public function getSubscriberManager()
	{
		if (is_null($this->subscriberManager)) {
			$this->subscriberManager = new Manager\SubscriberManager($this->getEntityManager());
		}

		return $this->subscriberManager;
	}

	/**
	 * Flush entities changes
	 */
	public function flush()
	{
		$this->getEntityManager()->flush();
	}

	/**
	 * Populate send queueu by camaign
	 * @param Entity\Campaign $campaign 
	 */
	public function populateSendQueue(Entity\Campaign $campaign)
	{
		$subscribersList = $campaign->getSubscriberList();
		$subscribers = $this->getSubscriberListManager()
				->getActiveSubscribers($subscribersList);

		$massMailContentHtml = new MassMailContent(MassMailContent::TYPE_HTML_CONTENT,
						$campaign->getHtmlContent());
		$massMailContentText = new MassMailContent(MassMailContent::TYPE_TEXT_CONTENT,
						$campaign->getTextContent());
		$massMailContentSubject = new MassMailContent(MassMailContent::TYPE_SUBJECT,
						$campaign->getSubject());

		foreach ($subscribers as $subscriber) {

			$sendQueueItem = $this->getSendQueueManager()->createQueueItem();
			$sendQueueItem->setNameTo($subscriber->getName());
			$sendQueueItem->setEmailTo($subscriber->getEmailAddress());
			$sendQueueItem->setEmailFrom($campaign->getFromEmail());
			$sendQueueItem->setNameFrom($campaign->getFromName());
			$sendQueueItem->setReplyTo($campaign->getReplyTo());
			$sendQueueItem->setCampaign($campaign);

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
