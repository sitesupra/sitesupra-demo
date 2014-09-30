<?php

namespace Supra\Package\Cms\Entity;

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
	 * @var integer
	 */
	private $numberChildren;

	/**
	 * @var \DateTime
	 */
	private $date;
	
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
	
	public function setNumberChildren($numberChildren)
	{
		$this->numberChildren = $numberChildren;
	}
	
	public function getNumberChildren()
	{
		return is_null($this->numberChildren) ? count($this->children) : $this->numberChildren;
	}

	public function hasCalculatedNumberChildren()
	{
		return ! is_null($this->numberChildren);
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @param \DateTime $date
	 */
	public function setGroupDate(\DateTime $date)
	{
		$this->date = $date;
	}
	
	/**
	 * @return \DateTime | null
	 */
	public function getGroupDate()
	{
		return $this->date;
	}
}
