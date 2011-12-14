<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Set\PageSet;
use Supra\Controller\Pages\Exception;

/**
 * @Entity
 * @method GroupPage getMaster()
 */
class GroupLocalization extends Abstraction\Localization
{
	const DISCRIMINATOR = self::GROUP_DISCR;
	
	/**
	 * Flag which marks the entity created automatically on miss
	 * @var boolean
	 */
	private $persistent = true;
	
	/**
	 * Creates new group localization object, sets ID equal to master ID, will
	 * regenerate if persisted
	 * @param string $locale
	 * @param GroupPage $groupPage
	 */
	public function __construct($locale, GroupPage $groupPage)
	{
		parent::__construct($locale);
		$this->id = $groupPage->getId();
		$this->title = $groupPage->getTitle();
		$this->master = $groupPage;
		$this->persistent = false;
	}
	
	public function getTemplateHierarchy()
	{
		throw new Exception\RuntimeException("Template hierarchy cannot be called for a group page");
	}
	
	public function getPathPart()
	{
		return null;
	}
	
	public function getPath()
	{
		return null;
	}
	
	public function getParentPath()
	{
		$parent = $this->getParent();
		
		if (empty($parent)) {
			return null;
		}
		
		$path = $parent->getPath();
		
		if (is_null($path)) {
			$path = $parent->getParentPath();
		}
		
		return $path;
	}
	
	/**
	 * Update the title for master as well
	 * @param string $title
	 */
	public function setTitle($title)
	{
		parent::setTitle($title);
		
		$this->master->setTitle($title);
	}
	
	/**
	 * @return boolean
	 */
	public function isPersistent()
	{
		return $this->persistent;
	}

	/**
	 * Sets the entity as persisted
	 */
	public function setPersistent()
	{
		$this->persistent = true;
	}
}
