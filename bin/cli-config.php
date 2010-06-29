<?php

require_once SUPRA_CONF_PATH . 'database.php';

// This is for doctrine CLI requests
$helpers = array(
	'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
	'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
);
$helperSet = new Symfony\Components\Console\Helper\HelperSet($helpers);