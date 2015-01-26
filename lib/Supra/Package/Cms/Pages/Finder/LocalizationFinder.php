<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Finder;

use Supra\Core\Locale\LocaleInterface;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Abstraction\Localization;

/**
 * LocalizationFinder
 */
class LocalizationFinder extends AbstractFinder
{

	/**
	 * @var PageFinder
	 */
	private $pageFinder;
//	private $active = true;
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

			$qb->from(TemplateLocalization::CN(), 'l');

			$qb->select('l');
		} else {

			$qb->from(PageLocalization::CN(), 'l');

			$qb->select('l, e2, p');
			
			$qb->join('l.path', 'p');
			$qb->join('l.master', 'e2');

//			if ($this->active) {
//				$qb->andWhere('l.active = true AND p.path IS NOT NULL');
//			}

			if ($this->public) {
				$qb->andWhere('l.publishedRevision IS NOT NULL');
			}

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

//	public function isActive($active)
//	{
//		$this->active = $active;
//	}

	public function isPublic($public)
	{
		$this->public = $public;

//		if ($public) {
//			$this->isActive(true);
//		}
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
//		$this->active = null;
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
			if ($entity instanceof Localization) {
				$localizationResult[] = $entity;
			}
		}

		return $localizationResult;
	}

	public function addFilterByParent(Localization $localization, $minDepth = 1, $maxDepth = null)
	{
		$this->setLocale($localization->getLocaleId());
		$this->pageFinder->addFilterByParent($localization->getMaster(), $minDepth, $maxDepth);
	}

	public function addFilterByChild(Localization $localization, $minDepth = 0, $maxDepth = null)
	{
		$this->setLocale($localization->getLocaleId());
		$this->pageFinder->addFilterByChild($localization->getMaster(), $minDepth, $maxDepth);
	}

}
