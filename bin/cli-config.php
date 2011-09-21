<?php

// Load test connection as well
require_once __DIR__ . '/phpunit-bootstrap.php';

$namespace = '';

if ($_SERVER['argv'][1] == 'draft') {
	array_splice($_SERVER['argv'], 1, 1);
	$namespace = 'Supra\Cms';
}

if ($_SERVER['argv'][1] == 'test') {
	array_splice($_SERVER['argv'], 1, 1);
	$namespace = 'Supra\Tests';
}

$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($namespace);
//$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('Supra\Cms');

$helpers = array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
);
$helperSet = new Symfony\Component\Console\Helper\HelperSet($helpers);
