<?php

namespace Supra\Package\Cms\Pages\Request;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Cache\CacheGroupManager;
use Doctrine\ORM\Query;
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
	 * {@inheritdoc}
	 * @param Query $query
	 */
	protected function prepareQueryResultCache(Query $query)
	{
		// @FIXME
		return null;

 		$cacheGroupManager = new CacheGroupManager();
		$cacheGroupManager->configureQueryResultCache($query, PageController::CACHE_GROUP_NAME);
	}
	
	/**
	 * Overriden with page detection from URL
	 * @return Entity\Abstraction\Localization
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
		$pathString = $this->attributes->get('path');

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

		$this->prepareQueryResultCache($query);
		
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

					$blocks = array_merge($blocks, $placeHolder->getBlocks()->toArray());

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

			$entityManager = $this->getEntityManager();

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
		}

		return $this->auditReader;
	}
}
