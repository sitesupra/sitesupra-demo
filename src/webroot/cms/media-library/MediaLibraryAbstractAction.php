<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Common MediaLibrary action
 */
abstract class MediaLibraryAbstractAction extends CmsAction
{
	/**
	 * @var FileStorage\FileStorage
	 */
	protected $fileStorage;
	
	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * Binds file storage instance, entity manager
	 */
	public function __construct()
	{
		$this->fileStorage = ObjectRepository::getFileStorage($this);
		$this->entityManager = ObjectRepository::getEntityManager($this->fileStorage);
	}
	
	/**
	 * @return Entity\Abstraction\File
	 */
	protected function getRequestedEntity($key, $className)
	{
		if ( ! $this->hasRequestParameter($key)) {
			throw new MedialibraryException('File ID has not been sent');
		}
		
		$id = $this->getRequestParameter($key);
		$file = $this->entityManager->find($className, $id);
		
		if (is_null($file)) {
			throw new MedialibraryException('Requested file does not exist anymore');
		}
		
		return $file;
	}
	
	/**
	 * @return Entity\Abstraction\File
	 */
	protected function getEntity($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\FileStorage\Entity\Abstraction\File');
		
		return $file;
	}
	
	/**
	 * @return Entity\File
	 */
	protected function getFile($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\FileStorage\Entity\File');
		
		return $file;
	}
	
	/**
	 * @return Entity\Folder
	 */
	protected function getFolder($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\FileStorage\Entity\Folder');
		
		return $file;
	}
	
	/**
	 * @return Entity\Image
	 */
	protected function getImage($key = 'id')
	{
		$file = $this->getRequestedEntity($key, 'Supra\FileStorage\Entity\Image');
		
		return $file;
	}
}
