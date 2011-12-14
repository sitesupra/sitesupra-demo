<?php

namespace Supra\Cms\MediaLibrary\Medialibrary;

use Supra\FileStorage\Helpers\FileNameValidationHelper;
use Supra\FileStorage\ImageProcessor;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Response\HttpResponse;
use Supra\Response\JsonResponse;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\Exception\LocalizedException;
use Supra\Cms\Exception\CmsException;
use Supra\Authorization\Exception\EntityAccessDeniedException;

class MedialibraryAction extends MediaLibraryAbstractAction
{
	// types for MediaLibrary UI
	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;
	
	/**
	 * Get internal file entity type constant
	 * @param Entity\Abstraction\File $entity
	 * @return int
	 */
	private function getEntityType(Entity\Abstraction\File $entity)
	{
		$type = null;
		
		if ($entity instanceof Entity\Folder) {
			$type = self::TYPE_FOLDER;
		} elseif ($entity instanceof Entity\Image) {
			$type = self::TYPE_IMAGE;
		} elseif ($entity instanceof Entity\File) {
			$type = self::TYPE_FILE;
		}
		
		return $type;
	}
	
	/**
	 * Used for list folder item
	 */
	public function listAction()
	{
		$rootNodes = array();
		$localeId = $this->getLocale()->getId();

		// FIXME: store the classname as constant somewhere?
		/* @var $repo FileRepository */
		$repo = $this->entityManager->getRepository('Supra\FileStorage\Entity\Abstraction\File');

		$output = array();

		// if parent dir is set then we set folder as rootNode
		if ( ! $this->emptyRequestParameter('id')) {
			$node = $this->getFolder('id');
			$rootNodes = $node->getChildren();
		} else {
			$rootNodes = $repo->getRootNodes();
		}

		foreach ($rootNodes as $rootNode) {
			/* @var $rootNode Entity\Abstraction\File */
			$item = array();
			
			$title = $rootNode->getFileName();
			
			if ($rootNode instanceof Entity\File) {
				$title = $rootNode->getTitle($localeId);
			}

			$item['id'] = $rootNode->getId();
			$item['title'] = $title;
			$item['filename'] = $rootNode->getFileName();
			$item['type'] = $this->getEntityType($rootNode);
			$item['children_count'] = $rootNode->getNumberChildren();
			$item['private'] = ! $rootNode->isPublic();

			$output[] = $item;
		}

		$return = array(
			'totalRecords' => count($output),
			'records' => $output,
		);

		$this->getResponse()->setResponseData($return);
	}

	/**
	 * Used for view file or image information
	 */
	public function viewAction()
	{
		$node = $this->getFile();

		$nodeOutput = $this->imageAndFileOutput($node);
		$output = array($nodeOutput);

		$return = array(
			'totalRecords' => count($output),
			'records' => $output,
		);

		$this->getResponse()->setResponseData($return);
	}

	/**
	 * Used for new folder creation
	 */
	public function insertAction()
	{
		$this->isPostRequest();
		
		if ( ! $this->hasRequestParameter('title')) {
			$this->getResponse()
					->setErrorMessage('Title was not sent');
			
			return;
		}
		
		$dir = new Entity\Folder();
		$this->entityManager->persist($dir);
		
		$dirName = $this->getRequestParameter('title');
		$dir->setFileName($dirName);

		// Adding child folder if parent exists
		if ( ! $this->emptyRequestParameter('parent')) {
			$folder = $this->getFolder('parent');
			
			// get parent folder private/public status
			$publicStatus = $folder->isPublic();
			$dir->setPublic($publicStatus);
			
			$folder->addChild($dir);
		}

		// trying to create folder
		$this->fileStorage->createFolder($dir);

		$this->entityManager->flush();

		$insertedId = $dir->getId();
		$this->writeAuditLog('insert', '%item% created', $dir);
		$this->getResponse()->setResponseData($insertedId);
	}

	/**
	 * Used for folder or file renaming
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$title = $this->getRequestParameter('title');
		$file = $this->getEntity();
		$localeId = $this->getLocale()->getId();

		// set private
		if ($this->hasRequestParameter('private')) {
			$private = $this->getRequestParameter('private');
			
			if($private == 0) {
				$this->fileStorage->setPublic($file);
			}

			if($private == 1) {
				$this->fileStorage->setPrivate($file);
			}

			$this->entityManager->flush();
			$this->getResponse()->setResponseData(null);
			return;
		}
		
		// find out with what we are working now with file or folder
		if ($file instanceof Entity\Folder) {
			
			if ( ! $this->hasRequestParameter('title')) {
				$this->getResponse()
						->setErrorMessage('Title is no provided');

				return;
			}
			
			// if is set folders new title we rename folder
			$this->fileStorage->renameFolder($file, $title);

		} else if ($file instanceof Entity\File) {

			if ($this->hasRequestParameter('filename')) {
				$fileName = $this->getRequestParameter('filename');

				// trying to rename file. Catching all FileStorage and Validation exceptions
				// and passing them to MediaLibrary UI
				$this->fileStorage->renameFile($file, $fileName);
			}
			
			if ($this->hasRequestParameter('title')) {
				$title = $this->getRequestParameter('title');
				$metaData = $file->getMetaData($localeId);
				$metaData->setTitle($title);
				
				$this->entityManager->flush();
			}
		}

		$fileId = $file->getId();
		$this->writeAuditLog('save', '%item% created', $file);
		$this->getResponse()->setResponseData($fileId);
	}

	/**
	 * Deletes file or folder
	 */
	public function deleteAction()
	{
		$this->isPostRequest();
		$file = $this->getEntity();

		$this->checkActionPermission($file, Entity\Abstraction\File::PERMISSION_DELETE_NAME);		

		if (is_null($file)) {
			$this->getResponse()->setErrorMessage('File doesn\'t exist anymore');
		}

		// try to delete
		try {
			$this->fileStorage->remove($file);
		} catch (Exception\NotEmptyException $e) {
			throw new CmsException('medialibrary.file_remove.can_not_delete_not_empty_directory', $e->getMessage());
		}
		
		$this->writeAuditLog('deleter', '%item% deleted', $file);
	}

	/**
	 * File upload action
	 */
	public function uploadAction()
	{
		$this->isPostRequest();
		
		$uploadPermissionCheckFolder = null;

		if ( ! $this->emptyRequestParameter('folder')) {
			$uploadPermissionCheckFolder = $this->getFolder('folder');
		}
		else {
			$uploadPermissionCheckFolder = new Entity\SlashFolder();
		}
		$this->checkActionPermission($uploadPermissionCheckFolder, Entity\Abstraction\File::PERMISSION_UPLOAD_NAME);
		
		$localeId = $this->getLocale()->getId();
		
		if (isset($_FILES['file']) && empty($_FILES['file']['error'])) {

			$file = $_FILES['file'];

			// checking for replace action
			if ( ! $this->emptyRequestParameter('file_id')) {
				$fileToReplace = $this->getFile('file_id');
				$this->fileStorage->replaceFile($fileToReplace, $file);

				$output = $this->imageAndFileOutput($fileToReplace);

				$this->getResponse()->setResponseData($output);

				return;
			}

			$fileEntity = null;
			if ($this->fileStorage->isMimeTypeImage($file['type'])) {
				$fileEntity = new Entity\Image();
			} else {
				$fileEntity = new Entity\File();
			}
			$this->entityManager->persist($fileEntity);

			$fileEntity->setFileName($file['name']);
			$fileEntity->setSize($file['size']);
			$fileEntity->setMimeType($file['type']);

			$humanName = $file['name'];
			
			// Could move to separate method, should be configurable
			{
				// Remove extension part
				$extensionLength = strlen($fileEntity->getExtension());

				if ($extensionLength != 0) {
					$extensionLength++;
					$humanName = substr($humanName, 0, -$extensionLength);
				}

				// Replace dots, underscores, space characters with space
				$humanNameSplit = preg_split('/[\s_\.]+/', $humanName);

				foreach ($humanNameSplit as &$humanNamePart) {
					$humanNamePart = mb_strtoupper(mb_substr($humanNamePart, 0, 1))
							. mb_substr($humanNamePart, 1);
				}

				// Implode back
				$humanName = implode(' ', $humanNameSplit);
			}

			// file metadata
			$fileData = new Entity\MetaData($localeId);
			$fileData->setMaster($fileEntity);
			$fileData->setTitle($humanName);
			
			// additional jobs for images
			if ($fileEntity instanceof Entity\Image) {
				// store original size
				$imageProcessor = new ImageProcessor\ImageResizer();
				$imageInfo = $imageProcessor->getImageInfo($file['tmp_name']);
				$fileEntity->setWidth($imageInfo['width']);
				$fileEntity->setHeight($imageInfo['height']);
			}
			
			// adding file as folders child if parent folder is set
			if ( ! $this->emptyRequestParameter('folder')) {	

				$folder = $this->getFolder('folder');
				
				// get parent folder private/public status
				$publicStatus = $folder->isPublic();
				$fileEntity->setPublic($publicStatus);
				
				$folder->addChild($fileEntity);
			}
			
			try {
				// trying to upload file
				$this->fileStorage->storeFileData($fileEntity, $file['tmp_name']);
			} catch (\Exception $e) {
				$this->entityManager->flush();
				$this->entityManager->remove($fileEntity);
				$this->entityManager->flush();
				
				throw $e;
			}
			
			if ($fileEntity instanceof Entity\Image) {
				// create preview
				$this->fileStorage->createResizedImage($fileEntity, 200, 200);
			}
			
			$this->entityManager->flush();

			// genrating output
			$output = $this->imageAndFileOutput($fileEntity);

			$this->writeAuditLog('upload', '%item% uploaded', $fileEntity);
			$this->getResponse()->setResponseData($output);
		} else {
			
			$message = 'Error uploading the file';
			
			//TODO: Separate messages to UI and to logger
			if ( ! empty($_FILES['file']['error']) && isset($this->fileStorage->fileUploadErrorMessages[$_FILES['file']['error']])) {
				$message = $this->fileStorage->fileUploadErrorMessages[$_FILES['file']['error']];
			}
			
			$this->getResponse()->setErrorMessage($message);
		}
	}

	/**
	 * Image rotate
	 */
	public function imagerotateAction()
	{
		$this->isPostRequest();
		$file = $this->getImage();

		if (isset($_POST['rotate']) && is_numeric($_POST['rotate'])) {
			$rotationCount = - intval($_POST['rotate'] / 90);
			$this->fileStorage->rotateImage($file, $rotationCount);
		}

		$fileData = $this->imageAndFileOutput($file);
		$this->writeAuditLog('rotate', '%item% rotated', $file);
		$this->getResponse()->setResponseData($fileData);
	}

	/**
	 * Image crop
	 */
	public function imagecropAction()
	{
		$this->isPostRequest();
		$file = $this->getImage('id');

		if (isset($_POST['crop']) && is_array($_POST['crop'])) {
			$crop = $_POST['crop'];
			if (isset($crop['left'], $crop['top'], $crop['width'], $crop['height'])) {
				$left = intval($crop['left']);
				$top = intval($crop['top']);
				$width = intval($crop['width']);
				$height = intval($crop['height']);
				$this->fileStorage->cropImage($file, $left, $top, $width, $height);
			}
		}

		$fileData = $this->imageAndFileOutput($file);
		$this->writeAuditLog('crop', '%item% cropped', $file);
		$this->getResponse()->setResponseData($fileData);
	}

	/**
	 * File info array
	 * @param Entity\File $file
	 * @return array
	 */
	private function imageAndFileOutput(Entity\File $file)
	{
		$localeId = $this->getLocale()->getId();
		$output = $this->fileStorage->getFileInfo($file, $localeId);
		
		return $output;
	}
}