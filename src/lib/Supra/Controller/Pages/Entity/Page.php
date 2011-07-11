<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception,
		Supra\Controller\Pages\Set\PageSet;

/**
 * Page controller page object
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\PageRepository")
 * @Table(name="page")
 */
class Page extends Abstraction\Page
{
	/**
	 * @OneToMany(targetEntity="PageData", mappedBy="page", cascade={"persist", "remove"}, indexBy="locale")
	 * @var Collection
	 */
	protected $data;

	/**
	 * @ManyToOne(targetEntity="Template", cascade={"persist"}, fetch="EAGER")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
	 * @var Template
	 */
	protected $template;

	/**
	 * Page place holders
	 * @OneToMany(targetEntity="PagePlaceHolder", mappedBy="master", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;

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

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception\RuntimeException("No template assigned to the page {$this->getId()}");
		}

		$pageSet = $template->getTemplateHierarchy();
		$pageSet[] = $this;

		return $pageSet;
	}

}
