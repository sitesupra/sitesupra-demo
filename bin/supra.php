<?php

$helperSet = null;

require_once __DIR__ . '/cli-config.php';

$cli = \Supra\Console\Application::getInstance();
$cli->setCatchExceptions(true);
//$cli->setHelperSet($helperSet);

$cli->addCommands(array(
	new \Supra\Tests\Controller\Pages\Fixture\PageFixtureCommand(),
	new \Supra\Tests\Authorization\Fixture\AuthorizationFixtureCommand(),
	new \Supra\Database\Console\SchemaUpdateCommand(),
	new \Supra\Database\Console\SchemaDropCommand(),
	new \Supra\Console\Cron\Command()
));

//$cli->addCronJob('su:schema:update', 
//		new \Supra\Console\Cron\Period\EveryHourPeriod('30'));

$cli->setHelperSet($helperSet);
$cli->run();
