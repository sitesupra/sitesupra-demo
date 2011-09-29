<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Entity;

/**
 * Interface for page applications
 */
interface PageApplicationInterface
{
	/**
	 * Renerates the base path for page localization.
	 * Must NOT start and end with "/"
	 * @param Entity\PageLocalization $pageLocalization
	 * @return string
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization);
}
