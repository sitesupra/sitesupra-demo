<?php

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Uri\Path;
use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;

/**
 * Stores page full path
 * 
 * @Entity
 * @Table(uniqueConstraints={
 *		@UniqueConstraint(name="locale_path_idx", columns={"locale", "path"})
 * })
 */
class PageLocalizationPath extends VersionedEntity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * Limitation because of MySQL unique constraint 1k byte limit
	 * @Column(type="path", length=255, nullable=true)
	 * @var Path
	 */
	protected $path = null;

	/**
	 * Marks if the page is active
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $active = true;

//	/**
//	 * Marks, if page is with limited access (requires an authorization)
//	 * @Column(type="boolean", nullable=false)
//	 * @var boolean
//	 */
//	protected $limited = false;

	/**
	 * Marks, if page is visible in sitemap
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $visibleInSitemap = true;

	/**
	 * Path entity and owner localization ids are equals
	 * @param PageLocalization $localization
	 */
	public function __construct(PageLocalization $localization)
	{
		$this->id = $localization->getId();
		$this->locale = $localization->getLocaleId();
	}

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

	/**
	 * @return Path
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param mixed $path 
	 */
	public function setPath($path = null)
	{
		if ( ! $path instanceof Path && ! is_null($path)) {
			$path = new Path($path);
		}
		$this->path = $path;
	}

	/**
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * @param boolean $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

//	/**
//	 * @return boolean
//	 */
//	public function isLimited()
//	{
//		return $this->limited;
//	}
//
//	/**
//	 * @param boolean $limited
//	 */
//	public function setLimited($limited)
//	{
//		$this->limited = $limited;
//	}

	/**
	 * @return boolean
	 */
	public function isVisibleInSitemap()
	{
		return $this->visibleInSitemap;
	}

	/**
	 * @param boolean $visibleInSitemap 
	 */
	public function setVisibleInSitemap($visibleInSitemap)
	{
		$this->visibleInSitemap = $visibleInSitemap;
	}
	
}
