<?php

namespace Supra\Controller\Pages\News;

use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Entity;
use DateTime;

/**
 * News page application
 */
class NewsApplication implements PageApplicationInterface
{
	/**
	 * {@inheritdoc}
	 * @param Entity\PageLocalization $pageLocalization
	 * @return string
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization)
	{
		//TODO: replace with real creation time
		$creationTime = new DateTime();
//		$path = $pageLocalization->getPathPart();
		$path = $creationTime->format('Y/m/d');
		
		return $path;
	}
}
