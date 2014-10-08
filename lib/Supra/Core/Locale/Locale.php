<?php

namespace Supra\Core\Locale;

/**
 * Locale object
 */
class Locale implements LocaleInterface
{
	const DEFAULT_CONTEXT = 'unified';

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $context = self::DEFAULT_CONTEXT;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $country;

	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * @var boolean
	 */
	protected $default = false;

	/**
	 *
	 */
	function __construct($context = self::DEFAULT_CONTEXT)
	{
		$this->context = $context;
	}

	/**
	 * @return string
	 * @throws \RuntimeException
	 */
	public function getId()
	{
		if (empty($this->id)) {
			throw new \RuntimeException('Locale id not set.');
		}

		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * @param string $country
	 */
	public function setCountry($country)
	{
		$this->country = $country;
	}

	/**
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param array $properties
	 */
	public function setProperties($properties)
	{
		$this->properties = $properties;
	}

	/**
	 * Sets property
	 * @param string $name
	 * @param mixed $value
	 */
	public function addProperty($name, $value)
	{
		$this->properties[$name] = $value;
	}

	/**
	 * Returns propery
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name)
	{
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}
	}

	/**
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 *
	 * @param boolean $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

	/**
	 * @return boolean
	 */
	public function isDefault()
	{
		return $this->default;
	}

	/**
	 * @param boolean $default
	 */
	public function setDefault($default)
	{
		$this->default = $default;
	}

	/**
	 * @return string
	 */
	public function getContext()
	{
		return $this->context;
	}
}
