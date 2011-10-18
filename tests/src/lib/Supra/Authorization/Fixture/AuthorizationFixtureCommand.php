<?php

namespace Supra\Tests\Authorization\Fixture;

use Symfony\Component\Console;
use Doctrine\ORM\Events;
use Supra\Controller\Pages\Listener\PublicVersionedTableIdChange;
use Supra\ObjectRepository\ObjectRepository;

/**
 * AuthorizationFixtureCommand
 */
class AuthorizationFixtureCommand extends Console\Command\Command
{
	/**
	 */
	protected function configure()
	{
		$this->setName('su:fixture:authorization')
				->setDescription('Runs authorization fixtures.')
				->setHelp('Runs authorization fixtures.');
	}
	
	/**
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$fixture = new FixtureHelper('Supra\Cms\CmsController');
		$fixture->build();
		
		$output->writeln("Fixtures finished successfully");
	}
}
