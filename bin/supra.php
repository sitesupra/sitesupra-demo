<?php

// Supra starting
require_once __DIR__ . '/phpunit-bootstrap.php';

$configFile = __DIR__ . '/cli-config.php';

require_once $configFile;

$cli = new \Symfony\Component\Console\Application('Supra Command Line Interface', '7.0.0');
$cli->setCatchExceptions(true);
//$cli->setHelperSet($helperSet);

$cli->addCommands(array(
	new \Supra\Tests\Controller\Pages\Fixture\PageFixtureCommand()
));

$cli->run();
