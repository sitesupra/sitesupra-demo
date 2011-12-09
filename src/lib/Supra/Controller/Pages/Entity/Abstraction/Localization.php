<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\PermissionType;
use Supra\Controller\Pages\Entity\LockData;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Entity\TemplateLocalization;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\GroupLocalization;

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
abstract class Localization extends Entity implements AuditedEntityInterface
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
	 * @Draft:OneToOne(targetEntity="Supra\Controller\Pages\Entity\LockData", cascade={"persist", "remove"})
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
	 * Object's place holders. Doctrine requires this to be defined because
	 * owning side references to this class with inversedBy parameter
	 * @OneToMany(targetEntity="PlaceHolder", mappedBy="localization", cascade={"persist", "remove"}, indexBy="name")
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Construct
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		parent::__construct();
		$this->setLocale($locale);
		$this->placeHolders = new ArrayCollection();
	}
	
	/**
	 * @return Collection
	 */
	public function getPlaceHolders()
	{
		return $this->placeHolders;
	}
	
	/**
	 * Adds placeholder
	 * @param PlaceHolder $placeHolder
	 */
	public function addPlaceHolder(PlaceHolder $placeHolder)
	{
		if ($this->lock('placeHolders')) {
			if ($this->addUnique($this->placeHolders, $placeHolder, 'name')) {
				$placeHolder->setMaster($this);
			}
			$this->unlock('placeHolders');
		}
	}

	/**
	 * @param string $locale
	 */
	public function setLocale($locale)
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
	 * @return Collection
	 */
	public function getChildren()
	{
		$coll = new ArrayCollection();
		$master = $this->getMaster();
		
		if (empty($master)) {
			return $coll;
		}
		
		$masterChildren = $master->getChildren();
		
		foreach ($masterChildren as $child) {
			$localization = $child->getLocalization($this->locale);
			
			if ( ! empty($localization)) {
				$coll->add($localization);
			}
		}
		
		return $coll;
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
	
	/**
	 * @param Block $block
	 * @return boolean
	 */
	private function containsBlock(Block $block)
	{
		$localization = $block->getPlaceHolder()
				->getMaster();
		
		$contains = $localization->equals($this);
		
		return $contains;
	}
	
	/**
	 * @param Block $block
	 * @return boolean
	 */
	public function isBlockEditable(Block $block)
	{
		// Contents are editable if block belongs to the page
		if ($this->containsBlock($block)) {
			return true;
		}
		
		// Also if it's not locked
		if ( ! $block->getLocked()) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param Block $block
	 * @return boolean
	 */
	public function isBlockManageable(Block $block)
	{
		// Contents are editable if block belongs to the page
		if ($this->containsBlock($block)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param PlaceHolder $placeHolder
	 * @return boolean
	 */
	public function isPlaceHolderEditable(PlaceHolder $placeHolder)
	{
		// Place holder can be ediable if it belongs to the page
		$localization = $placeHolder->getMaster();
		
		if ($localization->equals($this)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param Entity $baseEntity
	 * @param string $locale
	 * @param AbstractPage $page
	 * @return Localization
	 */
	public static function factory(AbstractPage $page, $locale)
	{
		$localization = null;
		$discriminator = $page::DISCRIMINATOR;
		
		switch ($discriminator) {
			case Entity::APPLICATION_DISCR:
				$localization = new ApplicationLocalization($locale);
				break;
			
			
			case Entity::GROUP_DISCR:
				$localization = new GroupLocalization($locale, $page);
				break;
			
			
			case Entity::TEMPLATE_DISCR:
				$localization = new TemplateLocalization($locale);
				break;
			
			
			case Entity::PAGE_DISCR:
				$localization = new PageLocalization($locale);
				break;
			
			default:
				throw new \InvalidArgumentException("Discriminator $discriminator not recognized");
		}
		
		$localization->setMaster($page);
		
		return $localization;
	}
}
