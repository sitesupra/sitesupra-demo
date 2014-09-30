<?php

namespace Supra\Package\Cms\Pages\Finder;

use Supra\Package\Cms\Entity\Template;

class TemplateFinder extends PageFinder
{
	/**
	 * @return Supra\Package\Cms\Repository\PageAbstractRepository
	 */
	protected function getRepository()
	{
		return $this->em->getRepository(Template::CN());
	}
}
