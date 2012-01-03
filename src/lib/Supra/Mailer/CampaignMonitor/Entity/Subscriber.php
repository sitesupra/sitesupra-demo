<?php

namespace Supra\Mailer\CampaignMonitor\Entity;

use Supra\Database\Entity;

/**
 * @Entity
 */
class Subscriber extends Entity
{

	/**
	 * Owning Side
	 *
	 * @ManyToMany(targetEntity="SubscriberList", inversedBy="subscribers")
	 */
	protected $lists;

	/**
	 * @Column(type="string", name="email_address", nullable=false)
	 * @var string
	 */
	protected $emailAddress;

	/**
	 * @Column(type="string", name="name", nullable=false)
	 * @var string
	 */
	protected $name;

	/**
	 * Return subscriber email address
	 * @return string
	 */
	public function getEmailAddress()
	{
		return $this->emailAddress;
	}

	/**
	 * Set subscriber email address
	 * @param string $emailAddress 
	 */
	public function setEmailAddress($emailAddress)
	{
		$this->emailAddress = $emailAddress;
	}

	/**
	 * Return subscriber name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set subscriber name
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = trim($name);
	}

}
