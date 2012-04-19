<?php

namespace Supra\Controller\Pages\Finder;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Repository;

/**
 * Template Finder
 */
class TemplateFinder extends PageFinder
{
	/**
	 * @return Repository\PageAbstractRepository
	 */
	protected function getRepository()
	{
		return $this->em->getRepository(Entity\Template::CN());
	}
}
