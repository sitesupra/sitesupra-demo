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

namespace Supra\Package\Cms\Pages\Request;

use Doctrine\ORM\Query;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Pages\Set\PageSet;
use Supra\Package\Cms\Pages\Set\PlaceHolderSet;
use Supra\Package\Cms\Pages\Set\BlockSet;
use Supra\Package\Cms\Pages\Set\BlockPropertySet;
use Supra\Package\Cms\Entity\TemplatePlaceHolder;

/**
 * Page controller request object on view method
 */
class PageRequestView extends PageRequest
{
	protected $pageSet;
	protected $blockPropertySet;
	protected $placeHolderSet;
	protected $blockSet;

	private $auditReader;

	/**
	 * Overriden with page detection from URL
	 * @return Localization
	 */
	public function getLocalization()
	{
		$data = parent::getLocalization();
		
		if (empty($data)) {
			$data = $this->detectRequestPageLocalization();
			
			$this->setLocalization($data);
		}
	
		return $data;
	}
	
	/**
	 * @return PageLocalization
	 * @throws ResourceNotFoundException if page not found or is inactive
	 */
	protected function detectRequestPageLocalization()
	{
		$pathString = trim($this->attributes->get('path'), '/');

		$entityManager = $this->getEntityManager();

		$queryString = sprintf('SELECT l FROM %s l JOIN l.path p WHERE p.path = :path
			AND p.active = true AND p.locale = :locale AND l.publishedRevision IS NOT NULL',
				PageLocalization::CN()
		);
		
		$query = $entityManager->createQuery($queryString)
				->setParameters(array(
					'locale' => $this->getLocale(),
					'path' => $pathString
				));

		$pageLocalization = $query->getOneOrNullResult();
		/* @var $pageData PageLocalization */

		if ($pageLocalization === null) {
			throw new ResourceNotFoundException(sprintf('
					No page found by path [%s] in pages controller.',
					$pathString
			));
		}
		
		if (! $pageLocalization->isActive()) {
			throw new ResourceNotFoundException(sprintf(
					'Page found by path [%s] in pages controller is inactive.'
			));
		}

		$localeManager = $this->container->getLocaleManager();

		if (! $localeManager->isActive($pageLocalization->getLocaleId())) {
			throw new ResourceNotFoundException(sprintf(
					'Page found by path [%s] in pages controller belongs to inactive locale [%s].',
					$pathString,
					$this->getLocale()
			));
		}

		return $pageLocalization;
	}

	/**
	 * @return PlaceHolderSet
	 */
	public function getPlaceHolderSet()
	{
		if ($this->placeHolderSet === null) {

			$localeId = $this->getLocale();

			$knownNames = $this->getLayoutPlaceHolderNames();

			$pageSet = $this->getPageSet();

			$placeHolders = array();

			foreach ($pageSet as $page) {
				/* @var $page \Supra\Package\Cms\Entity\Abstraction\AbstractPage */
				$localization = $page->getLocalization($localeId);

				foreach ($localization->getPlaceHolders() as $placeHolder) {

					$name = $placeHolder->getName();

					if (! in_array($name, $knownNames)) {
						continue;
					}

					if (! isset($placeHolders[$name])) {
						$placeHolders[$name] = $placeHolder;
						continue;
					}

					if ($placeHolders[$name]->isLocked()) {
						continue;
					}

					$placeHolders[$name] = $placeHolder;
				}
			}

			$this->placeHolderSet = new PlaceHolderSet($this->getLocalization());

			$this->placeHolderSet->appendArray($placeHolders);
		}

		return $this->placeHolderSet;
	}

	/**
	 * @return BlockSet
	 */
	public function getBlockSet()
	{
		if ($this->blockSet === null) {

			$blocks = array();

			$localeId = $this->getLocale();

			$knownNames = $this->getLayoutPlaceHolderNames();
			$visitedNames = array();

			foreach ($this->getPageSet() as $page) {
				/* @var $page \Supra\Package\Cms\Entity\Abstraction\AbstractPage */
				$localization = $page->getLocalization($localeId);

				foreach ($localization->getPlaceHolders() as $placeHolder) {

					$name = $placeHolder->getName();

					if (! in_array($name, $knownNames)
							|| in_array($name, $visitedNames)) {
						continue;
					}

					if ($placeHolder instanceof TemplatePlaceHolder
							&& ! $placeHolder->isLocked()) {

						foreach ($placeHolder->getBlocks() as $block) {
							if ($block->isLocked()) {
								$blocks[] = $block;
							}
						}

						continue;
					}

					$placeHolderBlocks = $placeHolder->getBlocks()->toArray();

					uasort($placeHolderBlocks, function(Block $a, Block $b) {
						return $a->getPosition() === $b->getPosition() ? 0
							: (($a->getPosition() < $b->getPosition()) ? -1 : 1);
					});

					$blocks = array_merge($blocks, $placeHolderBlocks);

					$visitedNames[] = $name;
				}
			}

			$this->blockSet = new BlockSet($blocks);
		}

		return $this->blockSet;
	}

	/**
	 * @return BlockPropertySet
	 */
	public function getBlockPropertySet()
	{
		if ($this->blockPropertySet === null) {

			$properties = array();

			$localeId = $this->getLocale();

			foreach ($this->getPageSet() as $page) {
				/* @var $page \Supra\Package\Cms\Entity\Abstraction\AbstractPage */
				$localization = $page->getLocalization($localeId);

				if ($localization) {
					$properties = array_merge($properties, $localization->getBlockProperties()->toArray());
				}
			}

			$this->blockPropertySet = new BlockPropertySet($properties);
		}

		return $this->blockPropertySet;
	}

	/**
	 * @return PageSet
	 */
	public function getPageSet()
	{
		if ($this->pageSet === null) {

			$auditReader = $this->getAuditReader();
			$localization = $this->getLocalization();

//			$auditReader->getCache()->setSuffix($localization->getPublishedRevision());

			$entityManager = $this->getEntityManager();

			$pages = array();

			foreach (parent::getPageSet() as $page) {

				$classMetadata = $entityManager->getClassMetadata($page::CN());

				$pages[] = $auditReader->find(
						$classMetadata->name,
						$page->getId(),
						$localization->getPublishedRevision()
				);
			}

			$this->pageSet = new PageSet($pages);
		}

		return $this->pageSet;
	}

	/**
	 * @return \SimpleThings\EntityAudit\AuditReader
	 */
	protected function getAuditReader()
	{
		if ($this->auditReader === null) {
			$this->auditReader = $this->container['entity_audit.manager']
				->createAuditReader($this->getEntityManager());
//			$this->auditReader->setCache($this->container['cache.frontend']);
		}

		return $this->auditReader;
	}
}
