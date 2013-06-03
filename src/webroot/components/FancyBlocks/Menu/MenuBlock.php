<?php

namespace Project\FancyBlocks\Menu;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Finder;
use Supra\Controller\Pages\Entity;

class MenuBlock extends BlockController
{

	public static function getPropertyDefinition()
	{
		$properties = array();

		return $properties;
	}

	protected function doExecute()
	{
		$pageFinder = $this->getPageFinder();
        $pageFinder->addLevelFilter(1, 1);
        
		$localizationFinder = $this->getLocalizationFinder($pageFinder);
        
		$qb = $localizationFinder->getQueryBuilder();
		$qb->andWhere('l.visibleInMenu = true');

		$results = $qb->getQuery()->getResult();
        $children = $this->findAllVisibleChildren($results);
		$items = $this->buildStructure($children);

		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		$response->assign('items', $items);
		$response->outputTemplate('index.html.twig');
	}
    
    
    protected function findAllVisibleChildren($pages)
    {
        $children = array();
        
        foreach($pages as $page) {
            if ($page->isVisibleInMenu()) {
                $children[] = $page;
                $data = $page->getChildren()->toArray();
                if ($data) {
                    $children = array_merge($children, $this->findAllVisibleChildren($data));
                }
            }
        }
        return $children;
    }
    

	protected function getPageFinder()
	{
		$em = ObjectRepository::getEntityManager($this);

		$pageFinder = new Finder\PageFinder($em);
		$pageFinder->addLevelFilter(1);

		return $pageFinder;
	}

	protected function getLocalizationFinder(Finder\PageFinder $pageFinder)
	{
		$locale = ObjectRepository::getLocaleManager($this)->getCurrent()->getId();

		$localizationFinder = new Finder\LocalizationFinder($pageFinder);
		$localizationFinder->setLocale($locale);

		return $localizationFinder;
	}

	/**
	 * Returns structured data
	 * @param array $results
	 * @return array 
	 */
	protected function buildStructure($results = array())
	{
		$interval = array(array(-1, PHP_INT_MAX));
		$stopInterval = array(-1, -1);
		$level = 0;

		$map = array();

		foreach ($results as $localization) {
			/* @var $localization Entity\PageLocalization */
			$lft = $localization->getMaster()->getLeftValue();
			$rgt = $localization->getMaster()->getRightValue();

			// under the parent
			if ($lft > $interval[$level][0] && $rgt < $interval[$level][1]) {
				$level ++;
				$interval[$level] = array($lft, $rgt);
			} else {
				while ($lft > $interval[$level - 1][1]) {
					$level --;

					if ($level < 0) {
						throw new \OutOfBoundsException("Negative sitemap level reached");
					}
				}
				$interval[$level] = array($lft, $rgt);
			}

			// Fix to hide news under the news application
			if ($lft > $stopInterval[0] && $rgt < $stopInterval[1]) {
				continue;
			}

			$path = $localization->getPathEntity();

			if (empty($path)) {
				continue;
			}

			$isActive = $path->isActive() && $localization->isActive();

			if ($isActive) {

				$map[] = array(
					'level' => $level,
					'localization' => $localization
				);
			}

			$updateStopper = false;

			// Fix for publications
			if ($localization instanceof Entity\ApplicationLocalization) {
				$updateStopper = true;
			}

			if ( ! $isActive) {
				$updateStopper = true;
			}

			if ($updateStopper) {
				// Don't change stopper if current interval includes the page
				if ( ! ($stopInterval[0] <= $lft && $stopInterval[1] >= $rgt)) {
					$stopInterval = array($lft, $rgt);
				}
			}
		}

		return $map;
	}

}
