<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * TemplateLocalization class
 * @Entity
 * @method TemplateLocalization getParent()
 * @method Template getMaster()
 */
class TemplateLocalization extends Abstraction\Localization
{
	/**
	 * {@inheritdoc}
	 */
	const DISCRIMINATOR = self::TEMPLATE_DISCR;
	
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