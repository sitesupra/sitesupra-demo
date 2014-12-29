<?php

namespace Supra\Package\Cms\Entity;

/**
 * Page controller template-layout class
 * @Entity
 */
class TemplateLayout extends Abstraction\Entity
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