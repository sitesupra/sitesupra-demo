<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Uri\Path;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;

/**
 * Creates the page path and checks it's uniqueness
 */
class PagePathGenerator implements EventSubscriber
{
	/**
	 * Called after page structure changes
	 */
	const postPageMove = 'postPageMove';
	
	/**
	 * Called after page duplication
	 */
	const postPageClone = 'postPageClone';
	
	/**
	 * @var EntityManager
	 */
	private $em;
	
	/**
	 * @var UnitOfWork
	 */
	private $unitOfWork;
	
	/**
	 * This class is used by path regeneration command as well
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em = null)
	{
		if ( ! is_null($em)) {
			$this->em = $em;
			$this->unitOfWork = $em->getUnitOfWork();
		}
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::onFlush, self::postPageMove, self::postPageClone);
	}
	
	/**
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();

		// Page path is not set from inserts, updates only
		foreach ($this->unitOfWork->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof Entity\PageLocalization) {
				
				$changeSet = $this->unitOfWork->getEntityChangeSet($entity);
				
				// Run only if pathPart or page activity has changed. Run for all children.
				if (isset($changeSet['pathPart']) || isset($changeSet['active']) || isset($changeSet['limitedAccess'])) {
					$master = $entity->getMaster();
					$pageLocalizationEntity = Entity\PageLocalization::CN();
					
					$dql = "SELECT l FROM $pageLocalizationEntity l JOIN l.master m
							WHERE m.left >= :left
							AND m.right <= :right
							AND l.locale = :locale
							ORDER BY m.left";
					$pageLocalizations = $this->em->createQuery($dql)
							->setParameters(array(
								'left' => $master->getLeftValue(),
								'right' => $master->getRightValue(),
								'locale' => $entity->getLocale(),
							))
							->getResult();
					
					foreach ($pageLocalizations as $pageLocalization) {
						$this->generatePath($pageLocalization);
					}
				}
			}

			// this should be covered by the move trigger
			if ($entity instanceof Entity\Page) {
				// Run for all children, every locale
				$this->pageChange($entity);
			}
		}
	}
	
	/**
	 * This is called for public schema when structure is changed in draft schema
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postPageMove(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();

		if ($entity instanceof Entity\Page) {
			$this->pageChange($entity);
		}
	}
	
	/**
	 * Finds Localizations without generated path, but with defined pathPart
	 * (happens when page is cloned, also is usable on page create action), and 
	 * generates unique path.
	 * 
	 * @param LifecycleEventArgs $eventArgs 
	 */
	public function postPageClone(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();
		
		if ($entity instanceof Entity\PageLocalization) {
			$this->generatePath($entity, true);
		} else 	if ($entity instanceof Entity\Page) {
			// Run for all children, every locale
			$this->pageChange($entity, true);
		}
	}
	
	/**
	 * Called when page structure is changed
	 * @param Entity\Page $master
	 */
	private function pageChange(Entity\Page $master)
	{
		// Run for all children
		$pageLocalizationEntity = Entity\PageLocalization::CN();

		$dql = "SELECT l FROM $pageLocalizationEntity l JOIN l.master m
				WHERE m.left >= :left
				AND m.right <= :right
				ORDER BY l.locale, m.left";

		$pageLocalizations = $this->em->createQuery($dql)
				->setParameters(array(
					'left' => $master->getLeftValue(),
					'right' => $master->getRightValue(),
				))
				->getResult();

		foreach ($pageLocalizations as $pageLocalization) {
			$this->generatePath($pageLocalization, true);
		}
	}
	
	/**
	 * Generates new full path and validates its uniqueness
	 * @param Entity\PageLocalization $pageData
	 */
	public function generatePath(Entity\PageLocalization $pageData, $force = false)
	{
		$page = $pageData->getMaster();
		
		$oldPath = $pageData->getPath();
		$changes = false;
		
		$oldPathEntity = $pageData->getPathEntity();
		
		list($newPath, $active, $limited) = $this->findPagePath($pageData);
		
		if ( ! $page->isRoot()) {
			
			if ( ! Path::compare($oldPath, $newPath)) {

				$suffix = null;
				
				// Check duplicates only if path is not null
				if ( ! is_null($newPath)) {

					$i = 2;
					$e = null;
					$pathPart = $pageData->getPathPart();

					do {
						try {
							$this->checkForDuplicates($pageData, $newPath);
							$pathValid = true;
						} catch (DuplicatePagePathException $e) {

							if ($force) {

								// loop stoper
								if ($i > 101) {
									throw new Exception\RuntimeException("Couldn't find unique path for new page", null, $e);
								}
								
								// Will try adding unique suffix after 100 iterations
								if ($i > 100) {
									$suffix = uniqid();
								} else {
									$suffix = $i;
								}
								$pageData->setPathPart($pathPart . '-' . $suffix);
								list($newPath, $active, $limited) = $this->findPagePath($pageData);

								$i++;
							}
						}
					}  while ($force && ! $pathValid);
					
					if ($e instanceof DuplicatePagePathException && ! $pathValid) {
						throw $e;
					}
				}

				// Validation passed, set the new path
				$pageData->setPath($newPath, $active, $limited);
				if ( ! is_null($suffix)) {
					$pageData->setTitle($pageData->getTitle() . " ($suffix)");
				}
				$changes = true;
			}
		} else {
			$newPath = new Path('');
			
			// Root page
			if ( ! $newPath->equals($oldPath)) {
				$changes = true;
				$pageData->setPath($newPath);
			}
		}
		
		if ($oldPathEntity->isLimited() !== $limited 
				|| $oldPathEntity->isActive() !== $active) {
				
			$pageData->setPath($newPath, $active, $limited);
			$changes = true;
		}
		
		if ($changes) {
			$pathEntity = $pageData->getPathEntity();
			$pathMetaData = $this->em->getClassMetadata($pathEntity->CN());
			$localizationMetaData = $this->em->getClassMetadata($pageData->CN());
			
			if ($this->unitOfWork->getEntityState($pathEntity) === UnitOfWork::STATE_NEW) {
				$this->em->persist($pathEntity);
			}
			
			/*
			 * Add the path changes to the changeset, must call different 
			 * methods depending on is the entity inside the unit of work
			 * changeset
			 */
			if ($this->unitOfWork->getEntityChangeSet($pathEntity)) {
				$this->unitOfWork->recomputeSingleEntityChangeSet($pathMetaData, $pathEntity);
			} else {
				$this->unitOfWork->computeChangeSet($pathMetaData, $pathEntity);
			}
			
			if ($this->unitOfWork->getEntityChangeSet($pageData)) {
				$this->unitOfWork->recomputeSingleEntityChangeSet($localizationMetaData, $pageData);
			} else {
				$this->unitOfWork->computeChangeSet($localizationMetaData, $pageData);
			}
		}
	}
	
	/**
	 * Throws exception if page duplicate is found
	 * @param Entity\PageLocalization $pageData
	 * @param Path $newPath
	 */
	protected function checkForDuplicates(Entity\PageLocalization $pageData, Path $newPath)
	{
		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();
		$repo = $this->em->getRepository(Entity\PageLocalizationPath::CN());
		
		$newPathString = $newPath->getFullPath();

		// Duplicate path validation
		$criteria = array(
			'locale' => $locale,
			'path' => $newPath
		);

		$duplicate = $repo->findOneBy($criteria);
		/* @var $duplicate Entity\PageLocalizationPath */

		if ( ! is_null($duplicate) && ! $pageData->getPathEntity()->equals($duplicate)) {
			throw new Exception\DuplicatePagePathException("Page with path $newPathString already exists", $pageData);
		}
	}
	
	/**
	 * Loads page path
	 * @param Entity\PageLocalization $pageData
	 * @return Path
	 */
	protected function findPagePath(Entity\PageLocalization $pageData)
	{
		$active = true;
		$limited = false;
		$path = new Path();
		
		// Inactive page children have no path
		if ( ! $pageData->isActive()) {
			$active = false;
		}
		
		if ($pageData->isLimitedAccessPage()) {
			$limited = true;
		}
		
		$pathPart = $pageData->getPathPart();
		
		if (is_null($pathPart)) {
			return array(null, null, null);
		}
		
		$path->prependString($pathPart);
		
		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();
		
		$parentPage = $page->getParent();

		if (is_null($parentPage)) {
			return array($path, $active, $limited);
		}
		
		$parentLocalization = $parentPage->getLocalization($locale);
		
		// No parent page localization
		if (is_null($parentLocalization)) {
			return array(null, null, null);
		}
		
		// Page application feature to generate base path for pages
		if ($parentPage instanceof Entity\ApplicationPage) {
			$applicationId = $parentPage->getApplicationId();

			$application = PageApplicationCollection::getInstance()
					->createApplication($parentLocalization, $this->em);

			if (empty($application)) {
				throw new Exception\PagePathException("Application '$applicationId' is not found", $pageData);
			}

			$pathBasePart = $application->generatePath($pageData);
			$path->prepend($pathBasePart);
		}
		
		// Search nearest page parent
		while ( ! $parentLocalization instanceof Entity\PageLocalization) {
			$parentPage = $parentPage->getParent();
			
			if (is_null($parentPage)) {
				return array($pageData->getPathPart(), $active, $limited);
			}
			
			$parentLocalization = $parentPage->getLocalization($locale);
			
			// No parent page localization
			if (is_null($parentLocalization)) {
				return array(null, null, null);
			}
		}

		// Is checked further
//		if ( ! $parentLocalization->isActive()) {
//			$active = false;
//		}
		
		// Assume that path is already regenerated for the parent
		$parentPath = $parentLocalization->getPathEntity()->getPath();
		$parentActive = $parentLocalization->getPathEntity()->isActive();
		$parentLimited = $parentLocalization->getPathEntity()->isLimited();

		if ( ! $parentActive) {
			$active = false;
		}
		
		if ($parentLimited) {
			$limited = true;
		}

		if (is_null($parentPath)) {
			return array(null, null, null);
		}
		
		$path->prepend($parentPath);

		return array($path, $active, $limited);
	}
		
}
