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
		if ($this->template == $template) {
			return;
		}
		if ( ! empty($this->template)) {
			throw new Exception("Not allowed to change template for template data object #{$this->getId()}");
		}
		if ($this->lock('template')) {
			$this->template = $template;
			$template->setData($this);
			$this->unlock('template');
		}
	}

	/**
	 * Set master template
	 * @param PageAbstraction $master
	 */
	public function setMaster(PageAbstraction $master)
	{
		$this->isInstanceOf($master, __NAMESPACE__ . '\Template', __METHOD__);
		$this->setTemplate($master);
	}

	/**
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

}