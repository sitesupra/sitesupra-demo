<?php

namespace Supra\Package\Cms\Entity\Theme;

use Supra\Package\Cms\Entity\Abstraction\Entity;

use Supra\Controller\Layout\Exception;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity 
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_in_theme_idx", columns={"name", "theme_id"})}))
 */
class ThemeLayout extends Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $filename;

	/**
	 * @ManyToOne(targetEntity="Theme", inversedBy="layouts")
	 * @JoinColumn(name="theme_id", referencedColumnName="id")
	 * @var Theme
	 */
	protected $theme;

	/**
	 * @OneToMany(targetEntity="ThemeLayoutPlaceholder", mappedBy="layout", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $placeholders;
	
	/**
	 * @OneToMany(targetEntity="ThemeLayoutPlaceholderGroup", mappedBy="layout", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $placeHolderGroups;
	
	
	/**
	 * 
	 */
	public function __construct()
	{
		parent::__construct();

		$this->placeholders = new ArrayCollection();
		$this->placeHolderGroups = new ArrayCollection();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @return string
	 */
	public function getFile()
	{
		\Log::warn('getFile() is depreacted, use getFilename() instead!');

		return $this->getFilename();
	}

	/**
	 * @param string $filename 
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * @return Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setTheme(Theme $theme = null)
	{
		$this->theme = $theme;
	}

	/**
	 * @return array
	 */
	public function getPlaceholders()
	{
		return $this->placeholders;
	}

	/**
	 * @param ThemeLayoutPlaceholder $placeholder 
	 */
	public function addPlaceholder(ThemeLayoutPlaceholder $placeholder)
	{
		$placeholder->setLayout($this);
		$this->placeholders[$placeholder->getName()] = $placeholder;
	}
	
	/**
	 * @param ThemeLayoutPlaceholder $placeholder 
	 */
	public function removePlaceholder(ThemeLayoutPlaceholder $placeholder)
	{
		$placeholder->setLayout(null);

		$this->placeholders->removeElement($placeholder);
	}
	
	/**
	 * @return ArrayCollection
	 */
	public function getPlaceHolderGroups()
	{
		return $this->placeHolderGroups;
	}

	public function addPlaceholderGroup(ThemeLayoutPlaceholderGroup $placeholderGroup)
	{
		$placeholderGroup->setLayout($this);
		$this->placeHolderGroups->set($placeholderGroup->getName(), $placeholderGroup);
	}
	
	public function removePlaceholderGroup(ThemeLayoutPlaceholderGroup $placeholderGroup)
	{
		$placeholderGroup->setLayout(null);
		$this->placeHolderGroups->removeElement($placeholderGroup);
	}

	/**
	 * @return string 
	 */
	protected function getFullFilename()
	{
		$theme = $this->getTheme();

		$rootDir = $theme->getRootDir();

		$fullFilename = $rootDir . DIRECTORY_SEPARATOR . $this->getFilename();

		return $fullFilename;
	}

	/**
	 * @return string
	 * @throws Exception\RuntimeException 
	 */
	public function getContent()
	{
		$fullFilename = $this->getFullFilename();

		if ( ! \file_exists($fullFilename) || ! \is_readable($fullFilename)) {
			throw new Exception\RuntimeException("Layout file {$fullFilename} is not found
					or not readable for layout {$this}");
		}

		$content = \file_get_contents($fullFilename);

		return $content;
	}

	/**
	 * @return array
	 */
	public function getPlaceholderNames()
	{
		return $this->getPlaceholders()->getKeys();
	}

}
