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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GroupsAddCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('groups:add')
			->setDescription('Adds a new group, provide --em to use different EntityManager')
			->addOption('em', null, InputArgument::OPTIONAL, 'Entity manager name')
			->addArgument('name', InputArgument::REQUIRED, 'Group name')
			->addArgument('super', InputArgument::OPTIONAL, 'Are these guys superusers?', false);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		if ($em->getRepository('CmsAuthentication:Group')->findOneByName($input->getArgument('name'))) {
			throw new \Exception(sprintf('Group "%s" already exists', $input->getArgument('name')));
		}

		$group = new Group();
		$group->setName($input->getArgument('name'));
		$group->setIsSuper($input->getArgument('super'));

		$em->persist($group);
		$em->flush();

		$output->writeln('Group created!');
	}

}
