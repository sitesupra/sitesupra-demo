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

			$isImage = $node->isMimeTypeImage($node->getMimeType());

			if ($isImage) {
				$type = self::TYPE_IMAGE;
			} else {
				$type = self::TYPE_FILE;
			}

//			$type = $this->getType($node->getMimeType());
			//List of properties which should be returned for each file or image
			$properties = isset($_GET['properties']) ? $_GET['properties'] : 'id';
			$properties = explode(',', $properties);

			$filePath = $node->getPath(DIRECTORY_SEPARATOR, true);

			$output = array();

			if ($type == self::TYPE_FILE) {
				$output[] = array(
					'title' => 'Report',
					'filename' => 'report.xml',
					'description' => 'Annual financial report for our shareholders bla bla blaaa',
					'file_web_path' => $filePath,
					'id' => $node->getId(),
					'type' => $type
				);
			}

			if ($type == self::TYPE_IMAGE) {
				$output[] = array(
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

			$return = array(
				'totalRecords' => count($output),
				'records' => $output,
			);

			$this->getResponse()->setResponseData($return);
		}
	}

	public function createAction()
	{
		1 + 1;
	}

	public function saveAction()
	{
		1 + 1;
	}

	public function deleteAction()
	{
		1 + 1;
	}

}