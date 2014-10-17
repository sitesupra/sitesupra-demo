<?php

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UsersUpdateCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('users:update')
			->setDescription('Lists groups, provide --em to use different EntityManager')
			->addArgument('login', InputArgument::REQUIRED, 'Login to edit')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name')
			->addOption('password', null, InputArgument::OPTIONAL, 'New password')
			->addOption('active', null, InputArgument::OPTIONAL, 'Active (true or false)')
			->addOption('groups', null, InputArgument::OPTIONAL, 'Group names (currently only one)')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$user = $em->getRepository('CmsAuthentication:User')->findOneByLogin($input->getArgument('login'));

		if (!$user) {
			throw new \Exception(sprintf('User with login "%s" has not been found', $input->getArgument('login')));
		}

		if ($input->getOption('password')) {
			$encoded = $this->container['cms_authentication.encoder_factory']
				->getEncoder($user)
				->encodePassword($input->getOption('password'), $user->getSalt());

			$user->setPassword($encoded);
		}

		$this->container->getDoctrine()->getManager()->flush();

		$output->writeln('User updated!');
	}

}
