<?php

namespace Supra\User\Entity;

use Supra\Database\Entity;
use Supra\User\Exception;
use Doctrine\Common\Collections;
/**
 * Single user preferences group
 * @Entity
 */
class UserPreferencesGroup extends Entity
{
	/**
	 * User settings collection
	 * @OneToMany(targetEntity="Supra\User\Entity\UserPreference", mappedBy="group", cascade={"all"}, indexBy="name")
	 * @var Collections\Collection
	 */
	protected $preferences;
	
	
	/**
	 * 
	 */
	public function __construct()
	{
		parent::__construct();
		$this->preferences = new Collections\ArrayCollection();
	}
	
	/**
	 * @return Collections\Collection
	 */
	public function getPreferencesCollection()
	{
		return $this->preferences;
	}
	
	/**
	 * @param Collections\Collection $collection
	 */
	public function setPreferencesCollection($collection)
	{
		$this->preferences = $collection;
	}
	
}
