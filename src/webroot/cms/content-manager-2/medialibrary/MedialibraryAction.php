<?php

namespace Supra\Cms\ContentManager\medialibrary;

use Supra\Cms\ContentManager\CmsActionController;
use Supra\FileStorage\FileStorage;

class MediaLibraryAction extends CmsActionController
{
	const TYPE_FOLDER = 1;
	const TYPE_IMAGE = 2;
	const TYPE_FILE = 3;

	public function listAction()
	{
		// FIXME: should doctrine entity manager be as file stogare parameter?
		$fileStorage = FileStorage::getInstance();

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

	public function viewAction()
	{
		if ( ! empty($_GET['id'])) {
			$id = $_GET['id'];

			// FIXME: should doctrine entity manager be as file stogare parameter?
			$fileStorage = FileStorage::getInstance();

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

	public function createAction()
	{

	}

	public function saveAction()
	{
		
	}

	public function deleteAction()
	{
		1 + 1;
	}

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

			$filestorage = FileStorage::getInstance();
			$filestorage->storeFileData($fileEntity, $file['tmp_name']);

			$em->flush();

			$output = $this->imageAndFileOutput($fileEntity);

			$this->getResponse()->setResponseData($output);
		}
	}

	private function imageAndFileOutput(&$node)
	{
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