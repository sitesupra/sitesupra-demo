<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Page controller template-layout class
 * @Entity
 * @Table(name="template_layout")
 */
class TemplateLayout extends Abstraction\Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $media;

	/**
	 * @ManyToOne(targetEntity="Layout", cascade={"persist"})
	 * @var Layout
	 */
	protected $layout;

	/**
	 * @ManyToOne(targetEntity="Template")
	 * @var Template
	 */
	protected $template;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set media
	 * @param string $media
	 */
	public function setMedia($media)
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
	 * Set layout
	 * @param Layout $layout
	 */
	public function setLayout(Layout $layout)
	{
		$this->layout = $layout;
	}

	/**
	 * Get template layout
	 * @return Layout
	 */
	public function getLayout()
	{
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