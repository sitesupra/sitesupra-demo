<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

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
	new \Supra\Console\Cron\Command(),
	new \Supra\Search\Command\RunIndexerCommand(),
	new \Supra\Search\Command\WipeCommand(),
	new \Supra\Search\Command\WipeQueuesCommand()
));

//$cli->addCronJob('su:schema:update', 
//		new \Supra\Console\Cron\Period\EveryHourPeriod('30'));

$cli->setHelperSet($helperSet);
$cli->setCatchExceptions(false);
$input = new ArgvInput();
$output = new ConsoleOutput();
try {
	$cli->run($input, $output);
} catch (\Exception $e) {
	$cli->renderException($e, $output);
	\Log::error("Error while running CLI command: {$e->__toString()}");
}
