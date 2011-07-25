<?php

namespace Supra\Cms\ContentManager\medialibrary;

use Supra\Controller\SimpleController;

/**
 *
 */
class MediaLibraryAction extends SimpleController
{

	public $hardcodedReturn = array(
		0 => array(
			'id' => 0,
			'title' => '',
			'type' => 1,
			'children' => array(1, 2),
		),
		1 => array(
			'id' => 1,
			'title' => 'Images',
			'type' => 1,
			'children' => array(),
		),
		2 => array(
			'id' => 2,
			'title' => 'Abstract',
			'type' => 1,
			'children' => array(5, 8),
		),
		3 => array(
			'id' => 3,
			'title' => 'Illustrations',
			'type' => 1,
			'children' => array(7, 9),
		),
		9 => array(
			'id' => 9,
			'title' => 'Summer',
			'type' => 1,
			'children' => array(10),
		),
		10 => array(
			'id' => 10,
			'title' => 'June',
			'type' => 1,
			'children' => array(),
		),
		6 => array(
			'id' => 6,
			'type' => 2,
			'title' => 'Flowers',
			'filename' => 'flower.jpg',
			'description' => 'Short description',
			'size' => '213kb',
			'sizes' => array(
				'60x60' => array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-1-thumb.jpg'),
				'200x200' => array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-1.jpg'),
				'original' => array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-1-original.jpg'),
			),
			'60x60_url' => '/cms/supra/img/media/picture-1-thumb.jpg',
			'200x200_url' => '/cms/supra/img/media/picture-1.jpg',
			'original_url' => '/cms/supra/img/media/picture-1-original.jpg',
		),
		5 => array(
			'id' => 5,
			'type' => 2,
			'title' => 'Tulips',
			'filename' => 'tulips.jpg',
			'description' => 'Short description',
			'size' => '64kb',
			'sizes' => array(
				'60x60' => array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-2-thumb.jpg'),
				'200x200' => array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-2.jpg'),
				'original' => array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-2-original.jpg'),
			),
			'60x60_url' => '/cms/supra/img/media/picture-2-thumb.jpg',
			'200x200_url' => '/cms/supra/img/media/picture-2.jpg',
			'original_url' => '/cms/supra/img/media/picture-2-original.jpg',
		),
		4 => array(
			'id' => 4,
			'type' => 2,
			'title' => 'Koala',
			'filename' => 'koala.jpg',
			'description' => 'Short description',
			'size' => '64kb',
			'sizes' => array(
				'60x60' => array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-3-thumb.jpg'),
				'200x200' => array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-3.jpg'),
				'original' => array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-3-original.jpg'),
			),
			'60x60_url' => '/cms/supra/img/media/picture-3-thumb.jpg',
			'200x200_url' => '/cms/supra/img/media/picture-3.jpg',
			'original_url' => '/cms/supra/img/media/picture-3-original.jpg',
		),
		7 => array(
			'id' => 7,
			'type' => 2,
			'title' => 'Penguins',
			'filename' => 'penguins.jpg',
			'description' => 'Short description',
			'size' => '110kb',
			'sizes' => array(
				'60x60' => array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-4-thumb.jpg'),
				'200x200' => array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-4.jpg'),
				'original' => array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-4-original.jpg'),
			),
			'60x60_url' => '/cms/supra/img/media/picture-4-thumb.jpg',
			'200x200_url' => '/cms/supra/img/media/picture-4.jpg',
			'original_url' => '/cms/supra/img/media/picture-4-original.jpg',
		),
		8 => array(
			'id' => 8,
			'type' => 3,
			'title' => 'Report',
			'filename' => 'report.xml',
			'description' => 'Annual financial report for our shareholders bla bla blaaa',
			'size' => '110kb',
			'file_web_path' => '/cms/supra/img/media/report.xml',
		),
	);

	/**
	 * @return string
	 */
	public function medialibraryAction()
	{
		// FIXME: should doctrine entity manager be as file stogare parameter?
		$fileStorage = \Supra\FileStorage\FileStorage::getInstance();
		
		// FIXME: getting default DEM right now
		$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();
		
		// TODO: currently FileRepository is not assigned to the file abstraction
		// FIXME: store the classname as constant somewhere?
		$repo = $em->getRepository('\Supra\FileStorage\Abstraction\File');
		$nodes = $repo->getRootNodes();
		
		//TODO: parse $nodes into JS array
		//...
		
		$id = isset($_GET['id']) ? $_GET['id'] : 0;

		//Result type (0 - all, 1 - only folders, 2 - images and folders, 3 - files and folder
		$type = isset($_GET['type']) ? $_GET['type'] : 0;

		//List of properties which should be returned for each file or image
		$properties = isset($_GET['properties']) ? $_GET['properties'] : 'id';
		$properties = explode(',', $properties);

		$output = array();

		if (isset($this->hardcodedReturn[$id])) {
			if ($this->hardcodedReturn[$id]['type'] == 1) {
				//Folder
				$children = $this->hardcodedReturn[$id]['children'];

				foreach ($children as $child_id) {
					$child = $this->hardcodedReturn[$child_id];

					if ($type && $child['type'] != 1 && $type != $child['type']) {
						//If type is 2 (Images) then show only images, if type is 3 then only files
						//Always show folders
						continue;
					}

					if ($child['type'] == 1) {
						$child['children_count'] = count($child['children']);
						unset($child['children']);
					} else {
						$child = $this->getProperties($child, $properties);
					}

					$output [] = $child;
				}
			} else {
				//File or image
				$child = $this->hardcodedReturn[$id];

				if ( ! $type || $type == $child['type']) {
					//If type is 2 (Images) then show only images, if type is 3 then only files

					$child = getProperties($child, $properties);

					$output [] = $child;
				}
			}
		}
		
		// TODO: json encoding must be already inside the manager action response object
		$return = array(
			'totalRecords' => count($output),
			'records' => $output,
		);
		echo json_encode($return);
	}

	public function listAction()
	{
		1 + 1;
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

	/**
	 * PRIVATE METHODS
	 */

	/**
	 *
	 * @param <type> $data
	 * @param <type> $properties
	 * @return string
	 */
	private function getProperties($data, $properties)
	{
		$output = array();

		foreach ($properties as $property) {
			if (isset($data[$property])) {
				$output[$property] = $data[$property];
			} else {
				$output[$property] = null;
			}
		}

		return $output;
	}

}
