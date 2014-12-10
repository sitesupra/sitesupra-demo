<?php

function getWords () {
	return array(
		'lorem' => array('a', 'ac', 'accumsan', 'adipiscing', 'aenean', 'aliquam', 'aliquet'),
		
		'a' => array('enim','erat','eros','est','et'),
		'ac' => array('etiam','eu','euismod','facilisi','facilisis','fames'),
		'accumsan' => array('amet','ante','arcu','at','auctor'),
		'adipiscing' => array('augue','bibendum','blandit','commodo','condimentum','congue','consectetur','consequat','convallis','cras','cum','curabitur','cursus','dapibus','diam','dictum'),
		'aenean' => array('faucibus','felis','fermentum','feugiat','fringilla','fusce'),
		'aliquam' => array('gravida','habitant','habitasse','hac','hendrerit','iaculis','id','imperdiet'),
		'aliquet' => array('dictumst','dignissim','dis','dolor','donec','dui','duis','egestas','eget','eleifend','elementum','elit'),
		
		'facilisis' => array('in','integer','interdum','ipsum','justo','lacinia','lacus','laoreet','lectus','leo','libero'),
		'eros' => array('ligula','lobortis','luctus','maecenas','magna','magnis','malesuada','massa','mattis','mauris','metus','mi','molestie','mollis','montes','morbi','mus','nam','nascetur'),
		'fermentum' => array('natoque','nec','neque','netus','nibh','nisi','nisl','non','nulla','nullam','nunc','odio','orci','ornare','parturient','pellentesque'),
		'hendrerit' => array('penatibus','pharetra','phasellus','placerat','platea','porta','porttitor','posuere','potenti','praesent','pretium','proin','pulvinar','purus','quam','quis'),
		'dictumst' => array('quisque','rhoncus','ridiculus','risus','rutrum','sagittis','sapien','scelerisque','sed','sem','semper','senectus','sit','sociis','sodales','sollicitudin','suscipit','suspendisse'),
		'condimentum' => array('tellus','tempor','tempus','tincidunt','tortor','tristique','turpis','ullamcorper','ultrices','ultricies','urna','ut','varius','vehicula','vel','velit','venenatis'),
		'amet' => array('vestibulum','vitae','vivamus','viverra','volutpat','vulputate'),
	);
}

function getChildrenCount ($word) {
	$words = getWords();
	
	if (isset($words[$word])) {
		return count($words[$word]);
	} else {
		return 0;
	}
}

function getItem ($word, $deep = false) {
	$item = array(
		'id' => $word,
		'title' => ucwords($word),
		'children_count' => getChildrenCount($word),
		'icon' => getChildrenCount($word) ? 'folder' : 'page'
	);
	
	if ($deep) {
		$item['children'] = getChildren($word);
	}
	
	return $item;
}

function getChildren ($word) {
	$words = getWords();
	
	if (getChildrenCount($word)) {
		$children = $words[$word];
		$result = array();
		
		foreach ($children as $child) {
			$result[]= getItem($child);
		}
		
		return $result;
	} else {
		return array();
	}
}

?>
