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
	private $customConditions = array();

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
	public function getQueryBuilder()
	{
		// Clones the query builder for local usage
		$qb = clone $this->pageFinder->getQueryBuilder();
		$qb->from(Entity\PageLocalization::CN(), 'l');
		$qb->andWhere('l.master = e');
		$qb->join('l.path', 'p');
		$qb->select('l, e, p');

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

		// Custom conditions
		foreach ($this->customConditions as $customCondition) {
			$qb->andWhere($customCondition);
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

	public function addCustomCondition($customCondition)
	{
		$this->customConditions[] = $customCondition;
	}

	public function isVisibleInSitemap($visibleInSitemap)
	{
		$this->visibleInSitemap = $visibleInSitemap;
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

	public function getAncestors(Entity\Abstraction\Localization $localization, $sortOrder = 'ASC')
	{
		$page = $localization->getMaster();

		$em = $this->getEntityManager();

		$ancestorPageFinder = new PageFinder($em);

		$pageAncestors = $ancestorPageFinder->getAncestors($page);

		$qb = $this->getQueryBuilder();
		
		$pageAncestorIds = array();
		
		foreach ($pageAncestors as $pageAncestor) {
			$pageAncestorIds[] = $pageAncestor->getId();
		}

		if ( ! empty($pageAncestorIds)) {
			$this->addCustomCondition($qb->expr()->in('l.master', $pageAncestorIds));
		}
		
		$this->locale = $localization->getLocale();

		$qb = $this->getQueryBuilder();
		
		$qb->select('l');
		$qb->addOrderBy('e.level', $sortOrder);
		
		$query = $qb->getQuery();
		
		return $query->getResult();
	}

}
