<?php

namespace Project\DummyRemote;

use Symfony\Component\Console\Command\Command;
use Supra\User\UserProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Console\Output\CommandOutputWithData;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;

class DummyCommandOne extends Command
{

	protected function configure()
	{
		$this->setName('su:utility:get_all_users')
				->setDescription('Lists all users.')
				->setHelp('Lists all users.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$userProvider = new UserProvider();

		$users = $userProvider->findAllUsers();

		$output->writeln('Trololo123');

		if ($output instanceof CommandOutputWithData) {

			$userData = array();

			foreach ($users as $user) {
				/* @var $user User */

				$userData[] = array(
					'login' => $user->getLogin(),
					'name' => $user->getName(),
					'email' => $user->getEmail(),
					'lastLoginTime' => $user->getLastLoginTime()
				);
			}

			$output->setData($userData);
		} else {

			foreach ($users as $user) {
				/* @var $user User */

				$userLine = array($user->getLogin(), $user->getName(), $user->getEmail(), $user->getLastLoginTime());
				$output->writeln(join(', ', $userLine));
			}
		}

		$output->writeln('Keke555');
	}

}
