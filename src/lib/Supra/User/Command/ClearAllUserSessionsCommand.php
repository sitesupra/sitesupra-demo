<?php

namespace Supra\User\Command;

use Symfony\Component\Console;

/**
 * Removes all UserSession records
 */
class ClearAllUserSessionsCommand extends Console\Command\Command
{

	/**
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:user:clear_all_sessions')
				->setDescription('Removes all UserSession records');
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		
		$sessionCn = \Supra\User\Entity\UserSession::CN();
		
		$em->createQuery("DELETE FROM {$sessionCn}")
				->execute();
		
		$output->writeln('Done');
	}

}
