<?php

namespace Supra\Core\Application;

class AbstractApplication implements ApplicationInterface
{
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

}