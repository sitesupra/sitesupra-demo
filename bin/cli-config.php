<?php

use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

// This loads test connection as well
//require_once __DIR__ . '/../src/lib/Supra/bootstrap.php';
require_once __DIR__ . '/../tests/bootstrap.php';

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
