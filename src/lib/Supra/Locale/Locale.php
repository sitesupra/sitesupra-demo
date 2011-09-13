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

}
