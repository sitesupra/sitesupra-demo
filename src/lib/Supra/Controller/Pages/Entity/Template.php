<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Set\PageSet;
use Supra\Controller\Pages\Entity\Theme;
use Supra\Controller\Pages\Entity\ThemeLayout;

/**
 * Page controller template class
 * @Entity(repositoryClass="Supra\Controller\Pages\Repository\TemplateRepository")
 * @method TemplateLocalization getLocalization(string $locale)
 */

class Template extends Abstraction\AbstractPage
{
	/**
	 * {@inheritdoc}
	 */

	const DISCRIMINATOR = self::TEMPLATE_DISCR;

	/**
	 * @OneToMany(targetEntity="TemplateLayout", mappedBy="template", cascade={"persist", "remove"}, indexBy="media")
	 * @var Collection
	 */
	protected $templateLayouts;

	/**
	 * @var Theme
	 */
	protected $theme;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->templateLayouts = new ArrayCollection();
	}

	/**
	 * Set templateLayout
	 * @param TemplateLayout $templateLayout
	 */
	public function addTemplateLayout(TemplateLayout $templateLayout)
	{
		// Not true anymore
//		if ($this->hasParent()) {
//			throw new Exception\RuntimeException("Template layout can be set to root template only");
//		}
		if ($this->lock('templateLayouts')) {

			$media = $templateLayout->getMedia();

			$this->templateLayouts->set($media, $templateLayout);
			$templateLayout->setTemplate($this);

			$this->unlock('templateLayouts');
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
	 * @param ThemeLayout $layout
	 * @return TemplateLayout
	 */
	public function addLayout($media, ThemeLayout $layout)
	{
		$templateLayout = new TemplateLayout($media);
		$templateLayout->setLayout($layout);
		$templateLayout->setTemplate($this);

		return $templateLayout;
	}

	/**
	 * Whether the layout exists
	 * @param string $media
	 * @return boolean
	 */
	public function hasLayout($media = TemplateLayout::MEDIA_SCREEN)
	{
		$has = $this->templateLayouts->offsetExists($media);

		return $has;
	}

	/**
	 * Get layout object by
	 * @param string $media
	 * @return ThemeLayout
	 */
	public function getLayout($media = TemplateLayout::MEDIA_SCREEN)
	{
		$templateLayouts = $this->getTemplateLayouts();

		if ($templateLayouts->offsetExists($media)) {
			$templateLayout = $templateLayouts->offsetGet($media);
			/* @var $templateLayout TemplateLayout */

			return $templateLayout->getLayout();
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

	/**
	 * @return Theme 
	 */
	public function getTheme()
	{
		if (empty($this->theme)) {

			$themeProvider = \Supra\ObjectRepository\ObjectRepository::getThemeProvider($this);

			$this->theme = $themeProvider->getCurrentTheme();
		}

		return $this->theme;
	}

	/**
	 * @param Theme $theme 
	 */
	public function setTheme(Theme $theme)
	{
		$this->theme = $theme;
	}

}
