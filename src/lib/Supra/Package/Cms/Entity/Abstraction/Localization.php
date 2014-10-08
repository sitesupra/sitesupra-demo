<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Package\Cms\Entity\LockData;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Entity\TemplateLocalization;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\GroupLocalization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\AuditLog\TitleTrackingItemInterface;
use Supra\Controller\Pages\Exception\RuntimeException;
use Supra\Controller\Pages\Entity\PlaceHolderGroup;

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 * 		"page"			= "Supra\Package\Cms\Entity\PageLocalization",
 * 		"template"		= "Supra\Package\Cms\Entity\TemplateLocalization",
 * 		"application"	= "Supra\Package\Cms\Entity\ApplicationLocalization",
 * 		"group"			= "Supra\Package\Cms\Entity\GroupLocalization"
 * })
 */
abstract class Localization extends VersionedEntity implements
		AuditedEntity,
		TitleTrackingItemInterface,
		LocalizationInterface
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
	 * @Column(type="text")
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $originalTitle;

	/**
	 * The parent entity which stores hierarchy information, AbstractPage implementation
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\AbstractPage", cascade={"persist"}, inversedBy="localizations")
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
	 * @var AbstractPage
	 */
	protected $master;

	/**
	 * Object's lock
	 * @OneToOne(targetEntity="Supra\Package\Cms\Entity\LockData", cascade={"persist", "remove"})
	 * @var LockData
	 */
	protected $lock;

	/**
	 * Left here just because cascade in remove
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\BlockProperty", mappedBy="localization", cascade={"persist", "remove"}, fetch="LAZY")
	 * @var Collection
	 */
	protected $blockProperties;

	/**
	 * Object's place holders. Doctrine requires this to be defined because
	 * owning side references to this class with inversedBy parameter
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\Abstraction\PlaceHolder", mappedBy="localization", cascade={"persist", "remove"}, indexBy="name")
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
	 * Used for page localization only but moved to the abstract class so it can
	 * be fetched in DQL when join happens from BlockProperty side
	 * @OneToOne(targetEntity="Supra\Package\Cms\Entity\PageLocalizationPath", cascade={"remove", "persist", "merge"})
	 * @var PageLocalizationPath
	 * @TODO: remove field from audit scheme maybe?
	 */
	protected $path;

	/**
	 * Moved to abstraction so it can be used inside queries
	 * @Column(type="datetime", nullable=true)
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * Automatically set, required because of DQL Group By limitations reported as improvement suggestion in DDC-1236
	 * @Column(type="smallint", nullable=true)
	 * @var int
	 */
	protected $creationYear;

	/**
	 * See $creationYear doc
	 * @Column(type="smallint", nullable=true)
	 * @var int
	 */
	protected $creationMonth;
	
	/**
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\PlaceHolderGroup", mappedBy="localization", cascade={"persist", "remove"}, indexBy="name")
	 * @var Collection
	 */
	protected $placeHolderGroups;
	
	/**
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\LocalizationTag", mappedBy="localization", cascade={"persist", "remove"}, indexBy="name", fetch="EXTRA_LAZY")
	 * @var Collection
	 */
	protected $tags;

	/**
	 * Construct
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		parent::__construct();
		$this->setLocale($locale);
		$this->placeHolders = new ArrayCollection();
		$this->placeHolderGroups = new ArrayCollection();
		
		$this->tags = new ArrayCollection();
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getBlockProperties()
	{
		return $this->blockProperties;
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
	public function getLocaleId()
	{
		return $this->locale;
	}

	/**
	 * @deprecated use getLocaleId() instead
	 */
	public function getLocale()
	{
		return $this->getLocaleId();
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		// track only first title change
		if (is_null($this->originalTitle) && ! is_null($this->title) && ($this->title != $title)) {
			$this->originalTitle = $this->title;
		}

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

	public function overrideMaster(AbstractPage $master)
	{
		if (is_null($this->master)
				|| $this->master->getId() !== $master->getId()) {
			throw new RuntimeException("You can override master only with the same id");
		}

		$this->master = $master;
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
	 * Returns page antestor localziation array
	 * 
	 * @param int $levelLimit
	 * @param boolean $includeNode
	 * @return array
	 */
	public function getAncestors($levelLimit = 0, $includeNode = false)
	{
		$master = $this->getMaster();
		
		if (empty($master)) {
			return array();
		}
		
		$ancestors = array();
		
		$pageAncestors = $master->getAncestors($levelLimit, $includeNode);
		
		foreach ($pageAncestors as $ancestor) {
			$ancestors[] = $ancestor->getLocalization($this->locale);
		}
		
		return $ancestors;
	}

	/**
	 * @param string $type 
	 * @return ArrayCollection
	 */
	public function getAllChildren($type = __CLASS__)
	{
		return $this->getChildrenHelper($type, 0);
	}

	/**
	 * @param string $type 
	 * @return ArrayCollection
	 */
	public function getAllChildrenIds($type = __CLASS__)
	{
		$master = $this->getMaster();

		if (empty($master)) {
			return array();
		}

		$nsn = $master->getNestedSetNode();

		$nsr = $nsn->getRepository();
		/* @var $nsr \Supra\NestedSet\DoctrineRepository */

		$sc = $nsr->createSearchCondition();
		$sc->leftGreaterThan($master->getLeftValue());
		$sc->leftLessThan($master->getRightValue());
		$sc->levelGreaterThan($master->getLevel());

		$oc = $nsr->createSelectOrderRule();
		$oc->byLeftAscending();

		$qb = $nsr->createSearchQueryBuilder($sc, $oc);
		/* @var $qb \Doctrine\ORM\QueryBuilder */

		// This loads all current locale localizations and masters with one query
		$qb->from($type, 'l');
		$qb->andWhere('l.master = e')
				->andWhere('l.locale = :locale')
				->setParameter('locale', $this->locale);

		$qb->select('l.id');

		$query = $qb->getQuery();
		$result = $query->getResult();

		return $result;
	}

	/**
	 * @param string $type
	 * @return ArrayCollection 
	 */
	public function getChildren($type = __CLASS__)
	{
		return $this->getChildrenHelper($type, 1);
	}

	/**
	 * @return ArrayCollection
	 */
	private function getChildrenHelper($type = __CLASS__, $maxDepth = 1)
	{
		$coll = new ArrayCollection();
		$master = $this->getMaster();

		if (empty($master)) {
			return $coll;
		}

		$nsn = $master->getNestedSetNode();

		$nsr = $nsn->getRepository();
		/* @var $nsr \Supra\NestedSet\DoctrineRepository */

		$sc = $nsr->createSearchCondition();
		$sc->leftGreaterThan($master->getLeftValue());
		$sc->leftLessThan($master->getRightValue());
		$sc->levelGreaterThan($master->getLevel());

		if ($maxDepth) {
			$sc->levelLessThanOrEqualsTo($master->getLevel() + $maxDepth);
		}

		$oc = $nsr->createSelectOrderRule();
		$oc->byLeftAscending();

		$qb = $nsr->createSearchQueryBuilder($sc, $oc);
		/* @var $qb \Doctrine\ORM\QueryBuilder */

		// This loads all current locale localizations and masters with one query
		$qb->from($type, 'l');
		$qb->andWhere('l.master = e')
				->andWhere('l.locale = :locale')
				->setParameter('locale', $this->locale);

		// Need to include "e" as well so it isn't requested by separate query
		if ($type == PageLocalization::CN()) {
			$qb->select('l, e, p');
			$qb->join('l.path', 'p');
			$qb->andWhere('p.path IS NOT NULL');
			$qb->andWhere('l.active = true');
		} else {
			$qb->select('l, e');
		}

		$query = $qb->getQuery();
		$result = $query->getResult();

		// Filter out localizations only
		foreach ($result as $record) {
			if ($record instanceof Localization) {
				$coll->add($record);
			}
		}

//		foreach ($masterChildren as $child) {
//			$localization = $child->getLocalization($this->locale);
//			
//			if ( ! empty($localization)) {
//				$coll->add($localization);
//			}
//		}

		return $coll;
	}

	/**
	 * Loads only public children
	 * @return ArrayCollection
	 */
	public function getPublicChildren()
	{
		$coll = $this->getChildren(PageLocalization::CN());

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
	 * Returns page lock object
	 * @return LockData
	 */
	public function getLock()
	{
		return $this->lock;
	}

	/**
	 * @return bool
	 */
	public function isLocked()
	{
		return $this->lock !== null;
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

		if (empty($changeFrequency)) {
			$changeFrequency = self::CHANGE_FREQUENCY_WEEKLY;
		}

		if ( ! in_array($changeFrequency, $frequencies)) {
			$logger = ObjectRepository::getLogger($this);
			$logger->warn("Invalid frequency value '$changeFrequency' provided.");

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
		if ($pagePriority < 0 || $pagePriority > 1) {
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

	/**
	 * Used to improve audit log readability
	 */
	public function getOriginalTitle()
	{
		return $this->originalTitle;
	}

	/**
	 * @return array
	 */
	protected function getAuthorizationAncestorsDirect()
	{
		// This is overriden because page localizations themselves are not nested set element, so
		// we take master page, fetch all of its ancestors and then fetch page localizations from those.
		$ancestors = array();

		$master = $this->getMaster();
		$masterAncestors = $master->getAncestors();

		$ancestors[] = $master;

		foreach ($masterAncestors as $masterAncestor) {
			/* @var $masterAncestor AbstractPage */

			$ancestors[] = $masterAncestor;

			$ancestorLocalization = $masterAncestor->getLocalization($this->locale);

			if ( ! empty($ancestorLocalization)) {
				$ancestors[] = $ancestorLocalization;
			}
		}

		return $ancestors;
	}

	/**
	 * @param string $localizationType
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return string
	 */
	static function getPreviewFilenameForTypeAndLocalizationAndRevision($localizationType, $localizationId, $revisionId)
	{
		$ini = ObjectRepository::getIniConfigurationLoader(get_called_class());
		$webrootPath = $ini->getValue('system', 'site_assets_external_path', SUPRA_WEBROOT_PATH . 'assets/');

		$previewFilename = join(DIRECTORY_SEPARATOR, array(
			$webrootPath,
			'previews',
			$localizationType,
			md5($localizationId . $revisionId) . '.jpg'));

		return $previewFilename;
	}

	/**
	 * @param string $localizationType
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return string
	 */
	static function getPreviewUrlForTypeAndLocalizationAndRevision($localizationType, $localizationId, $revisionId)
	{
		$previewUrl = join(DIRECTORY_SEPARATOR, array(
			'/assets/previews',
			$localizationType,
			md5($localizationId . $revisionId) . '.jpg'));

		return $previewUrl;
	}

	/**
	 * @return string
	 */
	public function getPreviewUrl()
	{
		return static::getPreviewUrlForLocalizationAndRevision($this->getId(), $this->getRevisionId());
	}

	/**
	 * @return string
	 */
	public function getPreviewFilename()
	{
		return static::getPreviewFilenameForLocalizationAndRevision($this->getId(), $this->getRevisionId());
	}
	
	
	/**
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */
	public function getPlaceHolderGroups()
	{
		return $this->placeHolderGroups;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\PlaceHolderGroup $group
	 */
	public function addPlaceHolderGroup(PlaceHolderGroup $group)
	{
		$group->setLocalization($this);
		$this->placeHolderGroups->set($group->getName(), $group);
	}
	
	/**
	 * @return Collection
	 */
	public function getTagCollection()
	{
		return $this->tags;
	}
	
	/**
	 * @return array
	 */
	public function getTagArray()
	{
		$tagArray = array();

		foreach ($this->tags as $tag) {
			/* @var $tag \Supra\Controller\Pages\Entity\LocalizationTag */
			$tagArray[] = $tag->getName();
		}
		
		return $tagArray;
	}
	
	/**
	 * @param string $name
	 */
	public function addTag($tag)
	{
		$name = $tag->getName();
		$tag->setLocalization($this);
		
		$this->tags->set($name, $tag);
	}
}
