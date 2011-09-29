<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Entity;
use Supra\Uri\Path;

/**
 * Interface for page applications
 */
interface PageApplicationInterface
{
	/**
	 * Renerates the base path for page localization.
	 * Must NOT start and end with "/"
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization);
}
