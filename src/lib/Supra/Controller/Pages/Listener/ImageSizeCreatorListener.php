<?php

namespace Supra\Controller\Pages\Listener;

use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\FileStorage;
use Supra\FileStorage\Entity\Image;
use Supra\Log\Writer\WriterAbstraction;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

/**
 * Creates image size when ImageReferencedElement is created in db
 */
class ImageSizeCreatorListener implements EventSubscriber
{
	/**
	 * @var WriterAbstraction
	 */
	private $log;
	
	/**
	 * @var FileStorage
	 */
	private $fileStorage;

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::onFlush);
	}
	
	/**
	 * Bind logger
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @return FileStorage
	 */
	protected function getFileStorage()
	{
		if ( ! $this->fileStorage instanceof FileStorage) {
			$this->fileStorage = ObjectRepository::getFileStorage($this);
		}
		
		return $this->fileStorage;
	}
	
	/**
	 * Override the file storage got from the supra object repository
	 * @param FileStorage $fileStorage
	 */
	public function setFileStorage(FileStorage $fileStorage)
	{
		$this->fileStorage = $fileStorage;
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
		$width = $entity->getWidth();
		$height = $entity->getHeight();

		$fileStorage = $this->getFileStorage();

		$fsEm = $fileStorage->getDoctrineEntityManager();
		$image = $fsEm->find(Image::CN(), $imageId);

		if ( ! $image instanceof Image) {
			$this->log->warn("Image by ID $imageId was not found inside the file storage specified." . 
					" Maybe another file storage must be configured for the image size creator listener?");

			return;
		}

		$sizeName = $fileStorage->createResizedImage($image, $width, $height);
		$entity->setSizeName($sizeName);

		// Recalculate the changeset because of changed size name field
		$class = $em->getClassMetadata(ImageReferencedElement::CN());
		$unitOfWork = $em->getUnitOfWork();
		$unitOfWork->recomputeSingleEntityChangeSet($class, $entity);
	}
}
