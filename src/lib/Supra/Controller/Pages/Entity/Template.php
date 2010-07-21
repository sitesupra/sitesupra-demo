<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception;

/**
 * Page controller template class
 * @Entity
 * @Table(name="template")
 */
class Template extends Abstraction\Page
{
	/**
	 * Data class
	 * @var string
	 */
	static protected $dataClass = 'Supra\Controller\Pages\Entity\TemplateData';

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
	 * @param Abstraction\Page $parent
	 */
	public function setParent(Abstraction\Page $parent = null)
	{
		if ( ! is_null($parent)) {

			$this->matchDiscriminator($parent);

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
			throw new Exception("Template layout can be set to root template only");
		}
		if ($this->lock('templateLayout')) {
			if ($this->addUnique($this->templateLayouts, $templateLayout, 'media')) {
				$templateLayout->setTemplate($this);
			}
			$this->unlock('templateLayout');
		}
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
	 * @throws Exception if layout for this media already exists
	 */
	public function addLayout($media, Layout $layout)
	{
		$templateLayout = new TemplateLayout($media);
		$templateLayout->setLayout($layout);
		$templateLayout->setTemplate($this);
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

	/**
	 * Get layout object by
	 * @param string $media
	 * @return Layout
	 */
	public function getLayout($media)
	{
		$templateLayouts = $this->getTemplateLayouts();
		/* @var $templateLayout TemplateLayout */
		foreach ($templateLayouts as $key => $templateLayout) {
			if ($templateLayout->getMedia() == $media) {
				return $templateLayout->getLayout();
			}
		}
		throw new Exception("No layout found for template #{$this->getId()} media '{$media}'");
	}

	/**
	 * Get array of template hierarchy starting from the root
	 * @return Template[]
	 */
	public function getTemplatesHierarchy()
	{
		$template = $this;

		/* @var $templates Template[] */
		$templates = array();
		do {
			array_unshift($templates, $template);
			$template = $template->getParent();
		} while ( ! is_null($template));

		return $templates;
	}

}