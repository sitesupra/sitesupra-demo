<?php

namespace Supra\Controller\Pages;

/**
 * TemplateData class
 * @Entity
 * @Table(name="template_data")
 */
class TemplateData extends PageDataAbstraction
{
	/**
	 * @ManyToOne(targetEntity="Template", inversedBy="data")
	 * @var Template
	 */
	protected $template;

	/**
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->template = $template;
	}

	/**
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

}