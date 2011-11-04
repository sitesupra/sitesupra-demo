<?php

use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

$namespace = '';

if ( ! empty($_SERVER['argv'][1])) {

	// This is the default
	if ($_SERVER['argv'][1] == 'public') {
		array_splice($_SERVER['argv'], 1, 1);
		$namespace = PageController::SCHEMA_PUBLIC;
	}
	
	if ($_SERVER['argv'][1] == 'draft') {
		array_splice($_SERVER['argv'], 1, 1);
		$namespace = PageController::SCHEMA_CMS;
	}

	if ($_SERVER['argv'][1] == 'test' || $_SERVER['argv'][1] == 'tests') {
		
		// Load test connection as well
		require_once __DIR__ . '/phpunit-bootstrap.php';
		
		array_splice($_SERVER['argv'], 1, 1);
		$namespace = '#tests';
	}

	if ($_SERVER['argv'][1] == 'trash') {
		array_splice($_SERVER['argv'], 1, 1);
		$namespace = PageController::SCHEMA_TRASH;
	}

	if ($_SERVER['argv'][1] == 'history') {
		array_splice($_SERVER['argv'], 1, 1);
		$namespace = PageController::SCHEMA_HISTORY;
	}
}

$em = ObjectRepository::getEntityManager($namespace);

$helpers = array(
		'db' => new ConnectionHelper($em->getConnection()),
		'em' => new EntityManagerHelper($em)
);
$helperSet = new HelperSet($helpers);
