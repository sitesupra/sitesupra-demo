<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$helperSet = null;

require_once __DIR__ . '/cli-config.php';

$cli = \Supra\Console\Application::getInstance();
$cli->setCatchExceptions(true);
//$cli->setHelperSet($helperSet);

$cli->addCommandClasses(array(
	'Supra\Tests\Controller\Pages\Fixture\PageFixtureCommand',
	'Supra\Tests\Authorization\Fixture\AuthorizationFixtureCommand',
	'Supra\Database\Console\SchemaUpdateCommand',
	'Supra\Database\Console\SchemaDropCommand',
	'Supra\Console\Cron\Command',
	'Supra\Search\Command\RunIndexerCommand',
	'Supra\Search\Command\WipeCommand',
	'Supra\Search\Command\WipeQueuesCommand',
	'Supra\Search\Command\QueueAllPageLocalizationsCommand',
	'Supra\Controller\Pages\Command\LayoutRereadCommand',
	'Supra\Controller\Pages\Command\ProcessScheduledPagesCommand',
));

//$cli->addCronJob('su:schema:update', 
//		new \Supra\Console\Cron\Period\EveryHourPeriod('30'));

$cli->addCronJob('su:pages:process_scheduled', 
		new \Supra\Console\Cron\Period\EveryHourPeriod('30'));

$cli->setHelperSet($helperSet);
$cli->setCatchExceptions(false);
$input = new ArgvInput();
$output = new ConsoleOutput();
try {
	$cli->run($input, $output);
} catch (\Exception $e) {
	$cli->renderException($e, $output);
	\Log::error("Error while running CLI command: {$e->__toString()}");
	
	$statusCode = $e->getCode();
	$statusCode = is_numeric($statusCode) && $statusCode ? $statusCode : 1;
	if ($statusCode > 255) {
		$statusCode = 255;
	}
	
	exit($statusCode);
}
