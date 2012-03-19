<?php

namespace Supra\Controller\Pages\Finder;

use Supra\Controller\Pages\Entity;

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
	
	private $locale;
	
	/**
	 * @param PageFinder $pageFinder
	 */
	public function __construct(PageFinder $pageFinder)
	{
		$this->pageFinder = $pageFinder;
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
			$this->setActive(true);
		}
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
}
