<?php

namespace Supra\Core\Locale;

/**
 * Locale object
 */
interface LocaleInterface
{

	/**
	 * @return string
	 * @throws Exception\RuntimeException
	 */
	public function getId();

	/**
	 * @param string $id
	 */
	public function setId($id);

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @param string $title
	 */
	public function setTitle($title);

	/**
	 * @return string
	 */
	public function getCountry();

	/**
	 * @param string $country
	 */
	public function setCountry($country);

	/**
	 * @return array
	 */
	public function getProperties();

	/**
	 * @param array $properties
	 */
	public function setProperties($properties);

	/**
	 * Sets property
	 * @param string $name
	 * @param mixed $value 
	 */
	public function addProperty($name, $value);

	/**
	 * Returns propery
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name);

	/**
	 * 
	 * @return boolean
	 */
	public function isActive();

	/**
	 * 
	 * @param boolean $active
	 */
	public function setActive($active);

	/**
	 * @return boolean
	 */
	public function isDefault();

	/**
	 * @param boolean $default
	 */
	public function setDefault($default);

	/**
	 * @return string
	 */
	public function getContext();
}
