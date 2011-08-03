<?php

namespace Supra\Cms\ContentManager\medialibrary;

use Supra\Cms\ContentManager\CmsActionController;
use Supra\FileStorage\Helpers\FileNameValidationHelper;
use Supra\FileStorage;

class MediaLibraryAction extends CmsActionController
{

	// types for MediaLibrary UI
	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	/**
	 * Used for list folder item
	 */
	public function listAction()
	{
		$fileStorage = FileStorage\FileStorage::getInstance();
		$em = $fileStorage->getEntityManager();

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

				$isImage = $rootNode->isMimeTypeImage($rootNode->getMimeType());

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

			$fileStorage = FileStorage\FileStorage::getInstance();
			$em = $fileStorage->getEntityManager();

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

			$fileStorage = FileStorage\FileStorage::getInstance();
			$em = $fileStorage->getEntityManager();

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

			$destination = $dir->getPath(DIRECTORY_SEPARATOR, false);
			$folderName = $dir->getName();

			// trying to create folder
			try {
				$fileStorage->createFolder($destination, $folderName);
			} catch (FileStorage\Exception\RuntimeException $exc) {
				$this->setErrorMessage($exc->getMessage());
				return;
			} catch (FileStorage\Exception\UploadFilterException $exc) {
				$this->setErrorMessage($exc->getMessage());
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
			$fileStorage = FileStorage\FileStorage::getInstance();

			$em = $fileStorage->getEntityManager();

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
					$fileStorage->renameFolder($file, $title);
				} catch (FileStorage\Exception\RuntimeException $exc) {
					$this->setErrorMessage($exc->getMessage());
					return;
				} catch (FileStorage\Exception\UploadFilterException $exc) {
					$this->setErrorMessage($exc->getMessage());
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
							$fileStorage->renameFile($file, $filename);
						} catch (FileStorage\Exception\RuntimeException $exc) {
							$this->setErrorMessage($exc->getMessage());
							return;
						} catch (FileStorage\Exception\UploadFilterException $exc) {
							$this->setErrorMessage($exc->getMessage());
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

	public function deleteAction()
	{
		1 + 1;
	}

	/**
	 * File upload action
	 */
	public function uploadAction()
	{
		// FIXME: should doctrine entity manager be as file stogare parameter?
		$fileStorage = FileStorage\FileStorage::getInstance();

		if (isset($_FILES['file']) && empty($_FILES['file']['error'])) {

			$file = $_FILES['file'];

			$em = $fileStorage->getEntityManager();

			// checking for replace action
			if (isset($_POST['file_id'])) {
				$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
				/* @var $node \Supra\FileStorage\Entity\File */
				$fileToReplace = $repo->findOneById($_POST['file_id']);

				$em->persist($fileToReplace);
				
				if ( ! empty($fileToReplace) && ($fileToReplace instanceof FileStorage\Entity\File)) {
					try {
						$fileStorage->replaceFile($fileToReplace, $file);
					} catch (FileStorage\Exception\RuntimeException $exc) {
						$this->setErrorMessage($exc->getMessage());
						return;
					} catch (FileStorage\Exception\UploadFilterException $exc) {
						$this->setErrorMessage($exc->getMessage());
						return;
					} catch (\Exception $exc) {
						\Log::error($exc->getMessage());
						return;
					}
				}
			
				$em->flush();
			}

			$fileEntity = new \Supra\FileStorage\Entity\File();
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
				$fileStorage->storeFileData($fileEntity, $file['tmp_name']);
			} catch (FileStorage\Exception\RuntimeException $exc) {
				$this->setErrorMessage($exc->getMessage());
				return;
			} catch (FileStorage\Exception\UploadFilterException $exc) {
				$this->setErrorMessage($exc->getMessage());
				return;
			}

			$em->flush();

			// genrating output
			$output = $this->imageAndFileOutput($fileEntity);

			$this->getResponse()->setResponseData($output);
		} else {
			//TODO: Separate messages to UI and to logger
			$this->setErrorMessage($fileStorage->fileUploadErrorMessages[$_FILES['error']]);
		}
	}

	public function downloadAction()
	{
		$fileStorage = FileStorage\FileStorage::getInstance();

		if ( ! empty($_GET['id'])) {

			$fileId = intval($_GET['id']);

			$em = $fileStorage->getEntityManager();

			// TODO: currently FileRepository is not assigned to the file abstraction
			// FIXME: store the classname as constant somewhere?
			/* @var $repo FileRepository */
			$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			/* @var $folder \Supra\FileStorage\Entity\File */
			$file = $repo->findOneById($fileId);

			if (empty($file) || ! ($file instanceof FileStorage\Entity\File)) {
				echo '404';
				// TODO: throw new NotFoundException
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

			$content = $fileStorage->getFileContent($file);

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

	/**
	 * Pretty hardcoded output right now
	 * @param \Supra\FileStorage\Entity\File $node
	 * @return array $output response
	 */
	private function imageAndFileOutput(&$node)
	{
		// checking for image MIME type
		$isImage = $node->isMimeTypeImage($node->getMimeType());

		$type = null;

		if ($isImage) {
			$type = self::TYPE_IMAGE;
		} else {
			$type = self::TYPE_FILE;
		}

		// getting full file path
		$filePath = $node->getPath(DIRECTORY_SEPARATOR, true);

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
				'sizes' => Array(
					'60x60' => Array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => $filePath),
					'200x200' => Array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => $filePath),
					'original' => Array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => $filePath),
				),
				'60x60_url' => $filePath,
				'200x200_url' => $filePath,
				'original_url' => $filePath,
			);
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

}