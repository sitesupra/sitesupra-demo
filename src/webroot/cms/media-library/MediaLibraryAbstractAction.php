<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\FileStorage\Entity\Abstraction\File as FileAbstraction;
use Supra\FileStorage\Entity\Folder;
use Supra\FileStorage\Entity\File;
use Supra\FileStorage\Entity\Image;
use Supra\AuditLog\AuditLogEvent;

/**
 * Common MediaLibrary action
 */
abstract class MediaLibraryAbstractAction extends CmsAction
{
	/**
	 * @var \Supra\FileStorage\FileStorage
	 */
	protected $fileStorage;
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;

	/**
	 * Binds file storage instance, entity manager
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->fileStorage = ObjectRepository::getFileStorage($this);
		$this->entityManager = ObjectRepository::getEntityManager($this->fileStorage);
	}
	
	/**
	 * @return Entity\Abstraction\File
	 */
	protected function getRequestedEntity($key, $className)
	{
		if ( ! $this->hasRequestParameter($key)) {
			throw new CmsException('medialibrary.validation_error.file_id_not_provided');
		}
		
		$id = $this->getRequestParameter($key);
		$file = $this->entityManager->find($className, $id);
		
		if (is_null($file)) {
			throw new CmsException('medialibrary.validation_error.file_not_exists');
		}
		
		return $file;
	}
	
	/**
	 * @return File
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
