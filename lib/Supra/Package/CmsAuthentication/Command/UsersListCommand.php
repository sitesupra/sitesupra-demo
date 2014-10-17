<?php

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UsersListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('users:list')
			->setDescription('Lists users, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$users = $em->getRepository('CmsAuthentication:User')->findBy(array(), array('login' => 'ASC'));

		$table = new Table($output);

		$table->setHeaders(array('ID', 'Login', 'Email', 'Groups', 'Active'));

		$table->addRows(array_map(function ($user) {
			/* @var $user User */

			return array(
				$user->getId(),
				$user->getLogin(),
				$user->getEmail(),
				call_user_func(function ($value) {
					if ($value instanceof Group) {
						return $value->getName();
					} else {
						return '--/--';
					}
				}, $user->getGroup()),
				$user->isActive() ? '<info>Yes</info>' : 'No'
			);
		}, $users));

		$table->render();
	}

}
