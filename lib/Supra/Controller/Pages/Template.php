<?php

namespace Supra\Controller\Pages;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Page controller template class
 * @Entity
 */
class Template extends PageAbstraction
{

	/**
	 * @OneToMany(targetEntity="TemplateData", mappedBy="page", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $data;

	/**
	 * @OneToMany(targetEntity="TemplateLayout", mappedBy="template", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $templateLayouts;

	/**
	 * @OneToMany(targetEntity="Template", mappedBy="parent")
	 * @var Collection
	 */
	protected $children;

	/**
     * @ManyToOne(targetEntity="Template", inversedBy="children")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
	 * @var Template
     */
	protected $parent;

	/**
	 * Template place holders
	 * @OneToMany(targetEntity="TemplatePlaceHolder", mappedBy="template", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $placeHolders;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->templateLayouts = new ArrayCollection();
		parent::__construct();
	}

	/**
	 * Removes all layout data if parent is set
	 * @param PageAbstraction $parent
	 */
	public function setParent(PageAbstraction $parent = null)
	{
		if ( ! is_null($parent)) {

			if ( ! ($parent instanceof Template)) {
				throw new Exception("The parent of Template must be the instance of Template");
			}

			// Remove associated template layout objects
			$templateLayouts = $this->getTemplateLayouts();
			foreach ($templateLayouts as $key => $templateLayout) {
				$templateLayouts->remove($key);
				self::getConnection()->remove($templateLayout);
			}
		}
		parent::setParent($parent);
	}

	/**
	 * Set templateLayout
	 * @param TemplateLayout $templateLayout
	 */
	public function addTemplateLayout(TemplateLayout $templateLayout)
	{
		if ( ! is_null($this->getParent())) {
			throw new Exception("Cannot set templateLayout to not root template");
		}
		$templateLayout->setTemplate($this);
		$this->templateLayouts->add($templateLayout);
	}

	/**
	 * Get template templateLayout
	 * @return Collection
	 */
	public function getTemplateLayouts()
	{
		return $this->templateLayouts;
	}

	/**
	 * Add layout for specific media
	 * @param string $media
	 * @param Layout $layout
	 */
	public function addLayout($media, Layout $layout)
	{
		$this->removeLayout($media);

		$templateLayout = new TemplateLayout();
		$templateLayout->setMedia($media);

		$templateLayout->setTemplate($this);
		$templateLayout->setLayout($layout);

		$this->addTemplateLayout($templateLayout);
	}

	/**
	 * Removes layout of specific media
	 * @param string $media
	 * @return boolean
	 */
	public function removeLayout($media)
	{
		$templateLayouts = $this->getTemplateLayouts();
		/* @var $templateLayout TemplateLayout */
		foreach ($templateLayouts as $key => $templateLayout) {
			if ($templateLayout->getMedia() == $media) {
				$templateLayout->remove($key);
				self::getConnection()->remove($templateLayout);
				return true;
			}
		}
		return false;
	}

}