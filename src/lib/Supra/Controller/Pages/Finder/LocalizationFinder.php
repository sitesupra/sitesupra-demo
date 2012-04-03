<?php

namespace Supra\Controller\Pages\Finder;

use Supra\Controller\Pages\Entity;
use Supra\Locale\Locale;

/**
 * LocalizationFinder
 */
class LocalizationFinder extends AbstractFinder
{

	/**
	 * @var PageFinder
	 */
	private $pageFinder;
	private $active = true;
	private $public = true;
	private $visibleInSitemap = false;
	private $redirect = null;

	/**
	 * @var string
	 */
	private $locale;

	/**
	 * @param PageFinder $pageFinder
	 */
	public function __construct(PageFinder $pageFinder)
	{
		$this->pageFinder = $pageFinder;

		parent::__construct($pageFinder->getEntityManager());
	}

	/**
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	protected function doGetQueryBuilder()
	{
		// Clones the query builder for local usage
		$qb = clone $this->pageFinder->getQueryBuilder();
		$qb->from(Entity\PageLocalization::CN(), 'l');
		$qb->andWhere('l.master = e');
		$qb->join('l.path', 'p');
		
		// Join only to fetch the master
		$qb->join('l.master', 'e2');
		
		// It's important to include all or else extra queries will be executed
		$qb->select('l, e2, p');

		if ( ! empty($this->locale)) {
			$qb->andWhere('l.locale = :locale')
					->setParameter('locale', $this->locale);
		}

		if ($this->active) {
			$qb->andWhere('l.active = true AND p.path IS NOT NULL');
		}

		if ($this->public) {
			$qb->andWhere('p.limited = false');
		}

		if ($this->visibleInSitemap) {
			$qb->andWhere('l.visibleInSitemap = true');
		}

		if ( ! is_null($this->redirect)) {
			$qb->andWhere('l.redirect IS ' . ($this->redirect ? 'NOT ' : '') . 'NULL');
		}

		return $qb;
	}

	public function isActive($active)
	{
		$this->active = $active;
	}

	public function isPublic($public)
	{
		$this->public = $public;

		if ($public) {
			$this->isActive(true);
		}
	}

	public function isRedirect($redirect)
	{
		$this->redirect = $redirect;
	}

	public function isVisibleInSitemap($visibleInSitemap)
	{
		$this->visibleInSitemap = $visibleInSitemap;
	}
	
	public function removeDefaultFilters()
	{
		$this->active = null;
		$this->public = null;
	}

	public function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * Filter out localizations only
	 * @return array
	 */
	public function getResult()
	{
		$result = parent::getResult();
		$localizationResult = array();

		foreach ($result as $entity) {
			if ($entity instanceof Entity\Abstraction\Localization) {
				$localizationResult[] = $entity;
			}
		}

		return $localizationResult;
	}
	
	public function addFilterByParent(Entity\Abstraction\Localization $localization, $minDepth = 1, $maxDepth = null)
	{
		$this->setLocale($localization->getLocale());
		$this->pageFinder->addFilterByParent($localization->getMaster(), $minDepth, $maxDepth);
	}
	
	public function addFilterByChild(Entity\Abstraction\Localization $localization, $minDepth = 0, $maxDepth = null)
	{
		$this->setLocale($localization->getLocale());
		$this->pageFinder->addFilterByChild($localization->getMaster(), $minDepth, $maxDepth);
	}

}
