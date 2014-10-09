<?php

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Database\Entity;
use Doctrine\Common\Collections;

/**
 * Single user preferences collection
 * @Entity
 */
class UserPreferencesCollection extends Entity
{

	/**
	 * User settings collection
	 * @OneToMany(targetEntity="Supra\Package\CmsAuthentication\Entity\UserPreference", mappedBy="collection", cascade={"all"}, indexBy="name")
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
	public function getPreferences()
	{
		return $this->preferences;
	}

	/**
	 * @param Collections\Collection $preferences
	 */
	public function setPreferences($preferences)
	{
		$this->preferences = $preferences;
	}

}
