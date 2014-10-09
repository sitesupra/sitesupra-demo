<?php

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Database\Entity;
use Supra\User\Exception;

/**
 * Single user preference item
 * @Entity
 */
class UserPreference extends Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="array", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * @ManyToOne(targetEntity="Supra\Package\CmsAuthentication\Entity\UserPreferencesCollection", inversedBy="preferences")
	 * @var UserPreferencesCollection
	 */
	protected $collection;

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param User $user
	 */
	public function __construct($name, $value, UserPreferencesCollection $collection)
	{
		parent::__construct();

		$this->name = $name;
		$this->value = $value;
		$this->collection = $collection;
	}

	/**
	 * @return UserPreferencesCollection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @param User $user
	 */
	public function setCollection(UserPreferencesCollection $collection)
	{
		$this->collection = $collection;
	}

}
