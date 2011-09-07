<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Entity\PageData;
use Supra\Controller\Pages\Entity\Page;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;

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
			if ($entity instanceof PageData) {
				$this->generatePath($em, $unitOfWork, $entity);
			}

			if ($entity instanceof Page) {
				$dataCollection = $entity->getDataCollection();

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
	 * @param PageData $pageData
	 */
	private function generatePath(EntityManager $em, UnitOfWork $unitOfWork, PageData $pageData)
	{
		$page = $pageData->getMaster();
		$pathPart = $pageData->getPathPart();
		$locale = $pageData->getLocale();
		$className = get_class($pageData);
		$repo = $em->getRepository($className);
		$metaData = $em->getClassMetadata($className);
		
		if ( ! $page->isRoot()) {
			
			// Leave path empty if path part is not set yet
			if ($pathPart == '') {
				return;
			}
			
			$parentPage = $page->getParent();
			
			$path = $pathPart;
			
			// Root page has no path
			if ( ! $parentPage->isRoot()) {
				
				$parentData = $parentPage->getData($locale);
				
				if (empty($parentData)) {
					throw new Exception\RuntimeException("Parent page #{$parentPage->getId()} does not have the data for the locale {$locale} required by page {$page->getId()}");
				}
				
				$path = $parentData->getPath() . '/' . $pathPart;
			}
			
			$oldPath = $pageData->getPath();
			
			if ($path != $oldPath) {
			
				// Duplicate path validation
				$criteria = array(
					'locale' => $locale,
					'path' => $path
				);

				$duplicate = $repo->findOneBy($criteria);

				if ( ! is_null($duplicate) && ! $page->equals($duplicate)) {
					throw new Exception\DuplicatePagePathException("Page with path $path already exists");
				}
				
				// Validation passed, set the new path
				$pageData->setPath($path);
				
				$oldPathPattern = str_replace(array('_', '%'), array('\\_', '\\%'), $oldPath) . '/%';
				
				// Update all children paths
				$params = array(
					$path,
					$oldPath,
				);

				// Upadate the current page
				$dql = "UPDATE {$className} d
					SET d.path = ?0
					WHERE d.path = ?1";
				$query = $em->createQuery($dql);
				$query->execute($params);
				
				// Update children pages
				$params[] = $oldPathPattern;
				$dql = "UPDATE {$className} d
					SET d.path = CONCAT(?0, SUBSTRING(d.path, LENGTH(?1) + 1))
					WHERE d.path LIKE ?2";
				$query = $em->createQuery($dql);
				$query->execute($params);

				// Add the path to the changeset
				$unitOfWork->recomputeSingleEntityChangeSet($metaData, $pageData);
			}
		} else {
			$pageData->setPath('');
		}
	}

}
