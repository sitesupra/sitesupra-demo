<?php

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UsersRemoveCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('users:remove')
			->setDescription('Removes users, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name')
			->addArgument('login', InputArgument::REQUIRED, 'Login to remove');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$user = $em->getRepository('CmsAuthentication:User')->findOneByLogin($input->getArgument('login'));

		if (!$user) {
			throw new \Exception(sprintf('User with login "%s" has not been found', $input->getArgument('login')));
		}

		$em->remove($user);
		$em->flush();

		$output->writeln('User removed!');
	}

}
