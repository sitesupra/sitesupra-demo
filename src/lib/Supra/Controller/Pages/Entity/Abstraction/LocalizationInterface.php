<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

/**
 * API methods
 */
interface LocalizationInterface
{
	/**
	 *
	 */
	static function getPreviewUrlForLocalizationAndRevision($localizationId, $revisionId);

	/**
	 *
	 */
	static function getPreviewFilenameForLocalizationAndRevision($localizationId, $revisionId);
}
