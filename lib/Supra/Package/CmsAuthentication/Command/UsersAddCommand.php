<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UsersAddCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('users:add')
			->setDescription('Adds users, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name')
			->addArgument('login', InputArgument::REQUIRED, 'Login to add (must be a valid e-mail address)')
			->addArgument('password', InputArgument::REQUIRED, 'User\'s password')
			->addOption('name', null, InputArgument::OPTIONAL, 'User\'s name')
			->addOption('email', null, InputArgument::OPTIONAL, 'User\'s email')
			->addOption('active', null, InputArgument::OPTIONAL, 'Active (true or false)')
			->addOption('groups', null, InputArgument::OPTIONAL, 'Group names (currently only one)');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$user = new User();

		$user->setLogin($input->getArgument('login'));
		$user->setName($input->getOption('name') ? $input->getOption('name') : $user->getLogin());
		$user->setEmail($input->getOption('name') ? $input->getOption('name') : $user->getLogin());
		$user->setEmailConfirmed(true);
		$user->setRoles(array('ROLE_USER'));

		if ($input->getOption('groups')) {
			$group = $em->getRepository('CmsAuthentication:Group')->findOneByName($input->getOption('groups'));

			if ($group) {
				$user->setGroup($group);
			} else {
				throw new \Exception(sprintf('Group "%s" does not exist', $input->getOption('groups')));
			}
		}

		$encoded = $this->container['cms_authentication.encoder_factory']
			->getEncoder($user)
			->encodePassword($input->getArgument('password'), $user->getSalt());

		$user->setPassword($encoded);

		if (!is_null($input->getOption('active'))) {
			$user->setActive((bool)$input->getOption('active'));
		}

		$em->persist($user);
		$em->flush();

		$output->writeln('User created!');
	}

}
