<?php

namespace Supra\Cms\MediaLibrary;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\FileStorage\Entity\Abstraction\File;
use Supra\FileStorage\Entity\Image;
use Supra\Log\AuditLogEvent;

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

	/**
	 * Write to audit log
	 *
	 * @param string $action
	 * @param mixed $data
	 * @param int $level 
	 */
	protected function writeAuditLog($action, $data, $level = AuditLogEvent::INFO) 
	{
		if ($data instanceof File) {
			$data = array(
				'title' => $data->getFileName(),
				'id' => $data->getId()
			);
		}
		
		parent::writeAuditLog($action, $data, $level);
	}
}
