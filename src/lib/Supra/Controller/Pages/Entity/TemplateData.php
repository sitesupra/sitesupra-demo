<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * TemplateData class
 * @Entity
 * @Table(name="template_data")
 */
class TemplateData extends Abstraction\Data
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
	 * @param Abstraction\Page $master
	 */
	public function setMaster(Abstraction\Page $master)
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