<?php

namespace Supra\Controller\Pages\News;

use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Entity;
use DateTime;
use Supra\Uri\Path;

/**
 * News page application
 */
class NewsApplication implements PageApplicationInterface
{
	/**
	 * {@inheritdoc}
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization)
	{
		$creationTime = $pageLocalization->getCreationTime();

		// Shouldn't we set some other path for not published publications?
		if ( ! $creationTime instanceof DateTime) {
			$creationTime = new DateTime();
		}
		
		$pathString = $creationTime->format('Y/m/d');
		$path = new Path($pathString);
		
		return $path;
	}

	/**
	 * News application hasn't path
	 * @return boolean
	 */
	public function hasPath()
	{
		return false;
	}
}
