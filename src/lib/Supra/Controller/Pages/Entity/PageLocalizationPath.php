<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Uri\Path;

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
	public function setPath($path)
	{
		if ( ! $path instanceof Path) {
			$path = new Path($path);
		}
		$this->path = $path;
	}
}
