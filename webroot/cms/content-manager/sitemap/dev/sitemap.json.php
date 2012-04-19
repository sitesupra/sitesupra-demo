<?php

$data = json_decode(file_get_contents('sitemap.json'), true);
$out = array();

//print_r($data);

function extractChunk ($arr) {
	$out = array();
	
	foreach($arr as $key => $val) {
		$out[$key] = $val;
		$out[$key]['children_count'] = 0;
		if (isset($out[$key]['children'])) {
			$out[$key]['children_count'] = count($out[$key]['children']);
			$out[$key]['children'] = array();
		}
	}
	
	return $out;
}
function findChildren ($arr, $id) {
	foreach($arr as $key => $val) {
		if ($val['id'] == $id) {
			if (isset($val['children'])) {
				return $val['children'];
			} else {
				return array();
			}
		}
		if (!empty($val['children'])) {
			$out = findChildren($val['children'], $id);
			if ($out !== false) return $out;
		}
	}
	
	return false;
}

if (!isset($_GET['parent_id']) || $_GET['parent_id'] === '0') {
	$out = extractChunk($data);
} else {
	$out = findChildren($data, $_GET['parent_id']);
	$out = extractChunk($out);
	
	$offset = 0;
	$resultsPerRequest = 1000;
	if (isset($_GET['offset'])) $offset = intval($_GET['offset']);
	if (isset($_GET['resultsPerRequest'])) $resultsPerRequest = intval($_GET['resultsPerRequest']);
	
	$out = array_slice($out, $offset, $resultsPerRequest);
}

?>
{
	"status": 1,
	"data": <?php echo json_encode($out); ?>
}