<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$helperSet = null;

// This is supposed to be overriden as necessarry by config/cli.php
$cliCommandClasses = array(
	'Supra\Upgrade\Command\DatabaseUpgradeCommand',
	'Supra\Upgrade\Command\ScriptUpgradeCommand',
	'Supra\Upgrade\Command\UpgradeCommand',
	'Supra\Tests\Controller\Pages\Fixture\PageFixtureCommand',
	'Supra\Tests\Authorization\Fixture\AuthorizationFixtureCommand',
	'Supra\Database\Console\SchemaUpdateCommand',
	'Supra\Database\Console\SchemaDropCommand',
	'Supra\Console\Cron\Command',
	'Supra\Console\Gearman\WorkerCommand',
	'Supra\Search\Command\RunIndexerCommand',
	'Supra\Search\Command\WipeCommand',
	'Supra\Search\Command\WipeQueuesCommand',
	'Supra\Search\Command\QueueAllPageLocalizationsCommand',
	'Supra\Controller\Pages\Command\GenerateBlockCommand',
	'Supra\Controller\Pages\Command\ThemeAddCommand',
	'Supra\Controller\Pages\Command\ThemeRemoveCommand',
	'Supra\Controller\Pages\Command\ThemeSetActiveCommand',
	//'Supra\Controller\Pages\Command\UpgradeTemplateLayoutsCommand',
	'Supra\Controller\Pages\Command\ProcessScheduledPagesCommand',
	'Supra\Controller\Pages\Command\PagePathRegenerationCommand',
	'Supra\Seo\Command\GenerateSitemapCommand',
	//'Supra\Controller\Pages\Command\UpgradeLinkReferencedElements',
	'Supra\Remote\Command\RemoteCommand',
	'Supra\User\Command\CreateUserCommand',
	'Supra\FileStorage\Command\RegenerateFilePathCommand',
	'Supra\NestedSet\Command\ValidateNestedSetCommand',
);

require_once __DIR__ . '/cli-config.php';

$cli = \Supra\Console\Application::getInstance();
$cli->setCatchExceptions(true);
$cli->setHelperSet($helperSet);

$cli->addCommandClasses($cliCommandClasses);

$cli->setCatchExceptions(false);

if (file_exists(SUPRA_CONF_PATH . 'cli.php')) {
	require_once SUPRA_CONF_PATH . 'cli.php';
}

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
