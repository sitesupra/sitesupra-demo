<?php

namespace Supra\Cms\MediaLibrary\Medialibrary;

use Supra\FileStorage\Helpers\FileNameValidationHelper;
use Supra\FileStorage\ImageProcessor;
use Supra\FileStorage;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Response\HttpResponse;
use Supra\Response\JsonResponse;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\Exception\LocalizedException;

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

			$item['id'] = $rootNode->getId();
			$item['title'] = $rootNode->getFileName();
			$item['type'] = $this->getEntityType($rootNode);
			$item['children_count'] = $rootNode->getNumberChildren();

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
			$folder->addChild($dir);
		}

		// trying to create folder
		$this->fileStorage->createFolder($dir);

		$this->entityManager->flush();

		$insertedId = $dir->getId();
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

			//TODO: localization (title)

			if ( ! $this->hasRequestParameter('filename')) {
				$this->getResponse()
						->setErrorMessage('No filename has been provided');

				return;
			}

			$fileName = $this->getRequestParameter('filename');

			// trying to rename file. Catching all FileStorage and Validation exceptions
			// and passing them to MediaLibrary UI
			$this->fileStorage->renameFile($file, $fileName);
		}

		$fileId = $file->getId();
		$this->getResponse()->setResponseData($fileId);
	}

	/**
	 * Deletes file or folder
	 */
	public function deleteAction()
	{
		$this->isPostRequest();
		$file = $this->getEntity();

		if (is_null($file)) {
			$this->getResponse()->setErrorMessage('File doesn\'t exist anymore');
		}

		// try to delete
		$this->fileStorage->remove($file);
	}

	/**
	 * File upload action
	 */
	public function uploadAction()
	{
		$this->isPostRequest();
		
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

			// adding file as folders child if parent folder is set
			if ( ! $this->emptyRequestParameter('folder')) {
				$folder = $this->getFolder('folder');
				$folder->addChild($fileEntity);
			}
			
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
			$fileData = new Entity\MetaData('en');
			$fileData->setMaster($fileEntity);
			$fileData->setTitle($humanName);
			
			// trying to upload file
			$this->fileStorage->storeFileData($fileEntity, $file['tmp_name']);
			
			// additional jobs for images
			if ($fileEntity instanceof Entity\Image) {
				// store original size
				$imageProcessor = new ImageProcessor\ImageResizer();
				$imageInfo = $imageProcessor->getImageInfo($this->fileStorage->getFilesystemPath($fileEntity));
				$fileEntity->setWidth($imageInfo['width']);
				$fileEntity->setHeight($imageInfo['height']);
				// create preview
				$this->fileStorage->createResizedImage($fileEntity, 200, 200);
			}

			$this->entityManager->flush();

			// genrating output
			$output = $this->imageAndFileOutput($fileEntity);

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
		$this->getResponse()->setResponseData($fileData);
	}

	/**
	 * Pretty hardcoded output right now
	 * @param Entity\File $node
	 * @return array $output response
	 */
	private function imageAndFileOutput(Entity\File $node)
	{
		$locale = $this->getLocale();
		$filePath = $this->fileStorage->getWebPath($node);

		$output = null;

		$output = array(
			'id' => $node->getId(),
			'filename' => $node->getFileName(),
			'title' => $node->getTitle($locale),
			'description' => $node->getDescription($locale),
			'file_web_path' => $filePath,
			'type' => self::TYPE_FILE,
			'size' => $node->getSize(),
		);

		// More data for images
		if ($node instanceof Entity\Image) {
			$output['type'] = self::TYPE_IMAGE;
			$output['sizes'] = array();

			$sizes = $node->getImageSizeCollection();
			
			foreach ($sizes as $size) {
				$sizeName = $size->getName();
				$sizePath = $this->fileStorage->getWebPath($node, $sizeName);
				$output['sizes'][$sizeName] = array(
					'id' => $sizeName,
					'width' => $size->getWidth(),
					'height' => $size->getHeight(),
					'external_path' => $sizePath
				);
				$output[$sizeName . '_url'] = $sizePath;
			}
			
			$output['sizes']['original'] = array(
				'id' => 'original',
				'width' => $node->getWidth(),
				'height' => $node->getHeight(),
				'external_path' => $filePath
			);
			$output['original_url'] = $filePath;
		}

		return $output;
	}

}