<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Symfony\Component\Console;
use Doctrine\ORM\Events;
use Supra\Controller\Pages\Listener\PublicVersionedTableIdChange;
use Supra\ObjectRepository\ObjectRepository;

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
//		$em = $this->getHelper('em')->getEntityManager();
		
		// Draft connection
		$em = ObjectRepository::getEntityManager('Supra\Cms');
		
//		$listeners = $em->getEventManager()->getListeners(Events::loadClassMetadata);
//		
//		foreach ($listeners as $listener) {
//			if ($listener instanceof PublicVersionedTableIdChange) {
//				$listeners = $em->getEventManager()->removeEventListener(Events::loadClassMetadata, $listener);
//			}
//		}
		
		$fixture = new FixtureHelper($em);
		$fixture->build();
		
		$output->writeln("Fixtures finished successfully");
	}
}
