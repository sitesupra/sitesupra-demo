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

	/**
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return string
	 */
	public static function getPreviewUrlForLocalizationAndRevision($localizationId, $revisionId)
	{
		return static::getPreviewUrlForTypeAndLocalizationAndRevision('t', $localizationId, $revisionId);
	}

	/**
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return string
	 */
	public static function getPreviewFilenameForLocalizationAndRevision($localizationId, $revisionId)
	{
		return static::getPreviewFielnameForTypeAndLocalizationAndRevision('t', $localizationId, $revisionId);
	}

}