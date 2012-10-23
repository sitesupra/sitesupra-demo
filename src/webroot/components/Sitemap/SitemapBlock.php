<?php

namespace Project\Sitemap;

use Supra\Controller\Pages\BlockController;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Finder\LocalizationFinder;
use Supra\Controller\Pages\Finder\PageFinder;
use Supra\Controller\Pages\Finder\Organizer\PageLocalizationLevelOrganizer;

/**
 * Description of SitemapBlock
 */
class SitemapBlock extends BlockController
{
	protected function doExecute()
	{
		$response = $this->getResponse();

		if ( ! $response instanceof Response\TwigResponse) {
			$this->log->warn("Only TwigResponse supported");
			return;
		}

		$em = ObjectRepository::getEntityManager($this);
		$pageFinder = new PageFinder($em);
		$pageFinder->addLevelFilter(0, 2);
		
		$localizationFinder = new LocalizationFinder($pageFinder);
		$localizationFinder->isVisibleInSitemap(true);
		$localizationFinder->setLocale(ObjectRepository::getLocaleManager($this)->getCurrent());

		$result = $localizationFinder->getResult();

		$organizer = new PageLocalizationLevelOrganizer();
		$tree = $organizer->organize($result);

		$response->assign('sitemap', $tree);
		$response->outputTemplate('sitemap.html.twig');
	}
}
