<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;
use DateTime;
use Supra\Uri\Path;
use Supra\Controller\Pages\Entity\Page;
use Supra\Search\IndexedDocument;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Uri\NullPath;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;

/**
 * PageLocalization class
 * @Entity
 * @method PageLocalization getParent()
 * @method Page getMaster()
 */
class PageLocalization extends Abstraction\Localization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::PAGE_DISCR;

	/**
	 * @ManyToOne(targetEntity="Template")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=true)
	 * @var Template
	 */
	protected $template;

	/**
	 * @OneToOne(targetEntity="PageLocalizationPath", cascade={"remove", "persist", "merge"})
	 * @var PageLocalizationPath
	 * @TODO: remove field from audit scheme maybe?
	 */
	protected $path;

	/**
	 * @Column(type="string", name="path_part")
	 * @var string
	 */
	protected $pathPart = '';

	/**
	 * @Column(type="string", name="meta_description")
	 * @var string
	 */
	protected $metaDescription = '';

	/**
	 * @Column(type="string", name="meta_keywords")
	 * @var string
	 */
	protected $metaKeywords = '';

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $active = true;
	
	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $limitedAccess = false;

	/**
	 * @Column(type="datetime", nullable=true, name="schedule_time")
	 * @var DateTime
	 */
	protected $scheduleTime;

	/**
	 * Redirect information if any
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement", cascade={"all"})
	 * @var ReferencedElement\LinkReferencedElement
	 */
	protected $redirect;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * Automatically set, required because of DQL Group By limitations reported as improvement suggestion in DDC-1236
	 * @Column(type="smallint")
	 * @var int
	 */
	protected $creationYear;

	/**
	 * See $creationYear doc
	 * @Column(type="smallint")
	 * @var int
	 */
	protected $creationMonth;

	/**
	 * Used to reset the creation time on first publish or creation time set
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $publishTimeSet = false;

	/**
	 * Additionally set creation time
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		parent::__construct($locale);

		$this->path = new PageLocalizationPath();
		$this->path->setLocale($locale);
		$this->setCreationTime();
		$this->publishTimeSet = false;

		$this->wasActive = $this->active;
	}

	/**
	 * @return Page
	 */
	public function getPage()
	{
		return $this->getMaster();
	}

	/**
	 * @param Page $page
	 */
	public function setPage(Page $page)
	{
		$this->setMaster($page);
	}

	/**
	 * Set page template
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->template = $template;
	}
	
	/**
	 * Set null as page template, 
	 * used for deleted pages that have unexisted template assigned
	 * @param Template $template
	 */
	public function setNullTemplate()
	{
		$this->template = null;
	}

	/**
	 * Get page template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Get page and it's template hierarchy starting with the root template
	 * @return PageSet
	 * @throws Exception\RuntimeException
	 */
	public function getTemplateHierarchy()
	{
		$template = $this->getTemplate();
		$page = $this->getPage();

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception\RuntimeException("No template assigned to the page {$page->getId()}");
		}

		$pageSet = $template->getTemplateHierarchy();
		$pageSet[] = $page;

		return $pageSet;
	}

	/**
	 * Set page path
	 * Should be called from the PagePathGenerator only!
	 * @param Path $path
	 * @param boolean $active
	 */
	public function setPath(Path $path = null, $active = true, $limited = false)
	{
		$this->getPathEntity()->setPath($path);
		$this->getPathEntity()->setActive($active);
		$this->getPathEntity()->setLimited($limited);
	}

	/**
	 * Get page actual path
	 * @return Path
	 */
	public function getPath()
	{
		$path = $this->getRealPath(true);

		return $path;
	}

	/**
	 * Will return real path or null path record if page not active and only
	 * active path is requested.
	 * 
	 * @param boolean $activeOnly
	 * @return Path
	 */
	private function getRealPath($activeOnly)
	{
		$path = $this->getPathEntity()->getPath();
		$active = $this->getPathEntity()->isActive();

		// Method will return NullPath instance
		if (is_null($path)) {
			$path = NullPath::getInstance();
			$this->getPathEntity()->setPath($path);
		} elseif ($activeOnly && ! $active) {
			$path = NullPath::getInstance();
		}

		return $path;
	}

	/**
	 * @return PageLocalizationPath
	 */
	public function getPathEntity()
	{
		if (is_null($this->path)) {
			$this->path = new PageLocalizationPath();
			$this->path->setLocale($this->locale);
		}

		return $this->path;
	}

	/**
	 * @param PageLocalizationPath $pathEntity
	 */
	public function setPathEntity(PageLocalizationPath $pathEntity)
	{
		$this->path = $pathEntity;
	}

	/**
	 * Sets path part of the page
	 * @param string $pathPart
	 * @TODO: added exception for experimental purposes
	 * @throws Exception\PagePathException if trying to set path for the root page
	 */
	public function setPathPart($pathPart)
	{
		// Remove all special characters
		$pathPart = preg_replace('!\?/\\\\#!', '', $pathPart);
		$pathPart = trim($pathPart);
		$page = $this->getMaster();

		if (empty($page)) {
			throw new Exception\RuntimeException('Page data page object must be set before setting path part');
		}

		// Check if path part is not added to the root page
		if ($page->isRoot() && $pathPart != '') {
			throw new Exception\PagePathException("Root page cannot have path assigned", $this);
		}

		// Now with news application it's possible...
		// FIXME: maybe should allow for applications only?
//		// Check if not trying to set empty path to not root page
//		if ( ! $page->isRoot() && $pathPart == '') {
//			throw new Exception\PagePathException("Path cannot be empty", $this);
//		}

		$this->pathPart = $pathPart;
	}

	/**
	 * @return string
	 */
	public function getPathPart()
	{
		return $this->pathPart;
	}

	/**
	 * @return string
	 */
	public function getMetaDescription()
	{
		return $this->metaDescription;
	}

	/**
	 * @param string $metaDescription
	 */
	public function setMetaDescription($metaDescription)
	{
		$this->metaDescription = $metaDescription;
	}

	/**
	 * @return string
	 */
	public function getMetaKeywords()
	{
		return $this->metaKeywords;
	}

	/**
	 * @param string $metaKeywords
	 */
	public function setMetaKeywords($metaKeywords)
	{
		$this->metaKeywords = $metaKeywords;
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

	/**
	 * @return DateTime
	 */
	public function getScheduleTime()
	{
		return $this->scheduleTime;
	}

	/**
	 * @param DateTime $scheduleTime
	 */
	public function setScheduleTime(DateTime $scheduleTime)
	{
		$this->scheduleTime = $scheduleTime;
	}

	/**
	 * Unsets the schedule
	 */
	public function unsetScheduleTime()
	{
		$this->scheduleTime = null;
	}

	/**
	 * @return ReferencedElement\LinkReferencedElement
	 */
	public function getRedirect()
	{
		return $this->redirect;
	}

	/**
	 * @param ReferencedElement\LinkReferencedElement $redirect
	 */
	public function setRedirect(ReferencedElement\LinkReferencedElement $redirect = null)
	{
		$this->redirect = $redirect;
	}

	/**
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * Sets creation time
	 * @param DateTime $creationTime
	 */
	public function setCreationTime(DateTime $creationTime = null)
	{
		if (is_null($creationTime)) {
			$creationTime = new DateTime();
		}

		$this->creationTime = $creationTime;
		$this->creationYear = (int) $creationTime->format('Y');
		$this->creationMonth = (int) $creationTime->format('n');
		$this->publishTimeSet = true;
	}

	/**
	 * Return if the creation time is already set for publishing
	 * @return boolean
	 */
	public function isPublishTimeSet()
	{
		return $this->publishTimeSet;
	}

	/**
	 * @return array
	 */
	protected function getAuthizationAncestorsDirect()
	{
		// This is overriden because page localizations themselves are not nested set element, so
		// we take master page, fetch all of its ancestors and then fetch page localizations from those.
		$ancestors = array();

		$master = $this->getMaster();
		$masterAncestors = $master->getAncestors();
		
		$ancestors[] = $master;

		foreach ($masterAncestors as $masterAncestor) {
			/* @var $masterAncestor Page */

			$ancestors[] = $masterAncestor;

			$ancestorLocalization = $masterAncestor->getLocalization($this->locale);

			if ( ! empty($ancestorLocalization)) {
				$ancestors[] = $ancestorLocalization;
			}
		}

		return $ancestors;
	}

	/**
	 * Gets page level by PATH not structure, ignores group pages
	 * @return int
	 */
	public function getLevel()
	{
		return $this->getPath()->getDepth();
	}

	/**
	 * Clone magic, recurse clone for redirect and path
	 */
	public function __clone()
	{
		if ( ! empty($this->id)) {
			parent::__clone();
			
			if ($this->redirect instanceof ReferencedElement\LinkReferencedElement) {
				$this->redirect = clone $this->redirect;
			}
			
			$this->path = new PageLocalizationPath();
			$this->path->setLocale($this->locale);
		}
	}

	/**
	 * Whether the page is available
	 * @return boolean
	 */
	public function isPublic()
	{
		// This page not active
		if ( ! $this->active) {
			return false;
		}
		
		// Any parent not active
		$active = $this->getPathEntity()
				->isActive();

		if ( ! $active) {
			return false;
		}
		
		$userProvider = ObjectRepository::getUserProvider($this);
		$isUserAuthorized = false;
		if ($userProvider instanceof \Supra\User\UserProvider) {
			$currentUser = $userProvider->getSignedInUser(false);
			$isUserAuthorized = ($currentUser instanceof User);
		}
		
		if ($this->limitedAccess && ! $isUserAuthorized) {
			return false;
		}

		$isLimited = $this->getPathEntity()
				->isLimited();
		
		if ($isLimited && ! $isUserAuthorized) {
			return false;
		}
		
		// Path is null for some other reason
		$path = $this->getPathEntity()
				->getPath();

		if (is_null($path)) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $locale
	 */
	public function setLocale($locale)
	{
		parent::setLocale($locale);

		if ( ! empty($this->path)) {
			$this->path->setLocale($locale);
		}
	}
	
	public function resetPath() 
	{
		$this->path = null;
	}
	
	/**
	 * Helper for the publish method
	 */
	public function initializeProxyAssociations()
	{
		if ($this->template) {
			$this->template->getId();
		}
		if ($this->path) {
			$this->path->getId();
		}
		if ($this->redirect) {
			$this->redirect->getId();
		}
	}
	
	/**
	 * @param boolean $access
	 */
	public function setLimitedAccessPage($access)
	{
		$this->limitedAccess = $access;
	}
	
	public function isLimitedAccessPage()
	{
		return $this->limitedAccess;
	}
	
//	public function hasLimitedAccessParent()
//	{
//		$has = false;
//		
//		$parent = $this->getParent();
//		while( ! is_null($parent)) {
//			$isParentLimited = $parent->isLimitedAccessPage();
//			
//			if ($isParentLimited) {
//				$has = true;
//				break;
//			}
//			
//			$parent = $parent->getParent();
//		}
//		
//		return $has;
//	}

}
