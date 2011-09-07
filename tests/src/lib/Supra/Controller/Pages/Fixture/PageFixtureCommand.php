<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Symfony\Component\Console;

/**
 * PageFixtureCommand
 */
class PageFixtureCommand extends Console\Command\Command
{
	/**
	 */
	protected function configure()
	{
		$this->setName('su:fixture:page')
				->setDescription('Runs page fixtures.')
				->setHelp('Runs page fixtures.');
	}
	
	/**
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$em = $this->getHelper('em')->getEntityManager();
		
		$fixture = new FixtureHelper($em);
		$fixture->build();
	}
}
