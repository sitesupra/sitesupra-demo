<?php

namespace Supra\Core\Application;

class AbstractApplication implements ApplicationInterface
{
	const APPLICATION_ACCESS_PUBLIC = 'public';
	const APPLICATION_ACCESS_PRIVATE = 'private';
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $icon;

	/**
	 * @var string
	 */
	protected $route;

	/**
	 * @var string
	 */
	protected $access = self::APPLICATION_ACCESS_PRIVATE;

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

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $route
	 */
	public function setRoute($route)
	{
		$this->route = $route;
	}

	/**
	 * @return string
	 */
	public function getRoute()
	{
		return $this->route;
	}

	/**
	 * @return bool
	 */
	public function isPublic()
	{
		return $this->access == $this::APPLICATION_ACCESS_PUBLIC;
	}

	/**
	 * @return bool
	 */
	public function isPrivate()
	{
		return $this->access == $this::APPLICATION_ACCESS_PRIVATE;
	}

}