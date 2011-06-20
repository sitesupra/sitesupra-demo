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
	 * @JoinColumn(name="template_id", referencedColumnName="id", nullable=false)
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
		$this->matchDiscriminator($master);
		$this->setTemplate($master);
	}
	
	/**
	 * Get master object (page/template)
	 * @return Template
	 */
	public function getMaster()
	{
		return $this->getTemplate();
	}

	/**
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->template;
	}

}