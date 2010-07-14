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
		if ($this->writeOnce($this->template, $template)) {
			$template->addPlaceHolder($this);
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

	/**
	 * Set master object
	 * @param PageAbstraction $master
	 */
	public function setMaster(PageAbstraction $master)
	{
		$this->isInstanceOf($master, __NAMESPACE__ . '\Template', __METHOD__);
		$this->setTemplate($master);
	}
}
