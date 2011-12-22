<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Stores page full path
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="locale_path_idx", columns={"locale", "path"})}))
 */
class PageLocalizationPath extends Abstraction\Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;
	
	/**
	 * Limitation because of MySQL unique constraint 1k byte limit
	 * @Column(type="path", length="255", nullable="true")
	 * @var Path
	 */
	protected $path = null;

//	/**
//	 * Used when current page has no path (e.g. news application)
//	 * @Column(type="path", length="255")
//	 * @var Path
//	 */
//	protected $parentPath = null;
	
	/**
	 * Special ID setter for path regeneration command so the ID in draft and 
	 * public schemes are equal
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getLocale()
	{
		return $this->locale;
	}

	public function setLocale($locale)
	{
		$this->locale = $locale;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function setPath($path)
	{
		$this->path = $path;
	}

//	public function getParentPath()
//	{
//		return $this->parentPath;
//	}
//
//	public function setParentPath($parentPath)
//	{
//		$this->parentPath = $parentPath;
//	}
}
