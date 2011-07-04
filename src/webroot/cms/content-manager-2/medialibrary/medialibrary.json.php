<?php
header('Content-type: application/json');

//Folder ID
$id = isset($_GET['id']) ? $_GET['id'] : 0;

//Result type (0 - all, 1 - only folders, 2 - images and folders, 3 - files and folder
$type = isset($_GET['type']) ? $_GET['type'] : 0;

//List of properties which should be returned for each file or image
$properties = isset($_GET['properties']) ? $_GET['properties'] : 'id';
$properties = explode(',', $properties);

$all_data = Array(
	0 => Array(
		'id' => 0,
		'title' => '',
		'type' => 1,
		'children' => Array(1,2),
	),
	1 => Array(
		'id' => 1,
		'title' => 'Images',
		'type' => 1,
		'children' => Array(3, 4, 6),
	),
	2 => Array(
		'id' => 2,
		'title' => 'Misc',
		'type' => 1,
		'children' => Array(5, 8),
	),
	3 => Array(
		'id' => 3,
		'title' => 'Illustrations',
		'type' => 1,
		'children' => Array(7, 9),
	),
	9 => Array(
		'id' => 9,
		'title' => 'Summer',
		'type' => 1,
		'children' => Array(10),
	),
	10 => Array(
		'id' => 10,
		'title' => 'June',
		'type' => 1,
		'children' => Array(),
	),
	
	4 => Array(
		'id' => 4,
		'type' => 2,
		'title' => 'Flowers',
		'filename' => 'flower.jpg',
		'description' => 'Short description',
		'size' => '213kb',
		'sizes' => Array(
			'60x60'    => Array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-1-thumb.jpg'),
			'200x200'  => Array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-1.jpg'),
			'original' => Array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-1-original.jpg'),
		),
		'60x60_url' => '/cms/supra/img/media/picture-1-thumb.jpg',
		'200x200_url' => '/cms/supra/img/media/picture-1.jpg',
		'original_url' => '/cms/supra/img/media/picture-1-original.jpg',
	),
	5 => Array(
		'id' => 5,
		'type' => 2,
		'title' => 'Tulips',
		'filename' => 'tulips.jpg',
		'description' => 'Short description',
		'size' => '64kb',
		'sizes' => Array(
			'60x60'    => Array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-2-thumb.jpg'),
			'200x200'  => Array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-2.jpg'),
			'original' => Array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-2-original.jpg'),
		),
		'60x60_url' => '/cms/supra/img/media/picture-2-thumb.jpg',
		'200x200_url' => '/cms/supra/img/media/picture-2.jpg',
		'original_url' => '/cms/supra/img/media/picture-2-original.jpg',
	),
	6 => Array(
		'id' => 6,
		'type' => 2,
		'title' => 'Koala',
		'filename' => 'koala.jpg',
		'description' => 'Short description',
		'size' => '64kb',
		'sizes' => Array(
			'60x60'    => Array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-3-thumb.jpg'),
			'200x200'  => Array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-3.jpg'),
			'original' => Array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-3-original.jpg'),
		),
		'60x60_url' => '/cms/supra/img/media/picture-3-thumb.jpg',
		'200x200_url' => '/cms/supra/img/media/picture-3.jpg',
		'original_url' => '/cms/supra/img/media/picture-3-original.jpg',
	),
	7 => Array(
		'id' => 7,
		'type' => 2,
		'title' => 'Penguins',
		'filename' => 'penguins.jpg',
		'description' => 'Short description',
		'size' => '110kb',
		'sizes' => Array(
			'60x60'    => Array('id' => '60x60', 'width' => 60, 'height' => 60, 'external_path' => '/cms/supra/img/media/picture-4-thumb.jpg'),
			'200x200'  => Array('id' => '200x200', 'width' => 200, 'height' => 150, 'external_path' => '/cms/supra/img/media/picture-4.jpg'),
			'original' => Array('id' => 'original', 'width' => 600, 'height' => 450, 'external_path' => '/cms/supra/img/media/picture-4-original.jpg'),
		),
		'60x60_url' => '/cms/supra/img/media/picture-4-thumb.jpg',
		'200x200_url' => '/cms/supra/img/media/picture-4.jpg',
		'original_url' => '/cms/supra/img/media/picture-4-original.jpg',
	),
	
	8 => Array(
		'id' => 8,
		'type' => 3,
		'title' => 'Report',
		'filename' => 'report.xml',
		'description' => 'Annual financial report for our shareholders bla bla blaaa',
		'size' => '110kb',
		'file_web_path' => '/cms/supra/img/media/report.xml',
	),
);

function getProperties ($data, $properties) {
	$output = Array();
	
	foreach($properties as $property) {
		if (isset($data[$property])) {
			$output[$property] = $data[$property];
		} else {
			$output[$property] = null;
		}
	}
	
	return $output;
}

$output = Array();

if (isset($all_data[$id])) {
	if ($all_data[$id]['type'] == 1) {
		//Folder
		$children = $all_data[$id]['children'];
		
		foreach($children as $child_id) {
			$child = $all_data[$child_id];
			
			if ($type && $child['type'] != 1 && $type != $child['type']) {
				//If type is 2 (Images) then show only images, if type is 3 then only files
				//Always show folders
				continue;
			}
			
			if ($child['type'] == 1) {
				$child['children_count'] = count($child['children']);
				unset($child['children']);
			} else {
				$child = getProperties($child, $properties);
			}
			
			$output []= $child;
		}
	} else {
		//File or image
		$child = $all_data[$id];
		
		if (!$type || $type == $child['type']) {
			//If type is 2 (Images) then show only images, if type is 3 then only files
			
			$child = getProperties($child, $properties);
			
			$output []= $child;
		}
	}
}

echo json_encode(Array(
	'totalRecords' => count($output),
	'records' => $output,
));

?>