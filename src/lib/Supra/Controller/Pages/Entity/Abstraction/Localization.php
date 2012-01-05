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
use Supra\ObjectRepository\ObjectRepository;

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
 */
abstract class Localization extends Entity implements AuditedEntityInterface
{
	const CHANGE_FREQUENCY_HOURLY = 'hourly';
	const CHANGE_FREQUENCY_DAILY = 'daily';
	const CHANGE_FREQUENCY_WEEKLY = 'weekly';
	const CHANGE_FREQUENCY_MONTHY = 'monthly';
	const CHANGE_FREQUENCY_YEARLY = 'yearly';
	const CHANGE_FREQUENCY_ALWAYS = 'always';
	const CHANGE_FREQUENCY_NEVER = 'never';
	
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
	 * The parent entity which stores hierarchy information, AbstractPage implementation
	 * @ManyToOne(targetEntity="AbstractPage", cascade={"persist"}, inversedBy="localizations")
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
	 *  Flag for hiding page from sitemap
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $visibleInSitemap = true;

	/**
	 * Flag for hiding page from menu
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $visibleInMenu = true;

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $includedInSearch = true;
	
	/**
	 * How frequently the page may change:
	 * 'Always' is used to denote documents that change each time that they are accessed. 
	 * 'Never' is used to denote archived URLs (i.e. files that will not be changed again).
	 * 
	 * @example always, hourly, daily, weekly, monthly, yearly, never. 
	 * Use constants. Localization::CHANGE_FREQUENCY_DAILY;
	 * 
	 * @Column(type="string")
	 * @var string
	 * 
	 */
	protected $changeFrequency = self::CHANGE_FREQUENCY_WEEKLY;
	
	/**
	 * The priority of that URL relative to other URLs on the site. 
	 * This allows webmasters to suggest to crawlers which pages are considered more important.
	 * The valid range is from 0.0 to 1.0, with 1.0 being the most important. 
	 * The default value is 0.5.
	 * 
	 * Rating all pages on a site with a high priority does not affect search listings, 
	 * as it is only used to suggest to the crawlers how important pages in the site are to one another.
	 * 
	 * @Column(type="string")
	 * @var string
	 */
	protected $pagePriority = '0.5';

	
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
	 * @return ArrayCollection
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
	 * Loads only public children
	 * @return ArrayCollection
	 */
	public function getPublicChildren()
	{
		$coll = $this->getChildren();
		
		foreach ($coll as $key => $child) {
			if ( ! $child instanceof PageLocalization) {
				$coll->remove($key);
				continue;
			}
			
			if ( ! $child->isPublic()) {
				$coll->remove($key);
				continue;
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

	/**
	 * @return boolean
	 */
	public function isVisibleInMenu()
	{
		return $this->visibleInMenu;
	}

	/**
	 * @param boolean $visibleInMenu 
	 */
	public function setVisibleInMenu($visibleInMenu)
	{
		$this->visibleInMenu = $visibleInMenu;
	}

	/**
	 * @return boolean 
	 */
	public function isIncludedInSearch()
	{
		return $this->includedInSearch;
	}

	/**
	 * @param boolean $includedInSearch 
	 */
	public function setIncludedInSearch($includedInSearch)
	{
		$this->includedInSearch = $includedInSearch;
	}
	
	/**
	 * Returns age change frequency for sitemap.xml
	 * @return string 
	 */
	public function getChangeFrequency()
	{
		return $this->changeFrequency;
	}

	/**
	 * Sets page change frequency for sitemap.xml
	 * Use constants like Localization::CHANGE_FREQUENCY_DAILY;
	 * @example always, hourly, daily, weekly, monthly, yearly, never. 
	 * @param string $changeFrequency 
	 */
	public function setChangeFrequency($changeFrequency)
	{
		$frequencies = array(
			self::CHANGE_FREQUENCY_HOURLY,
			self::CHANGE_FREQUENCY_DAILY,
			self::CHANGE_FREQUENCY_WEEKLY,
			self::CHANGE_FREQUENCY_MONTHY,
			self::CHANGE_FREQUENCY_YEARLY,
			self::CHANGE_FREQUENCY_ALWAYS,
			self::CHANGE_FREQUENCY_NEVER,
		);

		if ( ! in_array($changeFrequency, $frequencies)) {
			$logger = ObjectRepository::getLogger($this);
			$logger->error('Wrong frequency provided. Will use default');
			return false;
		}

		$this->changeFrequency = $changeFrequency;
	}

	/**
	 * Returns page priority for sitemap.xml
	 * @return string 
	 */
	public function getPagePriority()
	{
		return $this->pagePriority;
	}

	/**
	 * Sets page priority for sitemap.xml
	 * The valid range is from 0.0 to 1.0, with 1.0 being the most important. 
	 * @param string $pagePriority 
	 */
	public function setPagePriority($pagePriority)
	{
		if($pagePriority < 0 || $pagePriority > 1) {
			$logger = ObjectRepository::getLogger($this);
			$logger->error('Wrong priority provided. Will use default. 
				The valid range is from 0.0 to 1.0, with 1.0 being the most important');
			return false;
		}
		
		$this->pagePriority = $pagePriority;
	}

		
	/**
	 * Clear page lock on clone action
	 */
	public function __clone()
	{
		if ( ! empty($this->id)) {
			parent::__clone();
			$this->lock = null;
		}
	}
}
