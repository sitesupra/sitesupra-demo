<?php

namespace Supra\Controller\Pages;

/**
 * Template Place Holder
 * @Entity
 * @Table(name="template_place_holder")
 */
class TemplatePlaceHolder extends PlaceHolder
{
	/**
	 * @ManyToOne(targetEntity="Template")
	 * @var Template
	 */
	protected $template;

	/**
	 * Set template
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->template = $template;
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
