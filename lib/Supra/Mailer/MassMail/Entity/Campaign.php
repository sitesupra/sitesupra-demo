<?php

namespace Supra\Mailer\MassMail\Entity;

use Supra\Database\Entity;

/**
 * @Entity
 */
class Campaign extends Entity
{

	const STATUS_NEW = 10;
	const STATUS_READY_TO_SEND = 20;
	const STATUS_INACTIVE = 30;
	
	/**
	 * @ManyToOne(targetEntity="SubscriberList")
	 */
	protected $subscriberList;
	
	public function setSubscriberList(SubscriberList $subscriberList)
	{
		$this->subscriberList = $subscriberList;
	}

	
	/**
	 * @OneToMany(targetEntity="SendQueueItem", mappedBy="campaign", cascade={"persist", "remove"} )
	 * @var \Doctrine\Common\Collections\Collection
	 */	
	protected $sendQueueItems;
	
	
	/**
	 * @Column(type="string", name="subject", nullable=false)
	 * @var string
	 */
	protected $subject;

	/**
	 * @Column(type="string", name="name", nullable=false)
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string", name="from_name", nullable=false)
	 * @var string
	 */
	protected $fromName;

	/**
	 * @Column(type="string", name="from_email", nullable=false)
	 * @var string
	 */
	protected $fromEmail;

	/**
	 * @Column(type="string", name="reply_to", nullable=false)
	 * @var string
	 */
	protected $replyTo;

	/**
	 * @Column(type="string", name="html_content", nullable=true)
	 * @var string
	 */
	protected $htmlContent;

	/**
	 * @Column(type="string", name="text_content", nullable=true)
	 * @var string
	 */
	protected $textContent;

	/**
	 * @Column(type="integer", name="status", nullable=true)
	 * @var string
	 */
	protected $status;

	public function __construct()
	{
		parent::__construct();
		
		$this->sendQueueItems = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
	public function addSendQueueItem(SendQueueItem $sendQueueItem)
	{
		$this->sendQueueItems[] = $sendQueueItem;
	}
	
	/**
	 * Set campaign subject
	 * @param string $subject 
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	/**
	 * Set campaign name
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Set from name for campaign
	 * @param string $fromName 
	 */
	public function setFromName($fromName)
	{
		$this->fromName = $fromName;
	}

	/**
	 * Set from email address
	 * @param string $fromEmail 
	 */
	public function setFromEmail($fromEmail)
	{
		$this->fromEmail = $fromEmail;
	}

	/**
	 * Set reply to address
	 * @param string $replyTo 
	 */
	public function setReplyTo($replyTo)
	{
		$this->replyTo = $replyTo;
	}

	/**
	 * Set html content for campaign
	 * @param string $htmlContent 
	 */
	public function setHtmlContent($htmlContent)
	{
		$this->htmlContent = $htmlContent;
	}

	/**
	 * Set text content for campaign
	 * @param string $textContent 
	 */
	public function setTextContent($textContent)
	{
		$this->textContent = $textContent;
	}

	/**
	 * Set status for campaign
	 * @param integer $status 
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * Returns list assigned to campaign
	 * @return Entity\SubscriberList
	 */
	public function getSubscriberList()
	{
		return $this->subscriberList;
	}
	
	/**
	 * @return array
	 */
	public function getActiveSubscribers()
	{
		$activeSubscribers = array();
		$subscribers = $this->getSubscriberList()->getSubscribers();

		foreach ($subscribers as $subscriber) {
			/* @var $subscriber Subscriber */
			if ( ! $subscriber->getActive()) {
				continue;
			}

			$activeSubscribers[] = $subscriber;
		}

		return $activeSubscribers;
	}

	/**
	 * Returns campaign subject
	 * @return string
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * Returns campaign name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Return campaign from address
	 * @return string
	 */
	public function getFromName()
	{
		return $this->fromName;
	}

	/**
	 * Returns campaign email from address
	 * @return string
	 */
	public function getFromEmail()
	{
		return $this->fromEmail;
	}

	/**
	 * Returns campaign replay to address
	 * @return string
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}

	/**
	 * Returns campaign html content
	 * @return string
	 */
	public function getHtmlContent()
	{
		return $this->htmlContent;
	}

	/**
	 * Returns campaign text content
	 * @return string
	 */
	public function getTextContent()
	{
		return $this->textContent;
	}

	/**
	 * Return campaign status
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

}
