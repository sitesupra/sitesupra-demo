<?php

namespace Supra\Package\Cms\Pages\Finder;

use Supra\Controller\Pages\Entity;
use Supra\Locale\LocaleInterface;

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
	private $visibleInMenu = true;
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

		if ($this->pageFinder instanceof TemplateFinder) {

			$qb->from(Entity\TemplateLocalization::CN(), 'l');

			$qb->select('l');
		} else {

			$qb->from(Entity\PageLocalization::CN(), 'l');

			$qb->select('l, e2, p');
			
			$qb->join('l.path', 'p');
			$qb->join('l.master', 'e2');

			if ($this->active) {
				$qb->andWhere('l.active = true AND p.path IS NOT NULL');
			}

//			if ($this->public) {
//				$qb->andWhere('p.limited = false');
//			}

			if ($this->visibleInSitemap) {
				$qb->andWhere('l.visibleInSitemap = true');
			}

			if ($this->visibleInMenu) {
				$qb->andWhere('l.visibleInMenu = true');
			}

			if ( ! is_null($this->redirect)) {
				$qb->andWhere('l.redirect IS ' . ($this->redirect ? 'NOT ' : '') . 'NULL');
			}
		}

		$qb->andWhere('l.master = e');

		// Join only to fetch the master
		// It's important to include all or else extra queries will be executed
		//$qb->select('l, e2, p');

		if ( ! empty($this->locale)) {
			$qb->andWhere('l.locale = :locale')
					->setParameter('locale', $this->locale);
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
		$this->visibleInSitemap = (bool) $visibleInSitemap;
	}

	public function isVisibleInMenu($visibleInMenu)
	{
		$this->visibleInMenu = (bool) $visibleInMenu;
	}

	public function removeDefaultFilters()
	{
		$this->active = null;
		$this->public = null;
	}

	public function setLocale($locale)
	{
		if ($locale instanceof LocaleInterface) {
			$locale = $locale->getId();
		}
		$this->locale = $locale;
	}

	protected function filterResult($result)
	{
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
