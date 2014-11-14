<?php

namespace Supra\Package\Cms\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;

/**
 * Creates image size when ImageReferencedElement is created in db
 */
class ImageSizeCreatorListener implements EventSubscriber, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents()
	{
		return array(Events::onFlush);
	}

	/**
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$unitOfWork = $em->getUnitOfWork();

		// Insertions of image references
		foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
			if ($entity instanceof ImageReferencedElement) {
				$this->createImageSize($entity, $em);
			}
		}

		// Updates of image references
		foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof ImageReferencedElement) {
				$this->createImageSize($entity, $em);
			}
		}
	}

	/**
	 * Internal method to create the size
	 * @param ImageReferencedElement $entity
	 * @param EntityManager $em
	 */
	protected function createImageSize(ImageReferencedElement $entity, EntityManager $em)
	{
		$imageId = $entity->getImageId();

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		$image = $fileStorage->findImage($imageId);

		if ($image === null) {
			$this->container->getLogger()->warn(
					"Image [{$imageId}] was not found inside the file storage." .
					" Maybe another file storage must be configured for the image size creator listener?"
			);

			return false;
		}

		$width = $entity->getWidth();
		$height = $entity->getHeight();

		// No dimensions
		if ($width > 0 && $height > 0 || $entity->isCropped()) {

			if ($entity->isCropped()) {
				$sizeName = $fileStorage->createImageVariant($image, $width, $height, $entity->getCropLeft(), $entity->getCropTop(), $entity->getCropWidth(), $entity->getCropHeight());
			} else {
				$sizeName = $fileStorage->createResizedImage($image, $width, $height);
			}
			$entity->setSizeName($sizeName);

			// Maybe could update to real width/height inside image metadata?
//		$size = $image->getImageSize($sizeName);
//		$entity->setWidth($size->getWidth());
//		$entity->setHeight($size->getHeight());
			// Recalculate the changeset because of changed size name field
			$class = $em->getClassMetadata(ImageReferencedElement::CN());
			$unitOfWork = $em->getUnitOfWork();
			$unitOfWork->recomputeSingleEntityChangeSet($class, $entity);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

}
