<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Temporary group page object, used for not automatically generated page
 * grouping inside the sitemap (usually in CMS)
 */
class TemporaryGroupPage extends GroupPage
{
	/**
	 * Children to show
	 * @var array
	 */
	private $children;

	/**
	 * @return array
	 */
	public function getChildren()
	{
		return $this->children;
	}

	/**
	 * @param array $children
	 */
	public function setChildren(array $children)
	{
		$this->children = $children;
	}
}
