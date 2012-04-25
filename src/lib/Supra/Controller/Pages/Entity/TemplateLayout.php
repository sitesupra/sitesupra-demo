<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;

/**
 * Page controller template-layout class
 * @Entity
 */
class TemplateLayout extends Abstraction\Entity implements AuditedEntityInterface
{
	/**
	 * {@inheritdoc}
	 */

	const DISCRIMINATOR = self::TEMPLATE_DISCR;

	/**
	 * 
	 */
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
	 * @var ThemeLayout
	 */
	protected $layout;

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
	 * @param ThemeLayout $layout 
	 */
	public function setLayout(ThemeLayout $layout)
	{
		$this->layoutName = $layout->getName();
		$this->layout = $layout;
	}

	/**
	 * @return ThemeLayout
	 */
	public function getLayout()
	{
		if (empty($this->layout)) {

			$templateTheme = $this->getTemplate()->getTheme();

			$this->layout = $templateTheme->getLayout($this->getLayoutName());
		}

		return $this->layout;
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