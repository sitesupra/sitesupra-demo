<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Uri\Path;

/**
 * Creates the page path and checks it's uniqueness
 */
class PagePathGenerator
{
	/**
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$unitOfWork = $em->getUnitOfWork();

		// Page path is not set from inserts, updates only
		foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof Entity\PageLocalization) {
				$this->generatePath($em, $unitOfWork, $entity);
			}

			if ($entity instanceof Entity\Page) {
				$dataCollection = $entity->getLocalizations();

				foreach ($dataCollection as $dataEntity) {
					$this->generatePath($em, $unitOfWork, $dataEntity);
				}
			}
		}
	}
	
	/**
	 * Generates new full path and validates its uniqueness
	 * @param EntityManager $em
	 * @param UnitOfWork $unitOfWork
	 * @param Entity\PageLocalization $pageData
	 */
	private function generatePath(EntityManager $em, UnitOfWork $unitOfWork, Entity\PageLocalization $pageData)
	{
		$page = $pageData->getMaster();
		$pathPart = $pageData->getPathPart();
		$locale = $pageData->getLocale();
		$className = get_class($pageData);
		$repo = $em->getRepository($className);
		$metaData = $em->getClassMetadata($className);
		
		$pathPart = new Path($pathPart);
		$newPath = new Path('');
		
		if ( ! $page->isRoot()) {
			
			$parentPage = $page->getParent();
			
			if ($parentPage instanceof Entity\ApplicationPage) {
				$applicationId = $parentPage->getApplicationId();
				
				$application = PageApplicationCollection::getInstance()
						->createApplication($applicationId);
				
				if (empty($application)) {
					throw new Exception\PagePathException("Application '$applicationId' is not found", $pageData);
				}
				
				$pathBasePart = $application->generatePath($pageData);
				$pathPart = $pathBasePart->append($pathPart);
			}
			
			// Leave path empty if path part is not set yet
			if ($pathPart->isEmpty()) {
				return;
			}
			
			$parentPageData = $parentPage->getLocalization($locale);
			
			if (empty($parentPageData)) {
				throw new Exception\RuntimeException("Parent page localization is not found for the locale {$locale} required by page {$page->getId()}");
			}
			
			$parentPath = $parentPageData->getPath();
			$newPath->append($parentPath);
			$newPath->append($pathPart);
			
			$oldPath = $pageData->getPath();
			
			if ( ! $newPath->equals($oldPath)) {
			
				$newPathString = $newPath->getFullPath();
				
				// Duplicate path validation
				$criteria = array(
					'locale' => $locale,
					'path' => $newPathString
				);

				$duplicate = $repo->findOneBy($criteria);

				if ( ! is_null($duplicate) && ! $page->equals($duplicate)) {
					throw new Exception\DuplicatePagePathException("Page with path $newPathString already exists", $pageData);
				}
				
				// Validation passed, set the new path
				$pageData->setPath($newPath);
				
				// Run updates only if path was set before
				if ($oldPath !== null) {
				
					$oldPathString = $oldPath->getFullPath();
					$oldPathPattern = str_replace(array('_', '%'), array('\\_', '\\%'), $oldPathString) . '/%';

					// Update all children paths
					$params = array(
						0 => $newPathString,
						1 => $oldPathString,
						2 => $locale
					);

					// Upadate the current page
					$dql = "UPDATE {$className} d
						SET d.path = ?0
						WHERE d.path = ?1
						AND d.locale = ?2";
					$query = $em->createQuery($dql);
					$query->execute($params);

					// Update children pages
					$params[2] = $oldPathPattern;
					$dql = "UPDATE {$className} d
						SET d.path = CONCAT(?0, SUBSTRING(d.path, LENGTH(?1) + 1))
						WHERE d.path LIKE ?2";
					$query = $em->createQuery($dql);
					$query->execute($params);
				}

				// Add the path to the changeset
				$unitOfWork->recomputeSingleEntityChangeSet($metaData, $pageData);
			}
		} else {
			$pageData->setPath($newPath);
		}
	}

}
