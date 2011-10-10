<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\User\Entity\Abstraction\User;
use Supra\Authorization\PermissionType;
use Supra\Controller\Pages\Entity\LockData;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"page" = "Supra\Controller\Pages\Entity\PageLocalization",
 *		"template" = "Supra\Controller\Pages\Entity\TemplateLocalization",
 *		"application" = "Supra\Controller\Pages\Entity\ApplicationLocalization",
 *		"group" = "Supra\Controller\Pages\Entity\GroupLocalization"
 * })
 * @Table(uniqueConstraints={@UniqueConstraint(name="locale_path_idx", columns={"locale", "path"})}))
 */
abstract class Localization extends Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * Duplicate FK, still needed for DQL when it's not important what type the entity is
	 * @ManyToOne(targetEntity="AbstractPage", cascade={"persist"}, inversedBy="localizations", fetch="EAGER")
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
	 * @var AbstractPage
	 */
	protected $master;
	
	/**
	 * Object's lock
	 * @OneToOne(targetEntity="Supra\Controller\Pages\Entity\LockData", cascade={"persist", "remove"})
	 * @var LockData
	 */
	protected $lock;
	
	/**
	 * Left here just because cascade in remove
	 * @OneToMany(targetEntity="Supra\Controller\Pages\Entity\BlockProperty", mappedBy="localization", cascade={"persist", "remove"}, fetch="LAZY") 
	 * @var Collection 
	 */ 
	protected $blockProperties; 

	/**
	 * Construct
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		parent::__construct();
		$this->setLocale($locale);
	}

	/**
	 * @param string $locale
	 */
	protected function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
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
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set master object (page/template)
	 * @param AbstractPage $master
	 */
	public function setMaster(AbstractPage $master)
	{
		$this->matchDiscriminator($master);
		
//		if ($this->writeOnce($this->master, $master)) {
			$this->master = $master;
			$master->setLocalization($this);
//		}
	}
	
	/**
	 * Get master object (page/template)
	 * @return AbstractPage
	 */
	public function getMaster()
	{
		return $this->master;
	}
	
	/**
	 * Loads localization item parent
	 * @return Localization
	 */
	public function getParent()
	{
		$master = $this->getMaster();
		
		if (empty($master)) {
			return null;
		}
		
		$parent = $master->getParent();
		
		if (empty($parent)) {
			return null;
		}

		/* @var $parent AbstractPage */
		$parentData = $parent->getLocalization($this->locale);
		
		return $parentData;
	}
	
	/**
	 * Get page and it's template hierarchy starting with the root template
	 * @return PageSet
	 * @throws Exception\RuntimeException
	 */
	abstract public function getTemplateHierarchy();
	
	/**
	 * Returns page lock object
	 * @return LockData
	 */
	public function getLock()
	{
		return $this->lock;
	}
	
	/**
	 * Sets page lock object
	 * @param LockData $lock 
	 */
	public function setLock($lock)
	{
		$this->lock = $lock;
	}
}
