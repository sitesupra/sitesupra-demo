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
		$pathPartString = $pageData->getPathPart();
		$locale = $pageData->getLocale();
		$className = get_class($pageData);
		$metaData = $em->getClassMetadata($className);
		
		$oldPath = $pageData->getPath();
		
		if ( ! $page->isRoot()) {
			
			// Currently it may allow setting empty path to any page
			$hasPath = ($pathPartString != '');
			
			$newPath = null;
			$newParentPath = $this->findPageBasePath($pageData);
			
			// Generate new path if page has it's own path
			if ($hasPath) {
				$newPath = new Path('');
				$newPath->append($newParentPath);
				$pathPart = new Path($pathPartString);
				$newPath->append($pathPart);
			}
			
			$oldParentPath = $pageData->getParentPath();
			$anyPathChanged = false;
			
			// Old and new parent paths shouldn't differ in fact..
			if ( ! Path::compare($oldPath, $newPath) || ! Path::compare($oldParentPath, $newParentPath)) {

				// Check duplicates only if path is not null
				if ( ! is_null($newPath)) {
					$this->checkForDuplicates($em, $pageData, $newPath);
				}

				// Validation passed, set the new path
				$pageData->setPath($newPath);
				$pageData->setParentPath($newParentPath);
				
				// Will use path or parent path, whatever is not empty
				$oldBasePath = $oldPath ? $oldPath : $oldParentPath;
				$newBasePath = $newPath ? $newPath : $newParentPath;
				
				// Run updates only if path was set before
				if ($oldBasePath !== null) {
					$this->updateDependantPages($em, $pageData, $oldBasePath, $newBasePath);
				}

				/*
				 * Add the path changes to the changeset, must call different 
				 * methods depending on is the entity inside the unit of work
				 * changeset
				 */
				if ($unitOfWork->getEntityChangeSet($pageData)) {
					$unitOfWork->recomputeSingleEntityChangeSet($metaData, $pageData);
				} else {
					$unitOfWork->computeChangeSet($metaData, $pageData);
				}
			}
		} else {
			$newPath = new Path('');
			
			// Root page
			if ( ! $newPath->equals($oldPath)) {
				$pageData->setPath($newPath);
				$pageData->setParentPath($newPath);
			}
		}
	}
	
	/**
	 * Throws exception if page duplicate is found
	 * @param EntityManager $em
	 * @param Entity\PageLocalization $pageData
	 * @param Path $newPath
	 */
	protected function checkForDuplicates(EntityManager $em, Entity\PageLocalization $pageData, Path $newPath)
	{
		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();
		$className = get_class($pageData);
		$repo = $em->getRepository($className);
		
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
	}
	
	/**
	 * Runs database updates when page path changes on child pages
	 * @param EntityManager $em
	 * @param Entity\PageLocalization $pageData
	 * @param Path $oldBasePath
	 * @param Path $newBasePath 
	 */
	protected function updateDependantPages(EntityManager $em, Entity\PageLocalization $pageData, Path $oldBasePath, Path $newBasePath)
	{
		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();
		$className = get_class($pageData);
		$masterClassName = get_class($page);
		
		$newPathString = $newBasePath->getFullPath();
		$oldPathString = $oldBasePath->getFullPath();
		$oldPathPattern = str_replace(array('_', '%'), array('\\_', '\\%'), $oldPathString) . '/%';

		// For limiting only child pages by structure NOT path
		$left = $page->getLeftValue();
		$right = $page->getRightValue();
		
		$params = array(
			0 => $newPathString,
			1 => $oldPathString,
			2 => $locale,
			3 => $oldPathPattern,
			4 => $left,
			5 => $right,
		);
		
		// Update children pages and self, path field
		$dql = "UPDATE {$className} d
			SET d.path = CONCAT(?0, SUBSTRING(d.path, LENGTH(?1) + 1))
			WHERE
				(d.path = ?1 OR d.path LIKE ?3)
				AND d.locale = ?2
				AND d.master IN 
					(SELECT m FROM {$masterClassName} m WHERE m.left >= ?4 AND m.right <= ?5)";
		
		$query = $em->createQuery($dql);
		$query->execute($params);
		
		// Update children pages, parent path field
		$dql = "UPDATE {$className} d
			SET d.parentPath = CONCAT(?0, SUBSTRING(d.parentPath, LENGTH(?1) + 1))
			WHERE 
				(d.parentPath = ?1 OR d.parentPath LIKE ?3)
				AND d.locale = ?2
				AND d.master IN 
					(SELECT m FROM {$masterClassName} m WHERE m.left > ?4 AND m.right < ?5)";
		
		$query = $em->createQuery($dql);
		$query->execute($params);
	}

	/**
	 * Loads page base path
	 * @param Entity\PageLocalization $pageData
	 * @return Path
	 */
	protected function findPageBasePath(Entity\PageLocalization $pageData)
	{
		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();
		
		$parentPage = $page->getParent();
		$parentHasPath = true;
		$parentPageData = $parentPage->getLocalization($locale);
		
		// Unexpected issue
		if (empty($parentPageData)) {
			throw new Exception\RuntimeException("Parent page localization is not found for the locale {$locale} required by page {$page->getId()}");
		}
		
		// Get parent path
		$newParentPath = $parentPageData->getPath();
		if (is_null($newParentPath)) {
			$newParentPath = $parentPageData->getParentPath();
		}
		
		// Forget the reference by cloning
		$newParentPath = clone($newParentPath);

		// Page application feature to generate base path for pages
		if ($parentPage instanceof Entity\ApplicationPage) {
			$applicationId = $parentPage->getApplicationId();

			$application = PageApplicationCollection::getInstance()
					->createApplication($applicationId);

			if (empty($application)) {
				throw new Exception\PagePathException("Application '$applicationId' is not found", $pageData);
			}

			$pathBasePart = $application->generatePath($pageData);
			$newParentPath->append($pathBasePart);
		}

		return $newParentPath;
	}
	
}
