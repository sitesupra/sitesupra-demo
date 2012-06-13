<?php

use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

// When used with symbolic links
$rootDir = dirname(dirname(__DIR__));
$baseName = basename(__FILE__);

// Otherwise
if (realpath($rootDir . '/bin/' . $baseName) !== realpath(__FILE__)) {
	$rootDir = dirname(__DIR__);
}

define('SUPRA_PATH', $rootDir . DIRECTORY_SEPARATOR . 'src/');

//require_once __DIR__ . '/../src/lib/Supra/bootstrap.php';

// This loads test connection as well.
// We need test connection because of fixtures now.
$testBootstrap = $rootDir . '/tests/bootstrap.php';

if (file_exists($testBootstrap) && file_exists($rootDir . '/tests/src/conf/supra.ini')) {
	require_once $testBootstrap;
} else {
	require_once $rootDir . '/src/lib/Supra/bootstrap.php';
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
			$namespace = PageController::SCHEMA_DRAFT;
			break;
		
		case 'test':
		case 'tests':
			$namespace = '#tests';
			break;
		
		case 'audit':
			$namespace = PageController::SCHEMA_AUDIT;
			break;

		case 'users':
			$namespace = 'Supra\User';
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
