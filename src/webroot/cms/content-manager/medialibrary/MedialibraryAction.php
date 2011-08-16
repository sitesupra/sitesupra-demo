<?php

namespace Supra\Cms\ContentManager\Medialibrary;

use Supra\Cms\ContentManager\CmsAction;
use Supra\FileStorage\Helpers\FileNameValidationHelper;
use Supra\FileStorage\ImageProcessor;
use Supra\FileStorage;
use Supra\ObjectRepository\ObjectRepository;

class MediaLibraryAction extends CmsAction
{
	private $fileStorage;
	
	// types for MediaLibrary UI
	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;
	
	public function execute()
	{
		$this->fileStorage = ObjectRepository::getFileStorage($this);
		
		parent::execute();
	}

	/**
	 * Used for list folder item
	 */
	public function listAction()
	{
		$em = ObjectRepository::getEntityManager($this->fileStorage);

		// TODO: currently FileRepository is not assigned to the file abstraction
		// FIXME: store the classname as constant somewhere?
		/* @var $repo FileRepository */
		$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$rootNodes = $repo->getRootNodes();

		$output = array();

		// if parent dir is set then we set folder as rootNode
		if ( ! empty($_GET['id'])) {
			$id = $_GET['id'];
			$node = $repo->findOneById($id);
			$rootNodes = $node->getChildren();
		}

		foreach ($rootNodes as $rootNode) {

			$item = array();

			$item['id'] = $rootNode->getId();
			$item['title'] = $rootNode->getName();


			if ($rootNode instanceof \Supra\FileStorage\Entity\Folder) {
				$item['type'] = self::TYPE_FOLDER;
			} else if ($rootNode instanceof \Supra\FileStorage\Entity\File) {

				$isImage = $rootNode instanceof \Supra\FileStorage\Entity\Image;

				if ($isImage) {
					$item['type'] = self::TYPE_IMAGE;
				} else {
					$item['type'] = self::TYPE_FILE;
				}
			}

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
		if ( ! empty($_GET['id'])) {
			$id = $_GET['id'];

			$em = ObjectRepository::getEntityManager($this->fileStorage);

			// TODO: currently FileRepository is not assigned to the file abstraction
			// FIXME: store the classname as constant somewhere?
			/* @var $repo FileRepository */
			$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			/* @var $node \Supra\FileStorage\Entity\File */
			$node = $repo->findOneById($id);

			$nodeOutput = $this->imageAndFileOutput($node);
			$output = array($nodeOutput);

			$return = array(
				'totalRecords' => count($output),
				'records' => $output,
			);

			$this->getResponse()->setResponseData($return);
		}
	}

	/**
	 * Used for new folder creation
	 */
	public function insertAction()
	{
		if ( ! empty($_POST['title'])) {

			$em = ObjectRepository::getEntityManager($this->fileStorage);

			$dir = new \Supra\FileStorage\Entity\Folder();
			// FIXME: should doctrine entity manager be as file stogare parameter?

			$dirName = $_POST['title'];

			$em->persist($dir);
			$dir->setName($dirName);

			// Adding child folder if parent exists
			if ( ! empty($_POST['parent'])) {

				$folderId = $_POST['parent'];

				// TODO: currently FileRepository is not assigned to the file abstraction
				// FIXME: store the classname as constant somewhere?
				/* @var $repo FileRepository */
				$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
				/* @var $node \Supra\FileStorage\Entity\File */
				$folder = $repo->findOneById($folderId);

				if ( ! empty($folder)) {
					$folder->addChild($dir);
				} else {
					throw new MedialibraryException('Parent folder entity not found');
				}
			}

			// trying to create folder
			try {
				$this->fileStorage->createFolder($dir);
			} catch (FileStorage\Exception\FileStorageException $exception) {
				$this->handleException($exception);
				
				return;
			}
			
			$em->flush();

			$insertedId = $dir->getId();
			$this->getResponse()->setResponseData($insertedId);
		}
	}

	/**
	 * Used for folder or file renaming
	 */
	public function saveAction()
	{
		if ( ! empty($_POST['id'])) {


			// FIXME: should doctrine entity manager be as file stogare parameter?
			$em = ObjectRepository::getEntityManager($this->fileStorage);

			// TODO: currently FileRepository is not assigned to the file abstraction
			// FIXME: store the classname as constant somewhere?
			/* @var $repo FileRepository */
			$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			/* @var $folder \Supra\FileStorage\Entity\File */
			$file = $repo->findOneById($_POST['id']);

			// find out with what we are working now with file or folder
			if ($file instanceof \Supra\FileStorage\Entity\Folder) {

				// if is set folders new title we rename folder
				try {
					if (isset($_POST['title'])) {
						$title = $_POST['title'];
					} else {
						throw new MedialibraryException('Folder title isn\'t set');
					}
				} catch (MedialibraryException $exc) {
					$this->setErrorMessage($exc->getMessage());
					return;
				}

				// trying to rename folder. Catching all FileStorage and Validation exceptions
				// and passing them to MediaLibrary UI
				try {
					$this->fileStorage->renameFolder($file, $title);
				} catch (FileStorage\Exception\FileStorageException $exception) {
					$this->handleException($exception);
					
					return;
				}
				
			} else if ($file instanceof \Supra\FileStorage\Entity\File) {

				try {

					if (isset($_POST['title'])) {
						// TODO: Localization?
						$this->getResponse()->setResponseData(null);
						return;
					} else if (isset($_POST['filename'])) {

						$filename = $_POST['filename'];

						// trying to rename file. Catching all FileStorage and Validation exceptions
						// and passing them to MediaLibrary UI
						try {
							$this->fileStorage->renameFile($file, $filename);
						} catch (FileStorage\Exception\FileStorageException $exception) {
							$this->handleException($exception);
							return;
						}
					} else {
						throw new MedialibraryException('File name isn\'t set');
					}
				} catch (MedialibraryException $exc) {
					$this->setErrorMessage($message);
					return;
				}
			} else {
				throw new MedialibraryException('Wrong entity passed');
			}

			// flushing results to database
			$em->flush();

			$fileId = $file->getId();
			$this->getResponse()->setResponseData($fileId);
		}
	}

	/**
	 * Deletes file or folder
	 */
	public function deleteAction()
	{

		if ( ! empty($_POST['id'])) {
			$recordId = $_POST['id'];
			$em = ObjectRepository::getEntityManager($this->fileStorage);
			/* @var $repo FileRepository */
			$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			/* @var $node \Supra\FileStorage\Entity\File */
			$record = $repo->findOneById($recordId);

			if ( ! empty($record)) {

				try {
					// try to delete
					$this->fileStorage->remove($record);
				} catch (FileStorage\Exception\FileStorageException $exception) {
					$this->handleException($exception);
					return;
				}

				$em->flush();

				$this->getResponse()->setResponseData(null);
			} else {
				$this->setErrorMessage('Cant find record with such id');
			}
		}
	}

	/**
	 * File upload action
	 */
	public function uploadAction()
	{
		// FIXME: should doctrine entity manager be as file stogare parameter?

		if (isset($_FILES['file']) && empty($_FILES['file']['error'])) {

			$file = $_FILES['file'];

			$em = ObjectRepository::getEntityManager($this->fileStorage);

			// checking for replace action
			if (isset($_POST['file_id'])) {
				$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
				/* @var $node \Supra\FileStorage\Entity\File */
				$fileToReplace = $repo->findOneById($_POST['file_id']);

				if ( ! empty($fileToReplace) && ($fileToReplace instanceof FileStorage\Entity\File)) {

					$em->persist($fileToReplace);

					try {
						$this->fileStorage->replaceFile($fileToReplace, $file);
					} catch (FileStorage\Exception\FileStorageException $exception) {
						$this->handleException($exception);
						return;
					} catch (\Exception $exc) {
						\Log::error($exc->getMessage());
						return;
					}

					$em->flush();
				}

				$output = $this->imageAndFileOutput($fileToReplace);

				$this->getResponse()->setResponseData($output);

				return;
			}

			$fileEntity = null;
			if ($this->fileStorage->isMimeTypeImage($file['type'])) {
				$fileEntity = new \Supra\FileStorage\Entity\Image();
			} else {
				$fileEntity = new \Supra\FileStorage\Entity\File();
			}
			$em->persist($fileEntity);

			$fileEntity->setName($file['name']);
			$fileEntity->setSize($file['size']);
			$fileEntity->setMimeType($file['type']);

			// adding file as folders child if parent folder is set
			if ( ! empty($_POST['folder'])) {

				$folderId = $_POST['folder'];

				// TODO: currently FileRepository is not assigned to the file abstraction
				// FIXME: store the classname as constant somewhere?
				/* @var $repo FileRepository */
				$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
				/* @var $node \Supra\FileStorage\Entity\File */
				$folder = $repo->findOneById($folderId);

				if ( ! empty($folder)) {
					$folder->addChild($fileEntity);
				} else {
					throw new MedialibraryException('Parent folder entity not found');
				}
			}

			// file metadata
			$fileData = new \Supra\FileStorage\Entity\MetaData('en');
			$fileData->setMaster($fileEntity);
			$fileData->setTitle($file['name']);

			// trying to upload file
			try {
				$this->fileStorage->storeFileData($fileEntity, $file['tmp_name']);
				// additional jobs for images
				if ($fileEntity instanceof \Supra\FileStorage\Entity\Image) {
					// store original size
					$imageProcessor = new ImageProcessor\ImageResizer();
					$imageInfo = $imageProcessor->getImageInfo($this->fileStorage->getFilesystemPath($fileEntity));
					$fileEntity->setWidth($imageInfo['width']);
					$fileEntity->setHeight($imageInfo['height']);
					// create preview
					$this->fileStorage->createResizedImage($fileEntity, 200, 200);
				}
			} catch (FileStorage\Exception\FileStorageException $exception) {
				$this->handleException($exception);
				return;
			}

			$em->flush();

			// genrating output
			$output = $this->imageAndFileOutput($fileEntity);

			$this->getResponse()->setResponseData($output);
		} else {
			//TODO: Separate messages to UI and to logger
			$this->setErrorMessage($this->fileStorage->fileUploadErrorMessages[$_FILES['error']]);
		}
	}

	public function downloadAction()
	{
		if ( ! empty($_GET['id'])) {

			$fileId = intval($_GET['id']);

			$em = ObjectRepository::getEntityManager($this->fileStorage);

			// TODO: currently FileRepository is not assigned to the file abstraction
			// FIXME: store the classname as constant somewhere?
			/* @var $repo FileRepository */
			$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			/* @var $folder \Supra\FileStorage\Entity\File */
			$file = $repo->findOneById($fileId);

			if (empty($file) || ! ($file instanceof FileStorage\Entity\File)) {
				echo '404';
				// TODO: throw new Exception\ResourceNotFoundException
			}

			// The file cache must be unique if "timestamp" hash is returned
			// TODO: Not modified
			$timestamp = null;
			if ( ! empty($timestamp)) {
				header('Pragma: private');
				header("Expires: " . date('r', strtotime('+1 year')));
				header('Cache-Control: private');

				if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
					header("HTTP/1.1 304 Not Modified");
					return;
				}
			} else {
				header("Expires: 0");
				header("Cache-Control: private, must-revalidate");
			}

			$content = $this->fileStorage->getFileContent($file);

			$mimeType = $file->getMimeType();
			$fileName = $file->getName();

			if ( ! empty($mimeType)) {
				header('Content-type: ' . $mimeType);
			}

			header('Content-Disposition: attachment; filename="' . $fileName . '"');
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . strlen($content));

			echo $content;
		}
	}

	public function imagerotateAction()
	{
		if ( ! empty($_POST['id'])) {
			$entityManager = ObjectRepository::getEntityManager($this->fileStorage);
			$fileRepository = 
					$entityManager->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			$file = $fileRepository->findOneById($_POST['id']);

			if ($file instanceof \Supra\FileStorage\Entity\Image) {

				try {
					if (isset($_POST['rotate']) && is_numeric($_POST['rotate'])) {
						$rotationCount = - intval($_POST['rotate'] / 90);
						$this->fileStorage->rotateImage($file, $rotationCount);
					}

				} catch (\Supra\FileStorage\Exception\FileStorageException $e) {
					$this->setErrorMessage('Image processing error: ' . $e->getMessage());
					return;
				}
				
			} else {
				$this->setErrorMessage('Could not perform action on non-image file');
				return;
			}

			$entityManager->flush();
			
			$fileData = $this->imageAndFileOutput($file);
			$this->getResponse()->setResponseData($fileData);
		}	
	}

	public function imagecropAction()
	{
		if ( ! empty($_POST['id'])) {
			$entityManager = ObjectRepository::getEntityManager($this->fileStorage);
			$fileRepository = 
					$entityManager->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			$file = $fileRepository->findOneById($_POST['id']);

			if ($file instanceof \Supra\FileStorage\Entity\Image) {

				try {
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

				} catch (\Supra\FileStorage\Exception\FileStorageException $e) {
					$this->setErrorMessage('Image processing error: ' . $e->getMessage());
					return;
				}
				
			} else {
				$this->setErrorMessage('Could not perform action on non-image file');
				return;
			}

			$entityManager->flush();

			$fileData = $this->imageAndFileOutput($file);
			$this->getResponse()->setResponseData($fileData);
		}			
	}

	/**
	 * Pretty hardcoded output right now
	 * @param \Supra\FileStorage\Entity\File $node
	 * @return array $output response
	 */
	private function imageAndFileOutput(&$node)
	{
		// checking for image MIME type
		$isImage = $node instanceof \Supra\FileStorage\Entity\Image;

		$type = null;

		if ($isImage) {
			$type = self::TYPE_IMAGE;
		} else {
			$type = self::TYPE_FILE;
		}

		/** @var $this->fileStorage \Supra\FileStorage\FileStorage */
		$this->fileStorage = ObjectRepository::getFileStorage($this);

		$filePath = $this->fileStorage->getWebPath($node);

		$output = null;

		if ($type == self::TYPE_FILE) {
			$output = array(
				'title' => $node->getTitle(),
				'filename' => $node->getName(),
				'description' => 'Hardcoded Description',
				'file_web_path' => $filePath,
				'id' => $node->getId(),
				'type' => $type
			);
		}

		if ($type == self::TYPE_IMAGE) {
			$output = array(
				'id' => $node->getId(),
				'type' => $type,
				'title' => $node->getTitle(),
				'filename' => $node->getName(),
				'description' => 'Hardcoded Description',
				'size' => $node->getSize(),
				'sizes' => array()
			);

			$sizes = $node->getImageSizeCollection();
			if ( ! $sizes->isEmpty()) {
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

	/**
	 * Sets error message to JsonResponse object
	 * @param string $message error message
	 */
	private function setErrorMessage($message)
	{
		$this->getResponse()->setErrorMessage($message);
		$this->getResponse()->setStatus(false);
	}
	
	/**
	 * Sets correct response error message
	 * @param FileStorage\FileStorageException $exception
	 */
	private function handleException(FileStorage\FileStorageException $exception)
	{
		if ($exception instanceof FileStorage\Exception\LocalizedException) {
			$messageKey = $exception->getMessageKey();

			if ( ! empty($messageKey)) {
				$messageKey = '{#' . $messageKey . '#}';
				$this->setErrorMessage($messageKey);
				
				return;
			}
		}
		
		$this->setErrorMessage($exception->getMessage());
	}

}