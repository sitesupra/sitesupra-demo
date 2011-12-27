<?php

use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

//require_once __DIR__ . '/../src/lib/Supra/bootstrap.php';

// This loads test connection as well.
// We need test connection because of fixtures now.
$testBootstrap = __DIR__ . '/../tests/bootstrap.php';

if (file_exists($testBootstrap)) {
	require_once $testBootstrap;
} else {
	require_once __DIR__ . '/../src/lib/Supra/bootstrap.php';
}

$namespace = '';

if ( ! empty($_SERVER['argv'][1])) {
	
	$match = true;
	
	switch ($_SERVER['argv'][1]) {
		case 'public':
			$namespace = PageController::SCHEMA_PUBLIC;
			break;
		
		case 'draft':
		case 'cms':
			$namespace = PageController::SCHEMA_CMS;
			break;
		
		case 'test':
		case 'tests':
			$namespace = '#tests';
			break;
		
		case 'trash':
			$namespace = PageController::SCHEMA_TRASH;
			break;
		
		case 'history':
			$namespace = PageController::SCHEMA_HISTORY;
			break;
		
		case 'audit':
			$namespace = PageController::SCHEMA_AUDIT;
			break;
		
		default:
			$match = false;
	}
	
	if ($match) {
		array_splice($_SERVER['argv'], 1, 1);
	}
}

$em = ObjectRepository::getEntityManager($namespace);

$helpers = array(
		'db' => new ConnectionHelper($em->getConnection()),
		'em' => new EntityManagerHelper($em)
);
$helperSet = new HelperSet($helpers);
