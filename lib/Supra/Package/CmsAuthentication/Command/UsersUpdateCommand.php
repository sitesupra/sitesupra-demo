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

		if (!is_null($input->getOption('active'))) {
			$user->setActive((bool)$input->getOption('active'));
		}

		//currently one group by design
		if ($input->getOption('groups')) {
			$group = $em->getRepository('CmsAuthentication:Group')->findOneByName($input->getOption('groups'));

			if ($group) {
				$user->setGroup($group);
			} else {
				throw new \Exception(sprintf('Group "%s" does not exist', $input->getOption('groups')));
			}
		}

		$this->container->getDoctrine()->getManager()->flush();

		$output->writeln('User updated!');
	}

}
