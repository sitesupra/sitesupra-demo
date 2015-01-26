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

namespace Supra\Package\Cms\Pages;

use Doctrine\ORM\EntityManager;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Locale\LocaleInterface;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\RedirectTargetPage;
use Supra\Package\Cms\Pages\DeepCopy\DoctrineCollectionFilter;
use Supra\Package\Cms\Pages\DeepCopy\DoctrineEntityFilter;
use DeepCopy\DeepCopy;
use DeepCopy\Filter\KeepFilter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyTypeMatcher;
use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\Filter\Doctrine\DoctrineEmptyCollectionFilter;

class PageManager implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Makes deep copy with persisting.
	 *
	 * @param Localization $source
	 * @param LocaleInterface $targetLocale
	 * @return Localization
	 */
	public function copyLocalization(
			Localization $source,
			LocaleInterface $targetLocale
	) {
		$deepCopy = new DeepCopy();

		$entityManager = $this->container->getDoctrine()->getManagerForClass(get_class($source));

		// Matches Localization::$master.
		// Prevents AbstractPage to be cloned.
		$deepCopy->addFilter(new KeepFilter(), new PropertyMatcher(get_class($source), 'master'));

		$this->addDeepCopyCommonFilters($deepCopy, $entityManager);

		$copiedLocalization = $deepCopy->copy($source);

		$copiedLocalization->setLocaleId($targetLocale->getId());

		$entityManager->persist($copiedLocalization);

		return $copiedLocalization;
	}

	/**
	 * @param Template $source
	 * @return Template
	 */
	public function copyTemplate(Template $source)
	{
		return $this->copyAbstractPage($source);
	}

	/**
	 * @param Page $source
	 * @return Page
	 */
	public function copyPage(Page $source)
	{
		return $this->copyAbstractPage($source);
	}

	/**
	 * @param AbstractPage $source
	 * @return AbstractPage
	 */
	public function copyAbstractPage(AbstractPage $source)
	{
		$deepCopy = new DeepCopy();

		$entityManager = $this->container->getDoctrine()->getManagerForClass(get_class($source));

		$deepCopy->addFilter(new KeepFilter(), new PropertyMatcher(AbstractPage::CN(), 'nestedSetNode'));

		$this->addDeepCopyCommonFilters($deepCopy, $entityManager);

		$copiedPage = $deepCopy->copy($source);

		$entityManager->persist($copiedPage);

		return $copiedPage;
	}

	/**
	 * @param DeepCopy $deepCopy
	 * @param EntityManager $entityManager
	 * @return DeepCopy
	 */
	private function addDeepCopyCommonFilters(DeepCopy $deepCopy, EntityManager $entityManager)
	{
		$keepFilter = new KeepFilter();
		$nullifyFilter = new SetNullFilter();

		// Matches RedirectTargetPage::$page property.
		// Keeps the $page property redirect target is referencing to.
		$deepCopy->addFilter($keepFilter, new PropertyMatcher(RedirectTargetPage::CN(), 'page'));

		// Matches PageLocalization::$template.
		// Prevents the template to be cloned.
		$deepCopy->addFilter($keepFilter, new PropertyMatcher(PageLocalization::CN(), 'template'));

		// Matches Localization::$path
		// Keeps the value since it is cloned manually (see PageLocalization::__clone());
		$deepCopy->addFilter($keepFilter, new PropertyMatcher(Localization::CN(), 'path'));

		// Matches Block::$blockProperties collection.
		// Replaces with empty collection, since block properties can be obtained via Localization::$blockProperties.
		$deepCopy->addFilter(
				new DoctrineEmptyCollectionFilter(),
				new PropertyMatcher(Block::CN(), 'blockProperties')
		);

		// Matches Localization::$lock.
		// Nullifies editing lock entity.
		$deepCopy->addFilter(
				$nullifyFilter,
				new PropertyMatcher(Localization::CN(), 'lock')
		);

		// Matches Localization::$publishedRevision.
		$deepCopy->addFilter(
			$nullifyFilter,
			new PropertyMatcher(Localization::CN(), 'publishedRevision')
		);

		// Matches Localization::$publishTime.
		$deepCopy->addFilter(
			$nullifyFilter,
			new PropertyMatcher(Localization::CN(), 'publishTime')
		);

		// Matches Entity Collection.
		// Creates Copy and persists the elements in it.
		$deepCopy->addFilter(
				new DoctrineCollectionFilter($entityManager),
				new PropertyTypeMatcher('Doctrine\Common\Collections\Collection')
		);

		// Matches any Entity.
		// Creates copy and persists it.
		$deepCopy->addFilter(
				new DoctrineEntityFilter($entityManager),
				new PropertyTypeMatcher(Entity::CN())
		);
	}
}