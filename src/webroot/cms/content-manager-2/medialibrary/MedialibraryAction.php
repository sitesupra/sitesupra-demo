<?php

namespace Supra\Cms\ContentManager\medialibrary;

use Supra\Cms\ContentManager\CmsActionController;
use Supra\FileStorage\FileStorage;

class MediaLibraryAction extends CmsActionController
{
	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	/**
	 * Used for list folder
	 */
	public function listAction()
	{
		// FIXME: getting default DEM right now
		$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();

		// TODO: currently FileRepository is not assigned to the file abstraction
		// FIXME: store the classname as constant somewhere?
		/* @var $repo FileRepository */
		$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
		$rootNodes = $repo->getRootNodes();

		//TODO: parse $nodes into JS array
		//...

		$output = array();

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

			// FIXME: getting default DEM right now
			$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();

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
			// FIXME: getting default DEM right now
			$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();
			$dir = new \Supra\FileStorage\Entity\Folder();
			// FIXME: should doctrine entity manager be as file stogare parameter?
			$fileStorage = FileStorage::getInstance();

			$dirName = $_POST['title'];
			$dir->setName($dirName);
			$em->persist($dir);

			if ( ! empty($_POST['parent'])) {

				$folderId = $_POST['parent'];

				// TODO: currently FileRepository is not assigned to the file abstraction
				// FIXME: store the classname as constant somewhere?
				/* @var $repo FileRepository */
				$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
				/* @var $node \Supra\FileStorage\Entity\File */
				$folder = $repo->findOneById($folderId);

				//TODO: some check on not existant folder

				$folder->addChild($dir);
			}

			$em->flush();

			$destination = $dir->getPath(DIRECTORY_SEPARATOR, true);

			$mkDirResult = $fileStorage->createFolder($destination);

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
			$fileStorage = FileStorage::getInstance();

			// FIXME: getting default DEM right now
			$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();

			// TODO: currently FileRepository is not assigned to the file abstraction
			// FIXME: store the classname as constant somewhere?
			/* @var $repo FileRepository */
			$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
			/* @var $folder \Supra\FileStorage\Entity\File */
			$file = $repo->findOneById($_POST['id']);
			
			
			if ($file instanceof \Supra\FileStorage\Entity\Folder) {
				
				if(isset($_POST['title'])){
					$title = $_POST['title'];
				} else {
					throw new MedialibraryException('Folder title isn\'t set');
				}
				
				$file = $fileStorage->renameFolder($file, $title);
				
			} else if ($file instanceof \Supra\FileStorage\Entity\File) {
				
				if(isset($_POST['title'])) {
					// TODO: Localization? 
					$this->getResponse()->setResponseData(null);
					return;
					
				} else if(isset($_POST['filename'])){
					
					$filename = $_POST['filename'];
					$file = $fileStorage->renameFile($file, $filename);
					
				} else {
					throw new MedialibraryException('File name isn\'t set');
				}	
				
			} else {
				throw new MedialibraryException('');
			}
			
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
		if (isset($_FILES['file']) && empty($_FILES['file']['error'])) {

			$file = $_FILES['file'];

			// FIXME: should doctrine entity manager be as file stogare parameter?
			$fileStorage = FileStorage::getInstance();

			// FIXME: getting default DEM right now
			$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();

			$fileEntity = new \Supra\FileStorage\Entity\File();
			$em->persist($fileEntity);

			$fileEntity->setName($file['name']);
			$fileEntity->setSize($file['size']);
			$fileEntity->setMimeType($file['type']);

			if ( ! empty($_POST['folder'])) {

				$folderId = $_POST['folder'];

				// TODO: currently FileRepository is not assigned to the file abstraction
				// FIXME: store the classname as constant somewhere?
				/* @var $repo FileRepository */
				$repo = $em->getRepository('Supra\FileStorage\Entity\Abstraction\File');
				/* @var $node \Supra\FileStorage\Entity\File */
				$folder = $repo->findOneById($folderId);

				//TODO: some check on not existant folder

				$folder->addChild($fileEntity);
			}

			$fileData = new \Supra\FileStorage\Entity\MetaData('en');
			$fileData->setMaster($fileEntity);
			$fileData->setTitle($file['name']);

			$fileStorage->storeFileData($fileEntity, $file['tmp_name']);

			$em->flush();

			$output = $this->imageAndFileOutput($fileEntity);

			$this->getResponse()->setResponseData($output);
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

}