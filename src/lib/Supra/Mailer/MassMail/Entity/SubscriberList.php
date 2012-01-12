<?php

namespace Supra\Mailer\MassMail\Entity;

use Supra\Database\Entity;

/**
 * @Entity
 */
class SubscriberList extends Entity
{

	/**
	 * Inverse Side
	 *
	 * @ManyToMany(targetEntity="Subscriber", mappedBy="lists")
	 */
	protected $subscribers;

	/**
	 * @Column(type="string", name="list_id", nullable=true, unique=true)
	 * @var string
	 */
	protected $listId;

	/**
	 * @Column(type="string", name="title", nullable=false)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="string", name="unsubscribe_page", nullable=true)
	 * @var string
	 */
	protected $unsubscribePage;

	/**
	 * @Column(type="boolean", name="confirmed_option", nullable=false)
	 * @var string
	 */
	protected $confirmedOption = false;

	/**
	 * Set subscriber list id
	 * @param string $listId 
	 */
	public function setListId($listId)
	{
		$this->listId = $listId;
	}

	/**
	 * Return subscriber list id
	 * @return string
	 */
	public function getListId()
	{
		return $this->listId;
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
		$this->title = trim($title);
	}

	/**
	 * Set unsubscribe page url
	 * @param string $url 
	 */
	public function setUnsubscribePage($url)
	{
		$this->unsubscribePage = $url;
	}

	/**
	 * Return unsubscribe page url
	 * @return string
	 */
	public function getUnsubscribePage()
	{
		return $this->unsubscribePage;
	}

	/**
	 * Set confirmed option
	 * @param boolean $state 
	 */
	public function setConfirmedOption($state)
	{
		$this->confirmedOption = (bool) $state;
	}

	/**
	 * Return confirmed option
	 * @return boolean
	 */
	public function getConfirmedOption()
	{
		return $this->confirmedOption;
	}

}
