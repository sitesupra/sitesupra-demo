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
use Supra\FileStorage\Entity\Folder;
use Supra\Cms\MediaLibrary\ApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;

class MedialibraryAction extends MediaLibraryAbstractAction
{
	// types for MediaLibrary UI

	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	//
	const MAX_FILE_BASENAME_LENGTH = 100;

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

			if ( ! $this->emptyRequestParameter('type')) {

				$itemType = $this->getEntityType($rootNode);
				$requestedType = $this->getRequestParameter('type');

				if ( ! (
						($itemType == $requestedType) ||
						($itemType == Folder::TYPE_ID)
						)) {
					continue;
				}
			}

			if ($rootNode instanceof Entity\File) {

				$extension = mb_strtolower($rootNode->getExtension());

				$knownExtensions = $this->getApplicationConfigValue('knownFileExtensions', array());
				if (in_array($extension, $knownExtensions)) {
					$item['known_extension'] = $extension;
				}

				$checkExistance = $this->getApplicationConfigValue('checkFileExistence');
				if ($checkExistance == ApplicationConfiguration::CHECK_FULL) {
					$item['broken'] = ( ! $this->isAvailable($rootNode));
				}
			}

			// Get thumbnail
			if ($rootNode instanceof Entity\Image) {
				// create preview
				// TODO: hardcoded 30x30
				try {
					if ($this->fileStorage->fileExists($rootNode)) {
						$sizeName = $this->fileStorage->createResizedImage($rootNode, 30, 30, true);
						if ($rootNode->isPublic()) {
							$item['thumbnail'] = $this->fileStorage->getWebPath($rootNode, $sizeName);
						} else {
							$item['thumbnail'] = $this->getPrivateImageWebPath($rootNode, $sizeName);
						}
					}
				} catch (\Exception $e) {
					$item['broken'] = true;
				}
			}

			$item['id'] = $rootNode->getId();
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

		if ( ! $this->hasRequestParameter('filename')) {
			$this->getResponse()
					->setErrorMessage('Folder title was not sent');

			return;
		}

		$dir = new Entity\Folder();
		$this->entityManager->persist($dir);

		$dirName = $this->getRequestParameter('filename');
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
		$this->writeAuditLog('%item% created', $dir);
		$this->getResponse()->setResponseData($insertedId);
	}

	/**
	 * Used for folder or file renaming
	 */
	public function saveAction()
	{
		$this->isPostRequest();

		$file = $this->getEntity();

		// set private
		if ($this->hasRequestParameter('private')) {
			$private = $this->getRequestParameter('private');

			if ($private == 0) {
				$this->fileStorage->setPublic($file);
				$this->writeAuditLog('%item% was set as public', $file);
			}

			if ($private == 1) {
				$this->fileStorage->setPrivate($file);
				$this->writeAuditLog('%item% was set as private', $file);
			}

			$this->entityManager->flush();
			$this->getResponse()->setResponseData(null);
			return;
		}

		// renaming
		if ($this->hasRequestParameter('filename')) {

			$fileName = $this->getRequestParameter('filename');

			$originalFileInfo = pathinfo($file->getFileName());

			$newFileInfo = pathinfo($fileName);

			if (mb_strlen($newFileInfo['basename'], 'utf-8') > 100) {

				if ($file instanceof Entity\Folder) {
					throw new CmsException(null, 'Folder name is too long! Maximum length is ' . self::MAX_FILE_BASENAME_LENGTH . ' characters!');
				} else {
					throw new CmsException(null, 'File name is too long! Maximum length is ' . self::MAX_FILE_BASENAME_LENGTH . ' characters!');
				}
			}

			if ($file instanceof Entity\Folder) {
				$this->fileStorage->renameFolder($file, $fileName);
			} else {
				$this->fileStorage->renameFile($file, $fileName);
			}
		}

		$this->writeAuditLog('%item% saved', $file);

		$response = array();

		// when changing image private attribute, previews and thumbs will change their paths
		// so we will output new image info
		if ($file instanceof Entity\Image) {
			$response = $this->imageAndFileOutput($file);
		}

		$this->getResponse()
				->setResponseData($response);
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

		$this->writeAuditLog('%item% deleted', $file);
	}

	public function moveAction()
	{
		$this->isPostRequest();
		$file = $this->getEntity();

//		$this->checkActionPermission($file, Entity\Abstraction\File::PERMISSION_DELETE_NAME);		
		$parentId = $this->getRequestParameter('parent_id');

		$target = null;
		if ( ! empty($parentId)) {
			$target = $this->entityManager->getRepository(Entity\Abstraction\File::CN())
					->findOneById($parentId);
		}

		if (is_null($file)) {
			$this->getResponse()->setErrorMessage('File doesn\'t exist anymore');
		}

		// try to move
		try {
			$this->fileStorage->move($file, $target);
		} catch (Exception\RuntimeException $e) {
			throw new CmsException(null, $e->getMessage());
		}

		$this->writeAuditLog('%item% moved', $file);
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
		} else {
			$uploadPermissionCheckFolder = new Entity\SlashFolder();
		}
		$this->checkActionPermission($uploadPermissionCheckFolder, Entity\Abstraction\File::PERMISSION_UPLOAD_NAME);

		$localeId = $this->getLocale()->getId();

		if (isset($_FILES['file']) && empty($_FILES['file']['error'])) {

			$file = $_FILES['file'];

			$this->entityManager->beginTransaction();

			try {
				// checking for replace action
				if ( ! $this->emptyRequestParameter('file_id')) {
					$fileToReplace = $this->getFile('file_id');
					$this->fileStorage->replaceFile($fileToReplace, $file);

					// Commit the changes
					$this->entityManager->commit();
					
					$this->writeAuditLog('%item% replaced', $fileToReplace);

					$output = $this->imageAndFileOutput($fileToReplace);
					$this->getResponse()->setResponseData($output);

					return;
				}

				$fileEntity = null;
				if ($this->fileStorage->isSupportedImageFormat($file['tmp_name'])) {
					$fileEntity = new Entity\Image();
				} else {
					$fileEntity = new Entity\File();
				}
				$this->entityManager->persist($fileEntity);

				$fileEntity->setFileName($file['name']);
				$fileEntity->setSize($file['size']);
				$fileEntity->setMimeType($file['type']);

				$humanName = $file['name'];

				// Could move to separate method, should be configurable {
				// Remove extension part
				$extensionLength = strlen($fileEntity->getExtension());

				if ($extensionLength != 0) {
					$extensionLength ++;
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


				// additional jobs for images
				if ($fileEntity instanceof Entity\Image) {
					// store original size
					$imageProcessor = new ImageProcessor\ImageResizer();
					$imageInfo = $imageProcessor->getImageInfo($file['tmp_name']);
					$fileEntity->setWidth($imageInfo->getWidth());
					$fileEntity->setHeight($imageInfo->getHeight());
				}

				// adding file as folders child if parent folder is set
				$folder = null;
				if ( ! $this->emptyRequestParameter('folder')) {

					$folder = $this->getFolder('folder');

					// get parent folder private/public status
					$publicStatus = $folder->isPublic();
					$fileEntity->setPublic($publicStatus);

					$folder->addChild($fileEntity);
				}

				if ($fileEntity instanceof Entity\Image) {
					try {
						$this->fileStorage->validateFileUpload($fileEntity, $file['tmp_name']);
					} catch (\Supra\FileStorage\Exception\InsufficientSystemResources $e) {

						$this->entityManager->flush();
						$this->entityManager->remove($fileEntity);
						$this->entityManager->flush();

						$fileEntity = new Entity\File();

						$this->entityManager->persist($fileEntity);

						$fileEntity->setFileName($file['name']);
						$fileEntity->setSize($file['size']);
						$fileEntity->setMimeType($file['type']);

						if ( ! is_null($folder)) {
							$publicStatus = $folder->isPublic();
							$fileEntity->setPublic($publicStatus);

							$folder->addChild($fileEntity);
						}

						$message = "Amount of memory required for image [{$humanName}] resizing exceeds available, it will be uploaded as File";
						$this->getResponse()
								->addWarningMessage($message);
					}
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

				$this->entityManager->flush();
			} catch (\Exception $e) {
				$this->entityManager->rollback();
				throw $e;
			}

			$this->entityManager->commit();

			// genrating output
			$output = $this->imageAndFileOutput($fileEntity);

			$this->writeAuditLog('%item% uploaded', $fileEntity);
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
		$this->writeAuditLog('%item% rotated', $file);
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
		$this->writeAuditLog('%item% cropped', $file);
		$this->getResponse()->setResponseData($fileData);
	}

	/**
	 * File info array
	 * @param Entity\File $file
	 * @return array
	 */
	private function imageAndFileOutput(Entity\File $file, $localeId = null)
	{
		$output = $this->fileStorage->getFileInfo($file, $localeId);

		// Create thumbnail&preview
		try {
			if ($file instanceof Entity\Image && $this->fileStorage->fileExists($file)) {
				$thumbSize = $this->fileStorage->createResizedImage($file, 30, 30, true);
				$previewSize = $this->fileStorage->createResizedImage($file, 200, 200);

				if ($file->isPublic()) {
					$output['preview'] = $this->fileStorage->getWebPath($file, $previewSize);
					$output['thumbnail'] = $this->fileStorage->getWebPath($file, $thumbSize);
				} else {
					$output['thumbnail'] = $this->getPrivateImageWebPath($file, $thumbSize);
					$output['file_web_path'] = $output['preview'] = $this->getPrivateImageWebPath($file);

					foreach ($output['sizes'] as $sizeName => &$size) {
						$sizePath = null;

						if ($sizeName == 'original') {
							$sizePath = $output['file_web_path'];
						} else {
							$sizePath = $this->getPrivateImageWebPath($file, $sizeName);
						}

						$size['external_path'] = $sizePath;
					}
				}
			}
		} catch (\Exception $e) {
			$output['broken'] = true;
			return $output;
		}

		$extension = mb_strtolower($file->getExtension());
		$knownExtensions = $this->getApplicationConfigValue('knownFileExtensions', array());
		if (in_array($extension, $knownExtensions)) {
			$output['known_extension'] = $extension;
		}

		$checkExistance = $this->getApplicationConfigValue('checkFileExistence');
		if ($checkExistance == ApplicationConfiguration::CHECK_FULL
				|| $checkExistance == ApplicationConfiguration::CHECK_PARTIAL) {

			$output['broken'] = ( ! $this->isAvailable($file));
		}

		return $output;
	}

	/**
	 * Check weither $file exists and is readable
	 * @param Entity\File $file
	 * @return boolean
	 */
	private function isAvailable(Entity\File $file)
	{
		$filePath = $this->fileStorage
				->getFilesystemPath($file);

		if (is_readable($filePath)) {
			return true;
		}

		return false;
	}

	/**
	 * Helper method to fetch config value from ApplicationConfig class
	 * for media library
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	private function getApplicationConfigValue($key, $default = null)
	{
		$appConfig = ObjectRepository::getApplicationConfiguration($this);

		if ($appConfig instanceof ApplicationConfiguration) {
			if (property_exists($appConfig, $key)) {
				return $appConfig->$key;
			}
		}

		if ( ! is_null($default)) {
			return $default;
		}

		return null;
	}

	private function getPrivateImageWebPath(Entity\Image $image, $sizeName = null)
	{
		$path = '/' . SUPRA_CMS_URL . '/media-library/download/' . rawurlencode($image->getFileName());
		$query = array(
			'inline' => 'inline',
			'id' => $image->getId(),
		);

		if ( ! is_null($sizeName)) {

			$imageSize = $image->findImageSize($sizeName);

			if ($imageSize instanceof Entity\ImageSize) {
				$query['size'] = $imageSize->getFolderName();
			}
		}

		$queryOutput = http_build_query($query);

		return $path . '?' . $queryOutput . '&';
	}

}