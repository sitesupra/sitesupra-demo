<?php

namespace Project\Sitemap;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Repository;

/**
 * SitemapBlock
 */
class SitemapBlock extends LinksBlock
{
	public function getPropertyDefinition()
	{
		return array();
	}
	
	public function execute()
	{
		$response = $this->getResponse();
		$em = ObjectRepository::getEntityManager($this);
		$pageRepo = $em->getRepository(Entity\Page::CN());
		$locale = $this->getRequest()->getLocale();
		/* @var $pageRepo Repository\PageRepository */
		
		$rootPages = $pageRepo->getRootNodes();
		$rootPage = $rootPages[0];
		
		if ( ! $rootPage instanceof Entity\Page) {
			$this->log->warn("No root page found in sitemap");
			return;
		}
		
		$localizations = array();
		
		// This called 2 queries
//		$children = $rootPage->getDescendants(5, false);
//		$localizations = array();
//
//		$ids = \Supra\Database\Entity::collectIds($children);
//
//		$localizations = $em->getRepository(Entity\PageLocalization::CN())
//				->findBy(array('master' => $ids, 'locale' => $locale));
//
//		$map = array_fill_keys($ids, null);
//
//		foreach ($localizations as $localization) {
//			$masterId = $localization->getMaster()->getId();
//			$map[$masterId] = $localization;
//		}
//
//		foreach ($map as $key => $nullCheck) {
//			if (is_null($nullCheck)) {
//				unset($map[$key]);
//			}
//		}
		
		// Manually creating getDescendants request with 5 levels
		$nsn = $rootPage->getNestedSetNode();

		$nsr = $nsn->getRepository();
		/* @var $nsr \Supra\NestedSet\DoctrineRepository */

		// @TODO: Managable sitemap depth
		$sc = $nsr->createSearchCondition();
		$sc->leftGreaterThan($rootPage->getLeftValue());
		$sc->leftLessThan($rootPage->getRightValue());
		$sc->levelLessThanOrEqualsTo($rootPage->getLevel() + 5);

		$oc = $nsr->createSelectOrderRule();
		$oc->byLeftAscending();

		$qb = $nsr->createSearchQueryBuilder($sc, $oc);
		/* @var $qb \Doctrine\ORM\QueryBuilder */

		// This loads all current locale localizations and masters with one query
		$qb->from(Entity\PageLocalization::CN(), 'l');
		$qb->andWhere('l.master = e')
				->andWhere('l.locale = :locale')
				->setParameter('locale', $locale);

		// Need to include "e" as well so it isn't requested by separate query
		$qb->select('l, e, p');
		$qb->andWhere('l.active = true');
		$qb->join('l.path', 'p');
		$qb->andWhere('p.path IS NOT NULL');
		$qb->andWhere('p.limited = false');

		$query = $qb->getQuery();
		/* @var $query \Doctrine\ORM\Query */
		$query->useResultCache(true, 300);
		$result = $query->getResult();
		
		// Filter out localizations only
		foreach ($result as $record) {
			if ($record instanceof Entity\PageLocalization) {
				$localizations[] = $record;
			}
		}
		
		$map = $this->addRealLevels($localizations);
		
		$response->getContext()
				->addCssLinkToLayoutSnippet('css', '/assets/css/page-sitemap.css');
		
		$response->assign('map', $map);
		$response->outputTemplate('sitemap.html.twig');
	}
	
	/**
	 * Appends real level information to localizations (ignores groups, strips out the news articles)
	 * @param array $localizations
	 * @return array
	 */
	private function addRealLevels($localizations)
	{
		$interval = array(array(-1, PHP_INT_MAX));
		$stopInterval = array(-1, -1);
		$level = 0;
		
		$map = array();
		
		foreach ($localizations as $localization) {
			
			$lft = $localization->getMaster()->getLeftValue();
			$rgt = $localization->getMaster()->getRightValue();
			
			// under the parent
			if ($lft > $interval[$level][0] && $rgt < $interval[$level][1]) {
				$level++;
				$interval[$level] = array($lft, $rgt);
			} else {
				while ($lft > $interval[$level - 1][1]) {
					$level--;
					
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
			
			$visibleInSitemap = $localization->isVisibleInSitemap();
			
			if ($visibleInSitemap) {
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
			
			// Don't show children pages for parent with visibility OFF as well
			if ( ! $visibleInSitemap) {
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
