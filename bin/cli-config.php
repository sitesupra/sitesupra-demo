<?php

$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('');
//$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('Supra\Cms');

$helpers = array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
);
$helperSet = new Symfony\Component\Console\Helper\HelperSet($helpers);
