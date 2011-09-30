<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;
use DateTime;
use Supra\Uri\Path;

/**
 * PageLocalization class
 * @Entity
 * @method PageLocalization getParent()
 */
class PageLocalization extends Abstraction\Localization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = 'page';
	
	/**
	 * @ManyToOne(targetEntity="Template", cascade={"persist"}, fetch="EAGER")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=true)
	 * @var Template
	 */
	protected $template;
	
	/**
	 * Limitation because of MySQL unique constraint 1k byte limit
	 * @Column(type="path", length="255")
	 * @var Path
	 */
	protected $path = null;

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
	 * @Column(type="datetime", nullable=true, name="schedule_time")
	 * @var DateTime
	 */
	protected $scheduleTime;
	
	/**
	 * NB! Eager load is because "publish" action includes Doctrine merge action 
	 * which will fail if object isn't initialized.
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement", cascade={"all"}, fetch="EAGER")
	 * @var ReferencedElement\LinkReferencedElement
	 */
	protected $redirect;
	
	/**
	 * @Column(type="datetime", nullable="true")
	 * @var DateTime
	 */
	protected $creationTime;

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
	 */
	public function setPath(Path $path)
	{
		$this->path = $path;
	}

	/**
	 * Get page path
	 * @return Path
	 */
	public function getPath()
	{
		return $this->path;
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
		
		// Check if not trying to set empty path to not root page
		if ( ! $page->isRoot() && $pathPart == '') {
			throw new Exception\PagePathException("Path cannot be empty", $this);
		}
		
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
	}

}
