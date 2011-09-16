<?php

namespace Supra\Locale;

/**
 * Locale object
 */
class Locale
{
	/**
	 * Locale ID
	 * @var string
	 */
	protected $id;

	/**
	 * Locale title
	 * @var string
	 */
	protected $title;

	/**
	 * Locale country name
	 * @var string
	 */
	protected $country;
	
	/**
	 * Locale properties
	 * @var array
	 */
	protected $properties;

	/**
	 * Return locale ID
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return locale title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Return locale country name
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * Set locale ID
	 * @param string $id 
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Set locale title
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * Set locale country name
	 * @param string $country 
	 */
	public function setCountry($country)
	{
		$this->country = $country;
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
	public function getProperty($name) {
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}
	}

}
