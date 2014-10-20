<?php

namespace Supra\Package\Cms\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;

/**
 * Page controller template class
 * @Entity(repositoryClass="Supra\Package\Cms\Repository\TemplateRepository")
 * @method TemplateLocalization getLocalization(string $locale)
 */

class Template extends Abstraction\AbstractPage
{
	/**
	 * {@inheritdoc}
	 */

	const DISCRIMINATOR = self::TEMPLATE_DISCR;

	/**
	 * @OneToMany(
	 *		targetEntity="TemplateLayout",
	 *		mappedBy="template",
	 *		cascade={"persist", "remove"},
	 *		indexBy="media"
	 * )
	 * 
	 * @var Collection
	 */
	protected $templateLayouts;

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
	 * Add layout for specific media.
	 * 
	 * @param string $media
	 * @param ThemeLayoutInterface $layout
	 * @return TemplateLayout
	 */
	public function addLayout($media, ThemeLayoutInterface $layout)
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

//	/**
//	 * Get layout object by
//	 * @param string $media
//	 * @return ThemeLayout
//	 */
//	public function getLayout($media = TemplateLayout::MEDIA_SCREEN)
//	{
//		throw new \Exception("Don't use me.");
//
//		$templateLayouts = $this->getTemplateLayouts();
//
//		if ($templateLayouts->offsetExists($media)) {
//			$templateLayout = $templateLayouts->offsetGet($media);
//			/* @var $templateLayout TemplateLayout */
//
//			return $templateLayout->getLayout();
//		}
//
//		throw new Exception\RuntimeException("No layout found for template #{$this->getId()} media '{$media}'");
//	}

	/**
	 * Get layout name for specified media type.
	 *
	 * @param string $media
	 * @return string
	 */
	public function getLayoutName($media = TemplateLayout::MEDIA_SCREEN)
	{
		$templateLayouts = $this->getTemplateLayouts();

		if ($templateLayouts->offsetExists($media)) {
			$templateLayout = $templateLayouts->offsetGet($media);
			/* @var $templateLayout TemplateLayout */

			return $templateLayout->getLayoutName();
		}

		throw new Exception\RuntimeException(sprintf(
				'Template [%s] has no layout for media [%s]',
				$this->getId(),
				$media
		));
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
