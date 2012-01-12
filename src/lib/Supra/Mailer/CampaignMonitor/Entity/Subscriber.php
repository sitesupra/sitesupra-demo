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
	 * @Column(type="string", name="confirm_hash", nullable=true)
	 * @var string
	 */
	protected $confirmHash;
	
	/**
	 * @Column(type="datetime", name="confirm_message_datetime", nullable=true)
	 * @var string
	 */
	protected $confirmMessageDatetime;
	
	/**
	 * @Column(type="boolean", name="active", nullable=false)
	 * @var type 
	 */
	protected $active = false;
	
	/**
	 * Set confirm hash
	 * @param string $hash 
	 */
	protected function setConfirmHash($hash)
	{
		$this->confirmHash = $hash;
	}
	
	/**
	 * Generate confirmation hash
	 */
	public function generateConfirmHash(){
		$this->setConfirmHash($this->generateId());
		$this->updateConfirmDateTime();
	}
	
	/**
	 * Gget confirm hash
	 * @return string
	 */
	public function getConfirmHash()
	{
		return $this->confirmHash;
	}
	
	/**
	 * Update confirmation date time
	 */
	protected function updateConfirmDateTime(){
		$this->confirmMessageDatetime = new \DateTime("now");
	}
	
	/**
	 * Return current confirmation date time
	 * @return string
	 */
	public function getConfirmDateTime()
	{
		return $this->confirmMessageDatetime;
	}
	
	
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
	 * Set subscriber active status
	 * @param boolean $state 
	 */
	public function setActive($state){
		$this->active = (bool) $state;
	}
	
	/**
	 * Return active subscriber status
	 * @return boolean
	 */
	public function getActive(){
		return $this->active;
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
