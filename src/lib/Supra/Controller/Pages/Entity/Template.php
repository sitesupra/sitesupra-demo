<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Set\PageSet;

/**
 * Page controller template class
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\TemplateRepository")
 * @Table(name="template")
 * @method TemplateData getData(string $locale)
 */
class Template extends Abstraction\Page
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = 'template';
	
	/**
	 * @OneToMany(targetEntity="TemplateLayout", mappedBy="template", cascade={"persist", "remove"}, indexBy="media")
	 * @var Collection
	 */
	protected $templateLayouts;

	/**
	 * Template place holders
	 * @OneToMany(targetEntity="TemplatePlaceHolder", mappedBy="master", cascade={"persist", "remove"})
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
	 * Set templateLayout
	 * @param TemplateLayout $templateLayout
	 */
	public function addTemplateLayout(TemplateLayout $templateLayout)
	{
		if ($this->hasParent()) {
			throw new Exception\RuntimeException("Template layout can be set to root template only");
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
	 * @throws Exception\RuntimeException if layout for this media already exists
	 */
	public function addLayout($media, Layout $layout)
	{
		$templateLayout = new TemplateLayout($media);
		$templateLayout->setLayout($layout);
		$templateLayout->setTemplate($this);
	}

	/**
	 * Get layout object by
	 * @param string $media
	 * @return Layout
	 */
	public function getLayout($media = Layout::MEDIA_SCREEN)
	{
		$templateLayouts = $this->getTemplateLayouts();
		/* @var $templateLayout TemplateLayout */
		foreach ($templateLayouts as $key => $templateLayout) {
			if ($templateLayout->getMedia() == $media) {
				return $templateLayout->getLayout();
			}
		}
		throw new Exception\RuntimeException("No layout found for template #{$this->getId()} media '{$media}'");
	}

	/**
	 * Get array of template hierarchy starting from the root
	 * @return PageSet
	 */
	public function getTemplateHierarchy()
	{
		/* @var $templates Template[] */
		$templates = $this->getAncestors(0, true);
		$templates = array_reverse($templates);
		
		$pageSet = new PageSet($templates);
		
		return $pageSet;
	}
	
	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		return __CLASS__;
	}
	
}
