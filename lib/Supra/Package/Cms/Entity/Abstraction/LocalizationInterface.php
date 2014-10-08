<?php

namespace Supra\Package\Cms\Entity\Abstraction;

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
