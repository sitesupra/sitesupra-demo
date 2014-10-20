<?php

namespace Supra\Package\Cms\Pages\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Core\NestedSet\Event\NestedSetEventArgs;
use Supra\Core\NestedSet\Event\NestedSetEvents;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\PageLocalizationPath;
use Supra\Package\Cms\Uri\Path;
use Supra\Package\Cms\Pages\Exception;
use Supra\Package\Cms\Pages\Exception\DuplicatePagePathException;
use Supra\Package\Cms\Pages\Application\PageApplicationManager;

/**
 * Creates the page path and checks it's uniqueness
 */
class PagePathGeneratorListener implements EventSubscriber, ContainerAware
{
//	/**
//	 * Called after page structure changes
//	 */
//	const postPageMove = 'postPageMove';

	/**
	 * Called after page duplication
	 */
	const postPageClone = 'postPageClone';

	/**
	 * @var ContainerInterface
	 */
	protected $container;

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
		return array(
			Events::onFlush,
			NestedSetEvents::nestedSetPostMove,
			self::postPageClone
		);
	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();

		// New localization creation, could contain children already, need to recurse into children
		foreach ($this->unitOfWork->getScheduledEntityInsertions() as $entity) {
			if ($entity instanceof PageLocalization) {
				$this->pageLocalizationChange($entity);
			}
		}

		// Page path is not set from inserts, updates only
		foreach ($this->unitOfWork->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof PageLocalization) {

				// Removed because news application needs to regenerate the path on creation time change
//				$changeSet = $this->unitOfWork->getEntityChangeSet($entity);
//
//				// Run only if pathPart or page activity has changed. Run for all children.
//				if (isset($changeSet['pathPart']) || isset($changeSet['active']) || isset($changeSet['limitedAccess']) || isset($changeSet['visibleInSitemap'])) {
					$this->pageLocalizationChange($entity);
//				}
			}

			// this should be covered by the move trigger
			if ($entity instanceof Page) {
				// Run for all children, every locale
				$this->pageChange($entity);
			}
		}
	}

	/**
	 * This is called for public schema when structure is changed in draft schema
	 * @param NestedSetEventArgs $eventArgs
	 */
	public function nestedSetPostMove(NestedSetEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$this->em = $eventArgs->getEntityManager();
		$this->unitOfWork = $this->em->getUnitOfWork();

		if ($entity instanceof Page) {
			$changedLocalizations = $this->pageChange($entity, true);

			foreach ($changedLocalizations as $changedLocalization) {
				$this->em->flush($changedLocalization);
			}
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

		if ($entity instanceof PageLocalization) {
			$this->generatePath($entity, true);
		} else if ($entity instanceof Page) {
			// Run for all children, every locale
			$this->pageChange($entity, true);
		}
	}

	/**
	 * Called when page structure is changed
	 * @param Page $master
	 * @return array of changed localizations
	 */
	private function pageChange(Page $master, $force = false)
	{
		// Run for all children
		$pageLocalizationEntity = PageLocalization::CN();

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

		$changedLocalizations = array();

		foreach ($pageLocalizations as $pageLocalization) {
			$changedLocalization = $this->generatePath($pageLocalization, $force);
			if ( ! is_null($changedLocalization)) {
				$changedLocalizations[] = $changedLocalization;
			}
		}

		return $changedLocalizations;
	}

	/**
	 * Recurse path regeneration for the localization and all its descendants
	 * @param PageLocalization $localization
	 * @param boolean $force
	 */
	private function pageLocalizationChange(PageLocalization $localization, $force = false)
	{
		$master = $localization->getMaster();
		$pageLocalizationEntity = PageLocalization::CN();

		$dql = "SELECT l FROM $pageLocalizationEntity l JOIN l.master m
				WHERE m.left >= :left
				AND m.right <= :right
				AND l.locale = :locale
				ORDER BY m.left";
		$pageLocalizations = $this->em->createQuery($dql)
				->setParameters(array(
					'left' => $master->getLeftValue(),
					'right' => $master->getRightValue(),
					'locale' => $localization->getLocale(),
				))
				->getResult();

		foreach ($pageLocalizations as $pageLocalization) {
			$this->generatePath($pageLocalization);
		}
	}

	/**
	 * Generates new full path and validates its uniqueness
	 * @param PageLocalization $pageData
	 * @return PageLocalization if changes were made
	 */
	public function generatePath(PageLocalization $pageData, $force = false)
	{
		$page = $pageData->getMaster();

		$oldPath = $pageData->getPath();
		$changes = false;

		$oldPathEntity = $pageData->getPathEntity();

		list($newPath, $active, $limited, $inSitemap) = $this->findPagePath($pageData);

		if ( ! $page->isRoot()) {

			if ( ! Path::compare($oldPath, $newPath)) {

				$suffix = null;

				// Check duplicates only if path is not null
				if ( ! is_null($newPath)) {

					// Additional check for path length
					$pathString = $newPath->getPath();
					if (mb_strlen($pathString) > 255) {
						throw new Exception\RuntimeException('Overall path length shouldn\'t be more than 255 symbols');
					}

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
								list($newPath, $active, $limited, $inSitemap) = $this->findPagePath($pageData);

								$i ++;
							}
						}
					} while ($force && ! $pathValid);

					if ($e instanceof DuplicatePagePathException && ! $pathValid) {
						throw $e;
					}
				}

				// Validation passed, set the new path
				$pageData->setPath($newPath, $active, $limited, $inSitemap);
				if ( ! is_null($suffix)) {
					$pageData->setTitle($pageData->getTitle() . " ($suffix)");
				}
				$changes = true;
			}
		} elseif ($page->getLeftValue() == 1) {
			$newPath = new Path('');

			// Root page
			if ( ! $newPath->equals($oldPath)) {
				$changes = true;
				$pageData->setPath($newPath, $active, $limited, $inSitemap);
			}
			// Another root page...
		} else {
			$newPath = null;
			$active = false;
			$pageData->setPath($newPath, $active, $limited, $inSitemap);
		}

		if (
				$oldPathEntity->isLimited() !== $limited
				|| $oldPathEntity->isActive() !== $active
				|| $oldPathEntity->isVisibleInSitemap() != $inSitemap
		) {

			$pageData->setPath($newPath, $active, $limited, $inSitemap);
			$changes = true;
		}

		if ($changes) {
			$pathEntity = $pageData->getPathEntity();
			$pathMetaData = $this->em->getClassMetadata($pathEntity->CN());
			$localizationMetaData = $this->em->getClassMetadata($pageData->CN());

			if ($this->unitOfWork->getEntityState($pathEntity, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW) {
				$this->em->persist($pathEntity);
//			} elseif ($this->unitOfWork->getEntityState($pathEntity) === UnitOfWork::STATE_DETACHED) {
//				$pathEntity = $this->em->merge($pathEntity);
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

			return $pageData;
		}
	}

	/**
	 * Throws exception if page duplicate is found
	 * @param PageLocalization $pageData
	 * @param Path $newPath
	 */
	protected function checkForDuplicates(PageLocalization $pageData, Path $newPath)
	{
		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();
		$repo = $this->em->getRepository(PageLocalizationPath::CN());

		$newPathString = $newPath->getFullPath();

		// Duplicate path validation
		$criteria = array(
			'locale' => $locale,
			'path' => $newPath
		);

		$duplicate = $repo->findOneBy($criteria);
		/* @var $duplicate PageLocalizationPath */

		if ( ! is_null($duplicate) && ! $pageData->getPathEntity()->equals($duplicate)) {
			throw new DuplicatePagePathException(
					sprintf('Page with path [%s] already exists.', $newPathString),
					$pageData
			);
		}
	}

	/**
	 * Loads page path
	 * @param PageLocalization $pageData
	 * @return Path
	 */
	protected function findPagePath(PageLocalization $pageData)
	{
		$active = true;
		$limited = false;
		$inSitemap = true;

		$path = new Path();

		// Inactive page children have no path
		if ( ! $pageData->isActive()) {
			$active = false;
		}

		if ( ! $pageData->isVisibleInSitemap()) {
			$inSitemap = false;
		}

		$pathPart = $pageData->getPathPart();

		if (is_null($pathPart)) {
			return array(null, false, false, false);
		}

		$path->prependString($pathPart);

		$page = $pageData->getMaster();
		$locale = $pageData->getLocale();

		$parentPage = $page->getParent();

		if (is_null($parentPage)) {
			return array($path, $active, $limited, $inSitemap);
		}

		$parentLocalization = $parentPage->getLocalization($locale);

		// No parent page localization
		if (is_null($parentLocalization)) {
			return array(null, false, false, false);
		}

		// Page application feature to generate base path for pages
		if ($parentPage instanceof ApplicationPage) {
			$applicationId = $parentPage->getApplicationId();

			$pageApplicationManager = $this->container['cms.pages.page_application_manager'];
			/* @var $pageApplicationManager PageApplicationManager */

			if (! $pageApplicationManager->hasApplication($applicationId)) {
				throw new Exception\PagePathException(
						sprintf("Failed to generate path because, page application [%s] not found.", $applicationId),
						$pageData
				);
			}

			$application = $pageApplicationManager->createApplicationFor($pageData, $this->em);

			$pathBasePart = $application->generatePath($pageData);
			$path->prepend($pathBasePart);
		}

		// Search nearest page parent
		while ( ! $parentLocalization instanceof PageLocalization) {
			$parentPage = $parentPage->getParent();

			if (is_null($parentPage)) {
				return array($pageData->getPathPart(), $active, $limited, $inSitemap);
			}

			$parentLocalization = $parentPage->getLocalization($locale);

			// No parent page localization
			if (is_null($parentLocalization)) {
				return array(null, false, false, false);
			}
		}

		// Is checked further
//		if ( ! $parentLocalization->isActive()) {
//			$active = false;
//		}
		// Assume that path is already regenerated for the parent
		$parentPath = $parentLocalization->getPathEntity()->getPath();
		$parentActive = $parentLocalization->getPathEntity()->isActive();
		$parentInSitemap = $parentLocalization->getPathEntity()->isVisibleInSitemap();

		if ( ! $parentActive) {
			$active = false;
		}

		if ( ! $parentInSitemap) {
			$inSitemap = false;
		}

		if (is_null($parentPath)) {
			return array(null, false, false, false);
		}

		$path->prepend($parentPath);

		return array($path, $active, $limited, $inSitemap);
	}

}
