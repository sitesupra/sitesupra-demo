<?php

namespace Supra\Cms\MediaLibrary\Medialibrary;

use Supra\FileStorage\ImageProcessor;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity;
use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\Cms\Exception\CmsException;
use Supra\FileStorage\Entity\Folder;
use Supra\Cms\MediaLibrary\ApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Configuration\PropertyConfiguration;

class MedialibraryAction extends MediaLibraryAbstractAction
{
	// types for MediaLibrary UI


	
	const DUPLICATE_NAME_PATTERN = '%s (%d).%s';

	//
	const MAX_FILE_BASENAME_LENGTH = 100;





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

			if (trim($fileName) == '') {
				throw new CmsException(null, 'Empty filename not allowed');
			}

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
		
		// Custom Properties
		$dirty = false;
		
		$input = $this->getRequestInput();
		/* @var $input \Supra\Request\RequestData */
		$propertyConfigurations = $this->fileStorage->getCustomPropertyConfigurations();
		foreach ($propertyConfigurations as $configuration) {
			/* @var $configuration PropertyConfiguration */
			
			$propertyName = $configuration->name;
			
			if ($input->offsetExists($propertyName)) {
				$value = $input->offsetGet($propertyName);

				$property = $this->fileStorage->getFileCustomProperty($file, $propertyName);
				
				$property->setEditableValue($value, $configuration->getEditable());
				
				$dirty = true;
			}
		}
		
		if ($dirty) {
			$this->entityManager->flush();
		}

		$this->writeAuditLog('%item% saved', $file);

		$response = array();

		// when changing image private attribute, previews and thumbs will change their paths
		// so we will output new image info
		if ($file instanceof Entity\File) {
			$response = $this->imageAndFileOutput($file);
        }

		$this->getResponse()
				->setResponseData($response);
	}

	/**
	 * @param Entity\File $file
	 */
	protected function removeFilesRecursively(Entity\Folder $file)
	{
		if ($file->hasChildren()) {

			foreach ($file->getChildren() as $childFile) {

				if ($childFile instanceof Entity\Folder) {
					$this->removeFilesRecursively($childFile);
				} else {
					$this->removeSingleFile($childFile);
				}
			}
		}

		$this->removeSingleFile($file);
	}

	public function moveAction()
	{
		$repository = $this->entityManager->getRepository(Entity\Abstraction\File::CN());
		/* @var $repository \Supra\FileStorage\Repository\FileNestedSetRepository */
		$repository->getNestedSetRepository()->lock();

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
		$post = $this->getRequest()->getPost();

		if ( ! isset($_FILES['file']) || ! empty($_FILES['file']['error'])) {
			$message = 'Error uploading the file';

			//TODO: Separate messages to UI and to logger
			if ( ! empty($_FILES['file']['error']) && isset($this->fileStorage->fileUploadErrorMessages[$_FILES['file']['error']])) {
				$message = $this->fileStorage->fileUploadErrorMessages[$_FILES['file']['error']];
			}

			$this->getResponse()->setErrorMessage($message);

			return;
		}

		$file = $_FILES['file'];

		$this->entityManager->beginTransaction();
		$repository = $this->entityManager->getRepository(Entity\Abstraction\File::CN());
		/* @var $repository \Supra\FileStorage\Repository\FileNestedSetRepository */
		$repository->getNestedSetRepository()->lock();

		// Permission check
		$uploadPermissionCheckFolder = null;

		if ( ! $this->emptyRequestParameter('folder')) {
			$uploadPermissionCheckFolder = $this->getFolder('folder');
		} else {
			$uploadPermissionCheckFolder = new Entity\SlashFolder();
		}
		$this->checkActionPermission($uploadPermissionCheckFolder, Entity\Abstraction\File::PERMISSION_UPLOAD_NAME);

		try {

			// getting the folder to upload in
			$folder = null;
			if ( ! $this->emptyRequestParameter('folder')) {
				$folder = $this->getFolder('folder');
			}

			// Will return the top folder created/found from folderPath string
			$firstSubFolder = null;

			// Create/get folder by path provided
			$folderPath = $this->getRequest()
					->getPostValue('folderPath', '');

			$folderPath = trim(str_replace('\\', '/', $folderPath), '/');

			if ( ! empty($folderPath)) {
				$folderPathParts = explode('/', $folderPath);
				foreach ($folderPathParts as $part) {

					$folderFound = false;
					$children = null;

					if ($folder instanceof Folder) {
						$children = $folder->getChildren();
					} elseif (is_null($folder)) {
						$children = $repository->getRootNodes();
					} else {
						throw new \LogicException("Not supported folder type: " . gettype($folder) . ', class: ' . get_class($folder));
					}

					foreach ($children as $child) {
						if ($child instanceof Folder) {
							$_name = $child->getTitle();
							if (strcasecmp($_name, $part) === 0) {
								$folderFound = $child;
								break;
							}
						}
					}

					if ($folderFound) {
						$folder = $folderFound;
					} else {
						$folder = $this->createFolder($part, $folder);
					}

					if (empty($firstSubFolder)) {
						$firstSubFolder = $folder;
					}
				}
			}

			// checking for replace action
			if ( ! $this->emptyRequestParameter('file_id')) {
				$fileToReplace = $this->getFile('file_id');
				$this->fileStorage->replaceFile($fileToReplace, $file);

				// close transaction and unlock the nested set
				$this->entityManager->commit();
				$repository->getNestedSetRepository()->unlock();

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

			// additional jobs for images
			if ($fileEntity instanceof Entity\Image) {
				// store original size
				$imageProcessor = $this->fileStorage->getImageResizer();
				$imageInfo = $imageProcessor->getImageInfo($file['tmp_name']);
				$fileEntity->setWidth($imageInfo->getWidth());
				$fileEntity->setHeight($imageInfo->getHeight());
			}

			if ( ! empty($folder)) {
				// get parent folder private/public status
				$publicStatus = $folder->isPublic();
				$fileEntity->setPublic($publicStatus);

				// Flush before nested set UPDATE
				$this->entityManager->flush();

				$folder->addChild($fileEntity);
			}
			
			// when "force" set to true, then we need to ignore duplicate 
			// filename exception, so postfix will be added to filename
			if ($fileEntity instanceof Entity\File) {
				if ($post->has('force') && $post->getValid('force', \Supra\Validator\Type\AbstractType::BOOLEAN)) {
					try { 
						$this->fileStorage->validateFileUpload($fileEntity, $file['tmp_name']);
					} catch (Exception\DuplicateFileNameException $e) {

						$siblings = $fileEntity->getSiblings();
						
						$existingNames = array();
						foreach($siblings as $siblingEntity) {
							if ( ! $siblingEntity->equals($fileEntity)) {
								$existingNames[] = $siblingEntity->getFileName();
							}
						}

						$extension = $fileEntity->getExtension();
						$fileNamePart = $fileEntity->getFileNameWithoutExtension();
						
						$possibleName = null;
						// assume that 1000 iterations is enough, to create unique name
						// if not, well... duplicate file name exception will be thrown
						for ($i = 1; $i < 1000; $i++) {
							$possibleName = sprintf(self::DUPLICATE_NAME_PATTERN, $fileNamePart, $i, $extension);
							
							if ( ! in_array($possibleName, $existingNames)) {
								$fileEntity->setFileName($possibleName);
								break;
							}
						}
					}
				}
			}
			
			// when it is not enough available memory to complete Image resize/crop
			// file will be uploaded as simple File entity
			if ($fileEntity instanceof Entity\Image) {
				try {
					$this->fileStorage->validateFileUpload($fileEntity, $file['tmp_name']);		
				} catch (\Supra\FileStorage\Exception\InsufficientSystemResources $e) {

					// Removing image
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

						// Flush before nested set UPDATE
						$this->entityManager->flush();

						$folder->addChild($fileEntity);
					}

					$message = "Amount of memory required for image [{$file['name']}] resizing exceeds available, it will be uploaded as a document";
					$this->getResponse()
							->addWarningMessage($message);
				}
			}

			$this->entityManager->flush();

			// trying to upload file
			$this->fileStorage->storeFileData($fileEntity, $file['tmp_name']);
		} catch (\Exception $e) {

			try {
				// close transaction and unlock the nested set
				$this->entityManager->flush();
				$this->entityManager->rollback();
				$repository->getNestedSetRepository()->unlock();
			} catch (\Exception $e) {
				$this->log->error("Failure on rollback/unlock: ", $e->__toString());
			}

			throw $e;
		}

		// close transaction and unlock the nested set
		$this->entityManager->commit();
		$repository->getNestedSetRepository()->unlock();

		// generating output
		$output = $this->imageAndFileOutput($fileEntity);

		if ( ! empty($firstSubFolder)) {
			$firstSubFolderOutput = $this->getEntityData($firstSubFolder);
			$output['folder'] = $firstSubFolderOutput;
		}

		$this->writeAuditLog('%item% uploaded', $fileEntity);
		$this->getResponse()->setResponseData($output);
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
		$postInput = $this->getRequestInput();
		$requestedSizes = $postInput->getChild('sizes', true);
		$requestedSizeNames = array();
		$isBroken = false;
		$thumbSize = null;
		$previewSize = null;

		try {
			if ($file instanceof Entity\Image && $this->fileStorage->fileExists($file)) {

				$thumbSize = $this->fileStorage->createResizedImage($file, 30, 30, true);
				$previewSize = $this->fileStorage->createResizedImage($file, 200, 200);

				while ($requestedSizes->hasNextChild()) {
					$requestedSize = $requestedSizes->getNextChild();
					$width = $requestedSize->getValid('width', 'smallint');
					$height = $requestedSize->getValid('height', 'smallint');
					$crop = $requestedSize->getValid('crop', 'boolean');

					$requestedSizeNames[] = $this->fileStorage->createResizedImage($file, $width, $height, $crop);
				}
			}
		} catch (Exception $e) {
			$isBroken = true;
		}

		$output = $this->fileStorage->getFileInfo($file, $localeId);

		// Return only requested sizes
		if ( ! empty($requestedSizeNames)) {
			foreach ($output['sizes'] as $sizeName => $size) {

				if ($sizeName == 'original') {
					continue;
				}

				if ( ! in_array($sizeName, $requestedSizeNames, true)) {
					unset($output['sizes'][$sizeName]);
				}
			}
		}
//		else {
//			unset($output['sizes']);
//		}
		// Create thumbnail&preview
		try {
			if ($file instanceof Entity\Image && $this->fileStorage->fileExists($file)) {
				if ($file->isPublic()) {
					$output['preview'] = $this->fileStorage->getWebPath($file, $previewSize);
					$output['thumbnail'] = $this->fileStorage->getWebPath($file, $thumbSize);
				} else {
					$output['thumbnail'] = $this->getPrivateImageWebPath($file, $thumbSize);
					$output['file_web_path'] = $output['preview'] = $this->getPrivateImageWebPath($file);

					if ( ! empty($output['sizes'])) {
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

		if ($isBroken) {
			$output['broken'] = true;
		} elseif ($checkExistance == ApplicationConfiguration::CHECK_FULL
				|| $checkExistance == ApplicationConfiguration::CHECK_PARTIAL) {

			$output['broken'] = ( ! $this->isAvailable($file));
		}

		$output['timestamp'] = $file->getModificationTime()->getTimestamp();
		
		// Custom Properties
		$propertyConfigurations = $this->fileStorage->getCustomPropertyConfigurations();
		$propertyData = array();
		foreach ($propertyConfigurations as $configuration) {
			/* @var $configuration PropertyConfiguration */
			$propertyName = $configuration->name;
			$propertyData[$propertyName] = $this->fileStorage->getFileCustomPropertyValue($file, $propertyName);
		}
		
		$output['metaData'] = $propertyData;
		
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