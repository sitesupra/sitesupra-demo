<?php

namespace Supra\Mailer\MassMail\Entity;

use Supra\Database\Entity;
use Doctrine\Common\Collections;

/**
 * @Entity
 */
class SubscriberList extends Entity
{

	/**
	 * @ManyToMany(targetEntity="Subscriber", mappedBy="lists")
	 * @var Collections\Collection
	 */
	protected $subscribers;

	/**
	 * @Column(type="string", name="title", nullable=false)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="string", name="description", nullable=true)
	 * @var string
	 */
	protected $description;

	public function __construct()
	{
		parent::__construct();
		$this->subscribers = new Collections\ArrayCollection();
	}
	
	/**
	 * Return list title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set list title
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	public function getSubscribers()
	{
		return $this->subscribers;
	}
		
	public function addSubscriber(Subscriber $subscriber)
	{
		$this->subscribers[] = $subscriber;
	}
	
	public function removeSubscriber(Subscriber $subscriber)
	{
		$this->subscribers->removeElement($subscriber);
	}
}
