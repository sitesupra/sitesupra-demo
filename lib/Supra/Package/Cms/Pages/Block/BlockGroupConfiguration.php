<?php

namespace Supra\Package\Cms\Pages\Block;

class BlockGroupConfiguration
{
	protected $name;
	protected $title;
	protected $default;
	
	/**
	 * @param string $name
	 * @param string $title
	 * @param null|bool $default
	 */
	public function __construct($name, $title, $default = false)
	{
		$this->name = $name;
		$this->title = $title;
		$this->default = $default;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return bool
	 */
	public function isDefault()
	{
		return $this->default === true;
	}
}