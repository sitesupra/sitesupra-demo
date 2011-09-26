<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Exception;

/**
 * TemplateData class
 * @Entity
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
	
	/**
	 * Get page and it's template hierarchy starting with the root template
	 * @return PageSet
	 * @throws Exception\RuntimeException
	 */
	public function getTemplateHierarchy()
	{
		$template = $this->getTemplate();

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception\RuntimeException("Template is empty");
		}

		$templateSet = $template->getTemplateHierarchy();

		return $templateSet;
	}

}