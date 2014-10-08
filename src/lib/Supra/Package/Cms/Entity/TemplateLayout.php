<?php

namespace Supra\Package\Cms\Entity;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;

/**
 * Page controller template-layout class
 * @Entity
 */
class TemplateLayout extends Abstraction\Entity // implements AuditedEntity
{
	const DISCRIMINATOR = self::TEMPLATE_DISCR;

	const MEDIA_SCREEN = 'screen';
	const MEDIA_PRINT = 'print';

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $media;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $layoutName;

	/**
	 * @ManyToOne(targetEntity="Template", inversedBy="templateLayouts")
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
	 * @var Template
	 */
	protected $template;

	/**
	 * Constructor
	 * @param string $media
	 */
	public function __construct($media)
	{
		parent::__construct();
		$this->setMedia($media);
	}

	/**
	 * Set media
	 * @param string $media
	 */
	protected function setMedia($media)
	{
		$this->media = $media;
	}

	/**
	 * Get media
	 * @return string
	 */
	public function getMedia()
	{
		return $this->media;
	}

	/**
	 * @return string
	 */
	public function getLayoutName()
	{
		return $this->layoutName;
	}

	/**
	 * @param string $layoutName 
	 */
	public function setLayoutName($layoutName)
	{
		$this->layoutName = $layoutName;
	}

	/**
	 * @param ThemeLayoutInterface $layout
	 */
	public function setLayout(ThemeLayoutInterface $layout)
	{
		$this->layoutName = $layout->getName();
	}

	/**
	 * @return ThemeLayout
	 */
	public function getLayout()
	{
		throw new \Exception('Don\'t use me bro.');
		//if (empty($this->layout)) {

		$template = $this->getTemplate();

		$themeProvider = ObjectRepository::getThemeProvider($this);

		return $themeProvider->getCurrentThemeLayoutForTemplate($template, $this->getMedia());
		//}
		//return $this->layout;
	}

	/**
	 * Set template
	 * @param Template $template
	 */
	public function setTemplate(Template $template = null)
	{
		if ($this->writeOnce($this->template, $template)) {
			$this->template->addTemplateLayout($this);
		}
	}

	/**
	 * Get template
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}
}