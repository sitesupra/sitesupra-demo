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
	

}
