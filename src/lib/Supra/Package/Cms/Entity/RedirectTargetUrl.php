<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 */
class RedirectTargetUrl extends Abstraction\RedirectTarget
{
	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $url;

	public function getRedirectUrl()
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}
}