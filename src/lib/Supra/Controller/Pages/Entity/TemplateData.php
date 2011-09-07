<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * TemplateData class
 * @Entity
 * @Table
 */
class TemplateData extends Abstraction\Data
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = 'template';
	
	/**
	 * @return Template
	 */
	public function getTemplate()
	{
		return $this->getMaster();
	}
	
	/**
	 * @param Template $template
	 */
	public function setTemplate(Template $template)
	{
		$this->setMaster($template);
	}

}